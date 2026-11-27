import { expect, test } from '@playwright/test';

const email = 'playwright@example.test';
const password = 'Password123!';

test.beforeEach(async ({ page }) => {
    await page.goto('/login');
    await page.getByRole('textbox', { name: 'Alamat email' }).fill(email);
    await page.getByRole('textbox', { name: 'Kata sandi' }).fill(password);
    await page.getByRole('button', { name: 'Masuk' }).click();
    await page.waitForURL(/\/dashboard$/);
});

test('double-clicking completion creates only one recurring task', async ({ page }) => {
    const serverErrors = [];
    page.on('response', (response) => {
        if (response.status() >= 500) {
            serverErrors.push(response.url());
        }
    });

    await page.goto('/dashboard/tasks');
    const task = page.locator('li').filter({ hasText: 'E2E double-click task' });
    await task.getByRole('button', { name: 'Tandai E2E double-click task selesai' }).dblclick({ delay: 25 });
    await expect(task.getByRole('button', { name: 'Tandai E2E double-click task selesai' })).toHaveAttribute('aria-disabled', 'true');

    await page.reload();
    await expect(page.getByRole('heading', { name: 'E2E double-click task', exact: true })).toHaveCount(2);
    expect(serverErrors).toEqual([]);
});

test('browser back returns to the previous authenticated page', async ({ page }) => {
    await page.goto('/dashboard/calendar');
    await page.goto('/dashboard/tasks');
    await page.goBack();

    await expect(page).toHaveURL(/\/dashboard\/calendar$/);
    await expect(page.getByRole('heading', { name: 'Kalender', level: 1 })).toBeVisible();
});

test('offline navigation shows the cached offline page', async ({ page, context }) => {
    await page.goto('/dashboard');
    await page.waitForFunction(() => navigator.serviceWorker.controller !== null);
    await context.setOffline(true);
    await page.goto('/dashboard/tasks');

    await expect(page.getByRole('heading', { name: 'Kamu sedang offline' })).toBeVisible();
});

test('receipt scanner works under enforced CSP without WebAssembly eval exceptions', async ({ page }) => {
    const consoleMessages = [];
    page.on('console', (message) => consoleMessages.push(message.text()));
    page.on('pageerror', (error) => consoleMessages.push(error.message));

    await page.goto('/dashboard/money?section=transactions&create=transaction');
    const scanner = page.locator('[data-receipt-scan]');
    await expect(scanner).toBeVisible();

    const imageDataUrl = await page.evaluate(() => {
        const canvas = document.createElement('canvas');
        canvas.width = 600;
        canvas.height = 180;
        const context = canvas.getContext('2d');
        context.fillStyle = '#fff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.fillStyle = '#000';
        context.font = '32px sans-serif';
        context.fillText('TOKO UJI  TOTAL 10000', 12, 90);

        return canvas.toDataURL('image/png');
    });
    const image = Buffer.from(imageDataUrl.split(',')[1], 'base64');
    await scanner.locator('[data-receipt-input="camera"]').setInputFiles({
        name: 'synthetic-receipt.png',
        mimeType: 'image/png',
        buffer: image,
    });

    const status = scanner.locator('[data-receipt-status]');
    await expect(status).toContainText(/gagal|cocok|terbaca|mencurigakan|ditemukan/i, { timeout: 60_000 });
    await expect(status).toContainText(/Nominal terbaca|cocok/i);
    await expect(scanner.locator('xpath=ancestor::form').locator('[name="amount"]')).not.toHaveValue('');
    expect(consoleMessages.join('\n')).not.toMatch(/Refused to compile or instantiate WebAssembly/i);
});
