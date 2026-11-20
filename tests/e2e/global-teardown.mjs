import { existsSync, rmSync } from 'node:fs';
import { dirname } from 'node:path';

export default async function globalTeardown() {
    const database = process.env.PLAYWRIGHT_DB;
    if (!database) {
        throw new Error('PLAYWRIGHT_DB must be set for browser tests.');
    }

    const tempDir = dirname(database);
    if (existsSync(tempDir)) {
        rmSync(tempDir, { recursive: true, force: true });
    }
}
