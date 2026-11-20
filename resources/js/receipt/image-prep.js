/**
 * Persiapan gambar struk sebelum OCR.
 *
 * Sengaja SEDERHANA: hanya orientasi EXIF, ukuran, grayscale, dan regang kontras.
 * Binarisasi/threshold buatan sendiri sudah diuji dan justru MENURUNKAN akurasi
 * (Tesseract punya binarisasi sendiri), jadi tidak dipakai.
 */

/** Grayscale + regangkan kontras (persentil 1-99). Murni: bisa diuji di Node. */
export function normalizeGray(img) {
    const { width, height, data } = img;
    const total = width * height;
    const gray = new Float32Array(total);
    const hist = new Uint32Array(256);
    for (let i = 0, p = 0; i < total; i++, p += 4) {
        gray[i] = 0.299 * data[p] + 0.587 * data[p + 1] + 0.114 * data[p + 2];
        hist[gray[i] | 0]++;
    }
    const percentile = (q) => {
        let acc = 0;
        const target = total * q;
        for (let v = 0; v < 256; v++) { acc += hist[v]; if (acc >= target) return v; }
        return 255;
    };
    const low = percentile(0.01);
    const high = Math.max(percentile(0.99), low + 1);
    const out = new Uint8ClampedArray(data.length);
    for (let i = 0, p = 0; i < total; i++, p += 4) {
        const v = Math.min(255, Math.max(0, ((gray[i] - low) * 255) / (high - low)));
        out[p] = out[p + 1] = out[p + 2] = v;
        out[p + 3] = 255;
    }
    return { data: out, width, height };
}

/** Skala: jangan melebihi maxPixels (memori HP), dan naikkan foto yang terlalu kecil. */
export function targetScale(width, height, { maxPixels = 5_000_000, minWidth = 1000, maxUpscale = 2.5 } = {}) {
    let scale = 1;
    if (width * height > maxPixels) scale = Math.sqrt(maxPixels / (width * height));
    else if (width < minWidth) scale = Math.min(maxUpscale, minWidth / width);
    return scale;
}

/** Muat File foto -> <canvas> siap OCR. Hanya jalan di browser. */
export async function loadReceiptCanvas(file, options = {}) {
    let bitmap;
    try {
        bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    } catch {
        bitmap = await createImageBitmap(file);
    }

    try {
        const scale = targetScale(bitmap.width, bitmap.height, options);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(bitmap.width * scale));
        canvas.height = Math.max(1, Math.round(bitmap.height * scale));
        const context = canvas.getContext('2d', { willReadFrequently: true });
        context.imageSmoothingQuality = 'high';
        context.drawImage(bitmap, 0, 0, canvas.width, canvas.height);

        const normalized = normalizeGray(context.getImageData(0, 0, canvas.width, canvas.height));
        context.putImageData(new ImageData(normalized.data, normalized.width, normalized.height), 0, 0);

        return canvas;
    } finally {
        bitmap.close?.();
    }
}
