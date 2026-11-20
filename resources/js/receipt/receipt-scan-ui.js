import { scanReceipt, ReceiptScanError } from './receipt-scan.js';

/**
 * Penghubung UI. Tanpa Alpine inline (proyek memakai @alpinejs/csp), hanya DOM biasa.
 * Markup: lihat INTEGRASI.md. Scan TIDAK menyimpan apa pun: hanya mengisi form,
 * lalu user memeriksa dan menekan "Simpan transaksi" seperti biasa.
 */
const STATUS_TEXT = {
    'loading tesseract core': 'Menyiapkan pembaca struk…',
    'initializing tesseract': 'Menyiapkan pembaca struk…',
    'loading language traineddata': 'Mengunduh data bahasa (hanya sekali)…',
    'initializing api': 'Menyiapkan pembaca struk…',
    'recognizing text': 'Membaca struk…',
};

const ERROR_TEXT = {
    not_image: 'File harus berupa foto.',
    too_large: 'Foto terlalu besar (maksimal 15 MB). Coba foto ulang.',
    engine_unavailable: 'Pembaca struk gagal dimuat. Kamu tetap bisa mengisi nominal manual.',
    ocr_failed: 'Struk gagal dibaca. Coba foto lebih dekat dan terang, atau isi manual.',
};

const rupiah = (value) => `Rp ${Number(value).toLocaleString('id-ID')}`;

function setValue(field, value) {
    if (!field) return;
    field.value = value;
    // x-model + rupiahInput.format mendengarkan event "input" untuk memformat 34000 -> 34.000
    field.dispatchEvent(new Event('input', { bubbles: true }));
    field.dispatchEvent(new Event('change', { bubbles: true }));
}

function setup(root) {
    const form = root.closest('form');
    const status = root.querySelector('[data-receipt-status]');
    const suggestions = root.querySelector('[data-receipt-suggestions]');
    const triggers = [...root.querySelectorAll('[data-receipt-trigger]')];
    const inputs = [...root.querySelectorAll('[data-receipt-input]')];
    if (!form || !status || !triggers.length || !inputs.length) return;

    let busy = false;
    const setBusy = (value) => {
        busy = value;
        root.setAttribute('aria-busy', String(value));
        triggers.forEach((button) => { button.disabled = value; });
    };

    triggers.forEach((button) => button.addEventListener('click', () => {
        if (busy) return;
        root.querySelector(`[data-receipt-input="${button.dataset.receiptTrigger}"]`)?.click();
    }));

    inputs.forEach((input) => input.addEventListener('change', async () => {
        const file = input.files?.[0];
        input.value = ''; // supaya foto yang sama bisa dipilih lagi
        if (!file || busy) return;

        setBusy(true);
        suggestions?.replaceChildren();
        status.textContent = 'Menyiapkan…';

        try {
            const result = await scanReceipt(file, {
                onProgress: ({ status: key }) => {
                    if (STATUS_TEXT[key]) status.textContent = STATUS_TEXT[key];
                },
            });
            fill(form, status, suggestions, result);
        } catch (error) {
            status.textContent = ERROR_TEXT[error instanceof ReceiptScanError ? error.code : 'ocr_failed'];
        } finally {
            setBusy(false);
        }
    }));
}

function fill(form, status, suggestions, result) {
    const amount = form.querySelector('[name="amount"]');
    const date = form.querySelector('[name="occurred_on"]');
    const note = form.querySelector('[name="note"]');

    if (result.total !== null) setValue(amount, String(result.total));
    if (result.date) setValue(date, result.date);
    if (result.merchant && note && !note.value.trim()) {
        setValue(note, `Belanja di ${result.merchant}`.slice(0, 255));
    }

    const messages = {
        high: 'Nominal cocok di beberapa bagian struk. Tetap periksa sebelum menyimpan.',
        medium: 'Nominal terbaca, tapi belum bisa dicek silang. Periksa dulu ya.',
        low: 'Hasil belum meyakinkan. Cocokkan nominal dengan struk.',
    };
    const lines = [messages[result.confidence], ...result.warnings.map((warning) => warning.message)];
    status.textContent = result.total === null ? result.warnings.map((w) => w.message).join(' ') : lines.join(' ');

    // Tawarkan nilai lain yang juga muncul di struk (mis. salah baca angka nol)
    result.alternatives.slice(0, 2).forEach((value) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'button button--secondary button--sm';
        button.textContent = `Maksudnya ${rupiah(value)}?`;
        button.addEventListener('click', () => { setValue(amount, String(value)); button.remove(); amount?.focus(); });
        suggestions?.append(button);
    });

    amount?.focus();
}

export function initializeReceiptScan() {
    document.querySelectorAll('[data-receipt-scan]').forEach((root) => {
        if (root.dataset.receiptReady) return;
        root.dataset.receiptReady = '1';
        setup(root);
    });
}
