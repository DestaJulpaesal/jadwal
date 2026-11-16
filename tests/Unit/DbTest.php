<?php

namespace Tests\Unit;

use App\Support\Db;
use Tests\TestCase;

class DbTest extends TestCase
{
    public function test_immediate_runs_and_commits_a_sqlite_operation(): void
    {
        $result = Db::immediate(fn () => \Illuminate\Support\Facades\DB::selectOne('SELECT 1 AS value')->value);

        $this->assertSame(1, $result);
        $this->assertFalse(\Illuminate\Support\Facades\DB::connection()->getPdo()->inTransaction());
    }
}
