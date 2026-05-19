<?php

namespace Tests\Feature;

use App\Services\DatabaseBackupService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

class DataImportDatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_data_import_backup_downloads_pg_dump_file(): void
    {
        $this->withoutMiddleware([
            \App\Http\Middleware\ErpMaintenanceMode::class,
            \App\Http\Middleware\LogErpActivity::class,
            \Spatie\Permission\Middleware\RoleMiddleware::class,
        ]);

        $user = User::factory()->create();
        $this->instance(DatabaseBackupService::class, new class extends DatabaseBackupService
        {
            public function downloadPostgresDump(?string $connectionName = null): StreamedResponse
            {
                return response()->streamDownload(function (): void {
                    echo "-- PostgreSQL database dump\n";
                    echo "CREATE TABLE users (id bigint primary key);\n";
                }, 'backup-database-test.sql', [
                    'Content-Type' => 'application/sql; charset=UTF-8',
                ]);
            }
        });

        $response = $this
            ->actingAs($user)
            ->get(route('erp.admin.data-import.backup'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/sql; charset=UTF-8');
        $response->assertHeader('content-disposition');

        $payload = $response->streamedContent();
        $this->assertStringContainsString('PostgreSQL database dump', $payload);
        $this->assertStringContainsString('CREATE TABLE users', $payload);
    }
}
