import { randomUUID } from 'node:crypto';
import { existsSync, rmSync } from 'node:fs';
import { createRequire } from 'node:module';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { spawn } from 'node:child_process';

const require = createRequire(import.meta.url);
const tempDir = join(tmpdir(), `jadwal-playwright-${randomUUID()}`);
const testRunner = spawn(process.execPath, [
    require.resolve('@playwright/test/cli'),
    'test',
    ...process.argv.slice(2),
], {
    env: { ...process.env, PLAYWRIGHT_TEMP_DIR: tempDir },
    stdio: 'inherit',
});

for (const signal of ['SIGINT', 'SIGTERM']) {
    process.on(signal, () => testRunner.kill(signal));
}

testRunner.on('exit', (code, signal) => {
    if (existsSync(tempDir)) {
        rmSync(tempDir, { recursive: true, force: true });
    }

    process.exitCode = code ?? (signal ? 1 : 0);
});
