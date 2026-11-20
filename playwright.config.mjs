import { randomUUID } from 'node:crypto';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { defineConfig } from '@playwright/test';

const port = 8024;
const tempDir = process.env.PLAYWRIGHT_TEMP_DIR
    || join(tmpdir(), `jadwal-playwright-${randomUUID()}`);

export const e2eEnv = {
    ...process.env,
    APP_ENV: 'testing',
    APP_DEBUG: 'false',
    APP_KEY: 'base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=',
    APP_URL: `http://127.0.0.1:${port}`,
    APP_CONFIG_CACHE: join(tempDir, 'config.php'),
    APP_ROUTES_CACHE: join(tempDir, 'routes.php'),
    VITE_HOT_FILE: join(tempDir, 'hot'),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: join(tempDir, 'database.sqlite'),
    SESSION_DRIVER: 'database',
    SESSION_SECURE_COOKIE: 'false',
    SESSION_SAME_SITE: 'lax',
    CACHE_STORE: 'array',
    QUEUE_CONNECTION: 'sync',
    MAIL_MAILER: 'array',
    CSP_MODE: 'enforce',
    PLAYWRIGHT_DB: join(tempDir, 'database.sqlite'),
};

Object.assign(process.env, e2eEnv);

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: join(tempDir, 'test-results'),
    fullyParallel: false,
    workers: 1,
    globalTeardown: './tests/e2e/global-teardown.mjs',
    use: {
        baseURL: e2eEnv.APP_URL,
        trace: 'retain-on-failure',
    },
    webServer: {
        command: 'node tests/e2e/serve.mjs',
        url: `${e2eEnv.APP_URL}/login`,
        reuseExistingServer: false,
        env: e2eEnv,
        timeout: 60_000,
    },
});
