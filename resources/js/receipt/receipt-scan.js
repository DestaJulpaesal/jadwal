import { loadReceiptCanvas } from './image-prep.js';
import { parseReceiptText } from './receipt-parser.js';

/**
 * OCR struk 100% di perangkat (tesseract.js, gratis). Foto tidak dikirim ke server mana pun.
 * Semua file OCR di-host sendiri di /vendor/tesseract (lihat scripts/copy-ocr-assets.mjs)
 * karena CSP aplikasi hanya mengizinkan 'self'.
 */
const ASSET_BASE = '/vendor/tesseract';
const MAX_BYTES = 15 * 1024 * 1024;
const IDLE_MS = 60_000;

let workerPromise = null;
let idleTimer = null;
let progressListener = null;

export class ReceiptScanError extends Error {
    constructor(code, message) {
        super(message);
        this.code = code;
    }
}

function getWorker() {
    if (!workerPromise) {
        workerPromise = import('tesseract.js')
            .then(({ createWorker }) => createWorker('ind', 1, {
                workerPath: `${ASSET_BASE}/worker.min.js`,
                corePath: `${ASSET_BASE}/core`,
                langPath: `${ASSET_BASE}/lang`,
                workerBlobURL: false, // blob: diblokir oleh worker-src 'self'
                gzip: false,
                logger: (message) => progressListener?.(message),
            }))
            .catch((error) => {
                workerPromise = null;
                throw new ReceiptScanError('engine_unavailable', error?.message ?? 'Pembaca struk gagal dimuat.');
            });
    }
    return workerPromise;
}

/** Hentikan worker (membebaskan memori). Dipanggil otomatis setelah idle. */
export async function disposeReceiptScanner() {
    clearTimeout(idleTimer);
    const pending = workerPromise;
    workerPromise = null;
    if (pending) {
        try { await (await pending).terminate(); } catch { /* sudah mati */ }
    }
}

/**
 * @param {File} file foto struk
 * @param {{ onProgress?: (m: {status: string, progress: number}) => void, now?: Date }} [options]
 * @returns {Promise<ReturnType<typeof parseReceiptText>>} hasil terstruktur (tanpa teks mentah)
 */
export async function scanReceipt(file, { onProgress, now } = {}) {
    if (!file || !String(file.type).startsWith('image/')) {
        throw new ReceiptScanError('not_image', 'File harus berupa foto.');
    }
    if (file.size > MAX_BYTES) {
        throw new ReceiptScanError('too_large', 'Foto terlalu besar (maksimal 15 MB).');
    }

    clearTimeout(idleTimer);
    progressListener = onProgress ?? null;
    let canvas;
    try {
        canvas = await loadReceiptCanvas(file);
        const worker = await getWorker();
        const { data } = await worker.recognize(canvas);
        return parseReceiptText(data.text, { now });
    } catch (error) {
        if (error instanceof ReceiptScanError) throw error;
        throw new ReceiptScanError('ocr_failed', error?.message ?? 'Struk gagal dibaca.');
    } finally {
        progressListener = null;
        if (canvas) { canvas.width = 0; canvas.height = 0; }
        idleTimer = setTimeout(disposeReceiptScanner, IDLE_MS);
    }
}
