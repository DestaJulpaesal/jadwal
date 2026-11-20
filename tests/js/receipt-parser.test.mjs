import test from 'node:test';
import assert from 'node:assert/strict';
import { parseAmountToken, parseReceiptText } from '../../resources/js/receipt/receipt-parser.js';

const now = new Date(2026, 10, 20); // 20 Nov 2026

test('parseAmountToken: format rupiah umum', () => {
    assert.equal(parseAmountToken('34.000'), 34000);
    assert.equal(parseAmountToken('Rp34.000'), 34000);
    assert.equal(parseAmountToken('34.000,00'), 34000);
    assert.equal(parseAmountToken('1.250.000'), 1250000);
    assert.equal(parseAmountToken('34000'), 34000);
    assert.equal(parseAmountToken('34.OOO'), 34000); // salah baca huruf O
    assert.equal(parseAmountToken('3l.000'), 31000); // salah baca huruf l
});

test('parseAmountToken: menolak yang ambigu atau bukan nominal', () => {
    for (const bad of ['8.00', '12,50', '2', '600ML', '20/11/2026', '14:32', 'KASIR', '0', '', '8991234567890']) {
        assert.equal(parseAmountToken(bad), null, `harus null: ${bad}`);
    }
});

test('struk bersih: semua sumber cocok -> high', () => {
    const r = parseReceiptText(`INDOMARET
JL. DAGO NO 12 BANDUNG
20/11/2026 14:32 KASIR 02
AQUA 600ML 2 x 4.000 8.000
INDOMIE GORENG 3 x 3.500 10.500
ROTI TAWAR 15.500
TOTAL 34.000
TUNAI 50.000
KEMBALI 16.000
TERIMA KASIH`, { now });
    assert.equal(r.total, 34000);
    assert.equal(r.confidence, 'high');
    assert.equal(r.date, '2026-11-20');
    assert.equal(r.merchant, 'Indomaret');
    assert.deepEqual(r.warnings, []);
});

test('salah baca nol (006) dari OCR nyata -> jangan yakin', () => {
    const r = parseReceiptText(`INDOMARET
20/11/2026 14:32 KASIR 02
ANUA 6OOML 2 x 4.000 8.00
INDOMIE GORENG 3 x 3.500 10.506
ROTI TAWAR 15.506
TOTAL 34.006
TUNAI 50.006
KEMBALI 16.006`, { now });
    assert.notEqual(r.confidence, 'high');
    assert.ok(r.warnings.some((w) => w.code === 'total_mismatch'));
    assert.ok([r.total, ...r.alternatives].includes(34000), 'nilai benar harus tersedia sebagai alternatif');
});

test('SUBTOTAL, HEMAT, PPN tidak dianggap total', () => {
    const r = parseReceiptText(`ALFAMART
15-11-2026
SUSU UHT 12.000
KOPI SACHET 8.000
SUB TOTAL 20.000
ANDA HEMAT 3.000
TOTAL 20.000
DEBIT 20.000`, { now });
    assert.equal(r.total, 20000);
    assert.equal(r.confidence, 'high');
});

test('GRAND TOTAL menang atas TOTAL, desimal ,00 dibuang', () => {
    const r = parseReceiptText(`SUPERINDO
2026-11-18
TOTAL 100.000,00
PPN 11.000,00
GRAND TOTAL 111.000,00
QRIS 111.000,00`, { now });
    assert.equal(r.total, 111000);
    assert.equal(r.date, '2026-11-18');
});

test('total hilang -> turunkan dari TUNAI - KEMBALI', () => {
    const r = parseReceiptText(`TOKO BU SARI
12 Nov 2026
TUNAI 100.000
KEMBALI 35.000`, { now });
    assert.equal(r.total, 65000);
    assert.equal(r.totalSource, 'payment');
    assert.equal(r.confidence, 'medium');
    assert.equal(r.date, '2026-11-12');
});

test('tanggal masa depan atau ngawur ditolak', () => {
    assert.equal(parseReceiptText('TOKO X\n20/11/2027\nTOTAL 10.000', { now }).date, null);
    assert.equal(parseReceiptText('TOKO X\n31/02/2026\nTOTAL 10.000', { now }).date, null);
    assert.equal(parseReceiptText('TOKO X\n2O/11/2O26\nTOTAL 10.000', { now }).date, '2026-11-20');
});

test('tanpa kata TOTAL -> tebakan nominal terbesar, confidence low', () => {
    const r = parseReceiptText('WARUNG MAKAN\nNASI 15.000\nTEH 5.000', { now });
    assert.equal(r.total, 15000);
    assert.equal(r.confidence, 'low');
    assert.ok(r.warnings.some((w) => w.code === 'total_guess'));
});

test('teks kosong atau sampah tidak melempar error', () => {
    for (const t of ['', '   \n  ', null, undefined, '@@@ ### !!!']) {
        const r = parseReceiptText(t, { now });
        assert.equal(r.total, null);
        assert.ok(r.warnings.some((w) => w.code === 'total_not_found'));
    }
});

test('alamat dan nomor telepon bukan nama toko atau nominal', () => {
    const r = parseReceiptText(`JL. MERDEKA 17
TELP 022-4231234
KEDAI KOPI SENJA
TOTAL 27.500`, { now });
    assert.equal(r.merchant, 'Kedai Kopi Senja');
    assert.equal(r.total, 27500);
});

test('satuan menempel ke angka (600ML, 250GR, 1KG) bukan nominal', () => {
    for (const bad of ['600ML', '250GR', '1KG', '1500ML', '(600ML)']) {
        assert.equal(parseAmountToken(bad), null, `harus null: ${bad}`);
    }
    assert.equal(parseAmountToken('(34.000)'), 34000);
    assert.equal(parseAmountToken('34.000,'), 34000);
});

test('alternatif sampah dari jumlah-baris (noise) tidak ditawarkan saat keyakinan tinggi', () => {
    const r = parseReceiptText(`INDOMARET
20/11/2026
ROTI TAWAR 15.500
. 106.009
TOTAL 34.000
TUNAI 50.000
KEMBALI 16.000`, { now });
    assert.equal(r.total, 34000);
    assert.equal(r.confidence, 'high');
    assert.deepEqual(r.alternatives, []);
});
