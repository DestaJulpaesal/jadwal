/**
 * Parser teks struk (hasil OCR) -> { total, date, merchant, confidence, warnings }.
 * Murni fungsi (tanpa DOM), jadi bisa diuji di Node dan dipakai di browser.
 *
 * Prinsip: OCR bisa salah baca angka, jadi nominal TIDAK dipercaya dari satu sumber.
 * Total dicari dari 3 sumber lalu "divoting":
 *   1) baris berkata kunci  (TOTAL, GRAND TOTAL, JUMLAH, ...)
 *   2) pembayaran           (TUNAI/DEBIT/QRIS dikurangi KEMBALI)
 *   3) jumlah baris barang
 * Makin banyak sumber yang cocok, makin tinggi keyakinan. Hasil selalu dikonfirmasi user.
 */

const MONTHS = {
    JAN: 1, FEB: 2, MAR: 3, APR: 4, MEI: 5, MAY: 5, JUN: 6, JUL: 7,
    AGU: 8, AGS: 8, AUG: 8, SEP: 9, OKT: 10, OCT: 10, NOV: 11, NOP: 11, DES: 12, DEC: 12,
};

const TOTAL_LEVELS = [
    /GRAND\s*TOTAL/,
    /TOTAL\s*(BAYAR|BELANJA|TAGIHAN|AKHIR|PEMBAYARAN)|\bTAGIHAN\b|\bNETTO\b|AMOUNT\s*DUE/,
    /\bTOTAL\b/,
    /\bJUMLAH\b/,
];
const NOT_TOTAL = /SUB\s*TOTAL|TOTAL\s*(ITEM|QTY|BARANG|HEMAT|DISKON|DISC|PPN|PAJAK|POTONGAN|SERVICE)|(HEMAT|DISKON|PPN|DPP|POIN)\s*TOTAL|HEMAT|DISKON/;
const CHANGE_RE = /\b(KEMBALI|KEMBALIAN|CHANGE)\b/;
const PAID_RE = /\b(TUNAI|CASH|DEBIT|KREDIT|CREDIT|QRIS|GOPAY|OVO|DANA|SHOPEEPAY|LINKAJA|BAYAR|PEMBAYARAN|KARTU)\b/;
const NOISE_LINE = /^(JL|JLN|JALAN|NO\b|TELP|TLP|HP\b|NPWP|KASIR|STRUK|NOTA|TERIMA|SELAMAT|WWW|HTTP|EMAIL|ALAMAT)/;

/** "34.000" | "Rp 34.000,00" | "34.OOO" | "34000" -> 34000, selain itu null (tidak ambigu). */
export function parseAmountToken(raw, { max = 1_000_000_000 } = {}) {
    let t = String(raw).replace(/^(RP\.?|IDR)/i, '');
    t = t.replace(/^[^0-9A-Za-z|]+|[^0-9A-Za-z|]+$/g, ''); // buang tanda baca saja, bukan huruf ("600ML" bukan nominal)
    if (!t || !/^[0-9OoIl|.,]+$/.test(t)) return null;

    const grouped = /^[0-9OoIl|]{1,3}([.,][0-9OoIl|]{3})+([.,][0-9Oo]{2})?$/.test(t);
    if (!/[0-9]/.test(t) && !grouped) return null;

    t = t.replace(/[Oo]/g, '0').replace(/[Il|]/g, '1');

    const withDecimals = /^(\d{1,3}(?:[.,]\d{3})+)[.,]\d{2}$/.exec(t);
    if (withDecimals) t = withDecimals[1];

    let digits;
    if (/^\d{1,3}(?:[.,]\d{3})+$/.test(t)) digits = t.replace(/[.,]/g, '');
    else if (/^\d{3,}$/.test(t)) digits = t;
    else return null;

    const value = Number(digits);
    return value > 0 && value <= max ? value : null;
}

function amountsIn(line, options) {
    return line.split(/\s+/).map((token) => parseAmountToken(token, options)).filter((v) => v !== null);
}

function toLines(text) {
    return String(text ?? '')
        .split(/\r?\n/)
        .map((l) => l.replace(/\s+/g, ' ').trim())
        .filter(Boolean)
        .map((raw) => ({ raw, up: raw.toUpperCase() }));
}

function ymd(y, m, d) {
    return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
}

function localToday(now) {
    return new Date(now.getFullYear(), now.getMonth(), now.getDate());
}

function validDate(y, m, d, now, maxAgeDays) {
    if (y < 100) y += 2000;
    if (m < 1 || m > 12 || d < 1 || d > 31) return null;
    const date = new Date(y, m - 1, d);
    if (date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) return null;
    const today = localToday(now);
    const diffDays = (date - today) / 86_400_000;
    if (diffDays > 1 || diffDays < -maxAgeDays) return null;
    return ymd(y, m, d);
}

function findDate(lines, now, maxAgeDays) {
    for (const { up } of lines) {
        let s = up;
        for (let i = 0; i < 2; i++) s = s.replace(/[\d/\-.]O|O[\d/\-.]/g, (m) => m.replace('O', '0'));

        let m = /\b(\d{4})[/\-.](\d{1,2})[/\-.](\d{1,2})\b/.exec(s);
        if (m) { const r = validDate(+m[1], +m[2], +m[3], now, maxAgeDays); if (r) return r; }

        m = /(\d{1,2})\s*[/\-.]\s*(\d{1,2})\s*[/\-.]\s*(\d{4}|\d{2})\b/.exec(s);
        if (m) {
            let day = +m[1], mon = +m[2];
            if (mon > 12 && day <= 12) [day, mon] = [mon, day];
            const r = validDate(+m[3], mon, day, now, maxAgeDays);
            if (r) return r;
        }

        m = /\b(\d{1,2})[\s-]*([A-Z]{3,9})[\s\-.,]*(\d{4}|\d{2})\b/.exec(s);
        if (m && MONTHS[m[2].slice(0, 3)]) {
            const r = validDate(+m[3], MONTHS[m[2].slice(0, 3)], +m[1], now, maxAgeDays);
            if (r) return r;
        }
    }
    return null;
}

function findMerchant(lines) {
    for (const { raw, up } of lines.slice(0, 8)) {
        const letters = (up.match(/[A-Z]/g) || []).length;
        const digits = (up.match(/\d/g) || []).length;
        if (letters < 3 || digits > letters * 0.3 || NOISE_LINE.test(up)) continue;
        if (/\d{1,2}[/\-.]\d{1,2}[/\-.]\d{2,4}/.test(up)) continue;
        const clean = raw.replace(/[^A-Za-z0-9&'.\- ]/g, ' ').replace(/\s+/g, ' ').trim();
        if (clean.length >= 3) {
            return clean.toLowerCase().replace(/(^|[\s-])([a-z])/g, (_, a, b) => a + b.toUpperCase());
        }
    }
    return null;
}

function findKeywordTotal(lines, options) {
    for (const level of TOTAL_LEVELS) {
        let found = null;
        lines.forEach(({ up }, i) => {
            if (CHANGE_RE.test(up) || NOT_TOTAL.test(up) || !level.test(up)) return;
            const same = amountsIn(up, options);
            const next = same.length === 0 && lines[i + 1] ? amountsIn(lines[i + 1].up, options) : [];
            const value = same.length ? same[same.length - 1] : next[0] ?? null;
            if (value !== null) found = value;
        });
        if (found !== null) return found;
    }
    return null;
}

function findPayment(lines, options) {
    let paid = null;
    let change = 0;
    for (const { up } of lines) {
        const isTotal = TOTAL_LEVELS.some((r) => r.test(up));
        const amounts = amountsIn(up, options);
        if (!amounts.length) continue;
        const last = amounts[amounts.length - 1];
        if (CHANGE_RE.test(up)) change = last;
        else if (!isTotal && !NOT_TOTAL.test(up) && PAID_RE.test(up)) paid = last;
    }
    if (paid === null || paid - change <= 0) return null;
    return paid - change;
}

function findItemsSum(lines, options) {
    const end = lines.findIndex(({ up }) => /SUB\s*TOTAL/.test(up) || TOTAL_LEVELS.some((r) => r.test(up)));
    const region = end === -1 ? [] : lines.slice(0, end);
    let sum = 0;
    let count = 0;
    let reliable = true;
    for (const { up } of region) {
        if ((up.match(/[A-Z]/g) || []).length < 3 || NOISE_LINE.test(up)) continue;
        const tokens = up.split(/\s+/);
        const lastToken = tokens[tokens.length - 1];
        if (/^\d{1,3}[.,]\d{1,2}$/.test(lastToken)) { reliable = false; continue; }
        const amount = parseAmountToken(lastToken, options);
        if (amount !== null) { sum += amount; count += 1; }
    }
    return reliable && count > 0 ? sum : null;
}

export function parseReceiptText(text, { now = new Date(), maxAmount = 1_000_000_000, maxAgeDays = 366 } = {}) {
    const lines = toLines(text);
    const options = { max: maxAmount };
    const warnings = [];

    const sources = [
        ['keyword', findKeywordTotal(lines, options)],
        ['payment', findPayment(lines, options)],
        ['items', findItemsSum(lines, options)],
    ].filter(([, v]) => v !== null);

    const groups = new Map();
    for (const [source, value] of sources) {
        if (!groups.has(value)) groups.set(value, []);
        groups.get(value).push(source);
    }
    const order = { keyword: 0, payment: 1, items: 2 };
    const ranked = [...groups.entries()].sort((a, b) =>
        b[1].length - a[1].length || Math.min(...a[1].map((s) => order[s])) - Math.min(...b[1].map((s) => order[s])));

    let total = null;
    let totalSource = null;
    let confidence = 'low';
    let alternatives = [];

    if (ranked.length) {
        const [value, via] = ranked[0];
        total = value;
        totalSource = via.join('+');
        if (via.length >= 2) {
            confidence = 'high';
        } else {
            const conflict = ranked.slice(1).some(([, s]) => s.some((x) => x !== 'items'));
            confidence = conflict ? 'low' : 'medium';
            if (conflict || ranked.length > 1) {
                warnings.push({ code: 'total_mismatch', message: 'Angka total di struk tidak saling cocok. Periksa nominalnya dengan foto.' });
            }
        }
        // Alternatif hanya yang didukung kata kunci/pembayaran. Jumlah-baris-barang saja terlalu
        // mudah tercemar bercak noise, jadi tidak ditawarkan ke user kecuali keyakinan rendah.
        alternatives = ranked.slice(1)
            .filter(([, s]) => confidence !== 'high' ? true : s.some((x) => x !== 'items'))
            .filter(([, s]) => s.some((x) => x !== 'items') || confidence === 'low')
            .map(([v]) => v);
    } else {
        const all = lines.flatMap(({ up }) => amountsIn(up, options));
        if (all.length) {
            total = Math.max(...all);
            totalSource = 'max';
            warnings.push({ code: 'total_guess', message: 'Kata "TOTAL" tidak terbaca. Nominal terbesar dipakai sebagai tebakan.' });
        } else {
            warnings.push({ code: 'total_not_found', message: 'Nominal tidak terbaca. Isi manual atau coba foto ulang.' });
        }
    }

    const date = findDate(lines, now, maxAgeDays);
    if (!date) warnings.push({ code: 'date_unreliable', message: 'Tanggal di struk tidak terbaca, dipakai tanggal hari ini.' });

    return { total, totalSource, confidence, alternatives, date, merchant: findMerchant(lines), warnings };
}
