<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class ServerMetricsService
{
    /**
     * @return array<string, mixed>
     */
    public function collect(): array
    {
        $connName = (string) config('database.default');
        $config = config("database.connections.{$connName}", []);
        $driver = (string) ($config['driver'] ?? 'unknown');

        return [
            'collected_at' => now()->toIso8601String(),
            'app' => [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
            ],
            'database' => $this->databaseMetrics($connName, $driver, $config),
            'network' => $this->networkMetrics(),
        ];
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function databaseMetrics(string $connName, string $driver, array $config): array
    {
        $host = isset($config['host']) ? (string) $config['host'] : null;
        $port = isset($config['port']) ? (int) $config['port'] : null;

        $queryMs = null;
        $queryError = null;
        try {
            $t0 = microtime(true);
            match ($driver) {
                'pgsql' => DB::connection()->select('SELECT 1 AS ok'),
                'mysql', 'mariadb' => DB::connection()->select('SELECT 1 AS ok'),
                'sqlite' => DB::connection()->select('SELECT 1 AS ok'),
                default => DB::connection()->select('SELECT 1 AS ok'),
            };
            $queryMs = round((microtime(true) - $t0) * 1000, 3);
        } catch (Throwable $e) {
            $queryError = $e->getMessage();
        }

        $tcpMs = null;
        $tcpError = null;
        if ($driver !== 'sqlite' && $host !== null && $port !== null && $port > 0) {
            try {
                $tcpMs = $this->tcpConnectMs($host, $port);
            } catch (Throwable $e) {
                $tcpError = $e->getMessage();
            }
        }

        $sizeBytes = null;
        $sizeHuman = null;
        $sizeError = null;
        try {
            [$sizeBytes, $sizeHuman] = $this->databaseSize($driver);
        } catch (Throwable $e) {
            $sizeError = $e->getMessage();
        }

        return [
            'connection' => $connName,
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'database' => isset($config['database']) ? (string) $config['database'] : null,
            'query_latency_ms' => $queryMs,
            'query_error' => $queryError,
            'tcp_connect_ms' => $tcpMs,
            'tcp_error' => $tcpError,
            'size_bytes' => $sizeBytes,
            'size_human' => $sizeHuman,
            'size_error' => $sizeError,
        ];
    }

    /**
     * @return array{0: ?int, 1: ?string}
     */
    private function databaseSize(string $driver): array
    {
        return match ($driver) {
            'pgsql' => $this->pgsqlDatabaseSize(),
            'mysql', 'mariadb' => $this->mysqlDatabaseSize(),
            'sqlite' => $this->sqliteDatabaseSize(),
            default => [null, null],
        };
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function pgsqlDatabaseSize(): array
    {
        $row = DB::connection()->selectOne('SELECT pg_database_size(current_database()) AS bytes');
        $bytes = (int) ($row->bytes ?? 0);
        $human = $this->formatBytes($bytes);

        return [$bytes, $human];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function mysqlDatabaseSize(): array
    {
        $row = DB::connection()->selectOne(
            'SELECT COALESCE(SUM(data_length + index_length), 0) AS bytes
             FROM information_schema.tables
             WHERE table_schema = DATABASE()'
        );
        $bytes = (int) ($row->bytes ?? 0);
        $human = $this->formatBytes($bytes);

        return [$bytes, $human];
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function sqliteDatabaseSize(): array
    {
        $path = (string) config('database.connections.sqlite.database');
        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('Basis data SQLite in-memory atau path kosong.');
        }
        if (! str_contains($path, DIRECTORY_SEPARATOR) && ! str_starts_with($path, '/')) {
            $path = database_path($path);
        }
        if (! is_file($path)) {
            throw new RuntimeException('File SQLite tidak ditemukan.');
        }
        $bytes = (int) filesize($path);
        $human = $this->formatBytes($bytes);

        return [$bytes, $human];
    }

    private function tcpConnectMs(string $host, int $port, float $timeoutSec = 2.5): ?float
    {
        $target = strtolower($host) === 'localhost' ? '127.0.0.1' : $host;
        $errno = 0;
        $errstr = '';
        $t0 = microtime(true);
        $fp = @fsockopen($target, $port, $errno, $errstr, $timeoutSec);
        $ms = round((microtime(true) - $t0) * 1000, 3);
        if (is_resource($fp)) {
            fclose($fp);
        }
        if ($fp === false) {
            throw new RuntimeException($errstr !== '' ? "TCP {$target}:{$port} — {$errstr} ({$errno})" : "TCP {$target}:{$port} gagal.");
        }

        return $ms;
    }

    /**
     * @return array<string, mixed>
     */
    private function networkMetrics(): array
    {
        $url = 'https://one.one.one.one/cdn-cgi/trace';
        $ms = null;
        $error = null;
        try {
            $t0 = microtime(true);
            Http::timeout(5)
                ->connectTimeout(3)
                ->withHeaders(['User-Agent' => 'PaymentSystemOCN-ServerMetrics/1.0'])
                ->get($url);
            $ms = round((microtime(true) - $t0) * 1000, 3);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        return [
            'outbound_http_ms' => $ms,
            'outbound_target' => $url,
            'outbound_error' => $error,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 0) {
            $bytes = 0;
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        $v = (float) $bytes;
        while ($v >= 1024 && $i < count($units) - 1) {
            $v /= 1024;
            $i++;
        }

        return round($v, $i === 0 ? 0 : 2).' '.$units[$i];
    }
}
