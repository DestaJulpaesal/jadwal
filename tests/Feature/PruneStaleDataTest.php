<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PruneStaleDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_housekeeping_prunes_audit_logs_older_than_one_year(): void
    {
        AdminAuditLog::query()->create([
            'action' => 'account.role_changed',
            'target_type' => 'App\\Models\\User',
            'target_id' => 1,
            'created_at' => now()->subDays(366),
        ]);
        AdminAuditLog::query()->create([
            'action' => 'account.role_changed',
            'target_type' => 'App\\Models\\User',
            'target_id' => 2,
            'created_at' => now()->subDays(364),
        ]);

        $this->artisan('app:prune-stale-data')->assertExitCode(0);

        $this->assertDatabaseCount('admin_audit_logs', 1);
        $this->assertDatabaseHas('admin_audit_logs', ['target_id' => 2]);
    }
}
