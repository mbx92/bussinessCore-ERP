<?php

namespace App\Services;

use App\Models\ErpSetting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
class AppInstallationService
{
    /**
     * @return array{database_ready: bool, tables_ready: bool, installed: bool}
     */
    public function status(): array
    {
        $databaseReady = $this->databaseConnectionWorks();
        $tablesReady = $databaseReady && $this->coreTablesExist();
        $installed = false;

        if ($tablesReady) {
            try {
                $installed = ErpSetting::query()->whereNotNull('installed_at')->exists();
            } catch (\Throwable) {
                $installed = false;
            }
        }

        return [
            'database_ready' => $databaseReady,
            'tables_ready' => $tablesReady,
            'installed' => $installed,
        ];
    }

    public function databaseConnectionWorks(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, string|null>  $config
     * @return array{ok: bool, message: string, warning?: string|null}
     */
    public function testDatabaseConnection(array $config): array
    {
        try {
            $this->applyDatabaseConfig($config);

            if (($config['connection'] ?? 'pgsql') === 'sqlite') {
                return [
                    'ok' => true,
                    'message' => 'File SQLite siap dipakai.',
                ];
            }

            DB::connection()->getPdo();

            return [
                'ok' => true,
                'message' => 'Koneksi database berhasil.',
                'warning' => $this->credentialVerificationWarning($config),
            ];
        } catch (\Throwable $exception) {
            return [
                'ok' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, string|null>  $config
     */
    private function credentialVerificationWarning(array $config): ?string
    {
        $connection = (string) ($config['connection'] ?? 'pgsql');

        if ($connection === 'sqlite') {
            return null;
        }

        $username = (string) ($config['username'] ?? '');
        $database = (string) ($config['database'] ?? '');
        $host = (string) ($config['host'] ?? '127.0.0.1');
        $port = (string) ($config['port'] ?? ($connection === 'pgsql' ? '5432' : '3306'));

        try {
            if ($connection === 'pgsql') {
                new \PDO(
                    sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $database),
                    $username,
                    '__definitely_wrong_password_probe__',
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
                );
            } elseif ($connection === 'mysql') {
                new \PDO(
                    sprintf('mysql:host=%s;port=%s;dbname=%s', $host, $port),
                    $username,
                    '__definitely_wrong_password_probe__',
                    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION],
                );
            } else {
                return null;
            }

            return 'Server database menerima koneksi walau password salah. Kemungkinan auth server memakai mode trust/peer atau password tidak diverifikasi.';
        } catch (\Throwable) {
            return null;
        }
    }

    public function coreTablesExist(): bool
    {
        try {
            return Schema::hasTable('migrations')
                && Schema::hasTable('erp_settings')
                && Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  array<string, string|null>  $config
     */
    public function applyDatabaseConfig(array $config): void
    {
        if (App::environment('testing')) {
            return;
        }

        $connection = (string) ($config['connection'] ?? 'pgsql');

        Config::set('database.default', $connection);

        if ($connection === 'sqlite') {
            $database = (string) ($config['database'] ?? database_path('database.sqlite'));
            $absolutePath = str_starts_with($database, DIRECTORY_SEPARATOR) ? $database : base_path($database);
            $directory = dirname($absolutePath);

            if (! is_dir($directory)) {
                File::ensureDirectoryExists($directory);
            }

            if (! file_exists($absolutePath)) {
                File::put($absolutePath, '');
            }

            Config::set('database.connections.sqlite.database', $absolutePath);
            DB::purge('sqlite');
            DB::reconnect('sqlite');

            return;
        }

        Config::set("database.connections.{$connection}.host", $config['host'] ?? '127.0.0.1');
        Config::set("database.connections.{$connection}.port", $config['port'] ?? ($connection === 'pgsql' ? '5432' : '3306'));
        Config::set("database.connections.{$connection}.database", $config['database'] ?? '');
        Config::set("database.connections.{$connection}.username", $config['username'] ?? '');
        Config::set("database.connections.{$connection}.password", $config['password'] ?? '');

        DB::purge($connection);
        DB::reconnect($connection);
    }

    /**
     * @param  array<string, string|null>  $config
     */
    public function persistDatabaseConfig(array $config): void
    {
        if (App::environment('testing')) {
            return;
        }

        $connection = (string) ($config['connection'] ?? 'pgsql');
        $updates = [
            'DB_CONNECTION' => $connection,
            'DB_DATABASE' => (string) ($config['database'] ?? ''),
        ];

        if ($connection !== 'sqlite') {
            $updates['DB_HOST'] = (string) ($config['host'] ?? '127.0.0.1');
            $updates['DB_PORT'] = (string) ($config['port'] ?? ($connection === 'pgsql' ? '5432' : '3306'));
            $updates['DB_USERNAME'] = (string) ($config['username'] ?? '');
            $updates['DB_PASSWORD'] = (string) ($config['password'] ?? '');
        }

        $this->writeEnvValues($updates);
    }

    public function runMigrations(): void
    {
        if (App::environment('testing')) {
            return;
        }

        Artisan::call('migrate', ['--force' => true]);
    }

    /**
     * @param  array<string, string>  $values
     */
    private function writeEnvValues(array $values): void
    {
        $path = base_path('.env');
        $content = file_exists($path) ? File::get($path) : File::get(base_path('.env.example'));

        foreach ($values as $key => $value) {
            $escapedValue = $this->escapeEnvValue($value);
            $pattern = "/^{$key}=.*$/m";
            $line = "{$key}={$escapedValue}";

            if (preg_match($pattern, $content) === 1) {
                $content = preg_replace($pattern, $line, $content) ?? $content;
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        File::put($path, $content);
    }

    private function escapeEnvValue(string $value): string
    {
        if ($value === '') {
            return '';
        }

        if (preg_match('/\s/', $value) === 1) {
            return '"'.addcslashes($value, '"').'"';
        }

        return $value;
    }
}
