import test from 'node:test';
import assert from 'node:assert/strict';
import { normalizeGray, targetScale } from '../../resources/js/receipt/image-prep.js';

test('normalizeGray: hasil abu-abu dan kontras diregangkan', () => {
    const w = 10, h = 10, data = new Uint8ClampedArray(w * h * 4);
    for (let i = 0; i < w * h; i++) { // kontras rendah: 100..140
        const v = i % 2 ? 140 : 100;
        data.set([v, v, v, 255], i * 4);
    }
    const out = normalizeGray({ data, width: w, height: h });
    const values = new Set();
    for (let i = 0; i < w * h; i++) {
        assert.equal(out.data[i * 4], out.data[i * 4 + 1]);
        assert.equal(out.data[i * 4], out.data[i * 4 + 2]);
        values.add(out.data[i * 4]);
    }
    assert.ok(Math.min(...values) <= 5 && Math.max(...values) >= 250, 'kontras harus melebar');
});

test('normalizeGray: gambar rata tidak menghasilkan NaN', () => {
    const data = new Uint8ClampedArray(4 * 4 * 4).fill(200);
    const out = normalizeGray({ data, width: 4, height: 4 });
    assert.ok(out.data.every((v) => Number.isFinite(v)));
});

test('targetScale: turunkan foto raksasa, naikkan yang kecil, biarkan yang pas', () => {
    assert.ok(targetScale(4000, 3000) < 1);
    assert.ok(Math.round(4000 * 3000 * targetScale(4000, 3000) ** 2) <= 5_000_000);
    assert.equal(targetScale(2000, 1500), 1);
    assert.equal(targetScale(500, 800), 2);
    assert.equal(targetScale(100, 100), 2.5);
});
