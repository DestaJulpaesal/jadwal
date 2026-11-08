<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SqlitePragmasTest extends TestCase
{
    use RefreshDatabase;

    public function test_sqlite_connection_uses_foreign_keys_busy_timeout_and_normal_synchronous_mode(): void
    {
        $pdo = DB::connection('sqlite')->getPdo();

        $this->assertSame(1, (int) $pdo->query('PRAGMA foreign_keys')->fetchColumn());
        $this->assertSame(5000, (int) $pdo->query('PRAGMA busy_timeout')->fetchColumn());
        $this->assertSame(1, (int) $pdo->query('PRAGMA synchronous')->fetchColumn());
        $this->assertSame('memory', strtolower((string) $pdo->query('PRAGMA journal_mode')->fetchColumn()));
    }
}
