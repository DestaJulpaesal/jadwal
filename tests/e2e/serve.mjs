import { execFileSync, spawn } from 'node:child_process';
import { closeSync, existsSync, mkdirSync, openSync, rmSync } from 'node:fs';
import { dirname } from 'node:path';

if (!process.env.PLAYWRIGHT_DB) {
    throw new Error('PLAYWRIGHT_DB must be set for browser tests.');
}

mkdirSync(dirname(process.env.PLAYWRIGHT_DB), { recursive: true });
closeSync(openSync(process.env.PLAYWRIGHT_DB, 'a'));

for (const args of [
    ['artisan', 'migrate:fresh', '--seed', '--force'],
    ['tests/e2e/seed.php'],
]) {
    execFileSync('php', args, { env: process.env, stdio: 'inherit' });
}

const server = spawn('php', [
    'artisan',
    'serve',
    '--host=127.0.0.1',
    '--port=8024',
    '--no-reload',
], { env: process.env, stdio: 'inherit' });

const stopServer = () => server.kill('SIGTERM');
process.on('SIGINT', stopServer);
process.on('SIGTERM', stopServer);
server.on('exit', (code) => {
    const tempDir = dirname(process.env.PLAYWRIGHT_DB);
    if (existsSync(tempDir)) {
        rmSync(tempDir, { recursive: true, force: true });
    }

    process.exit(code ?? 0);
});
