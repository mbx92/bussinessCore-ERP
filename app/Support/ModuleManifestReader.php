<?php

namespace App\Support;

final class ModuleManifestReader
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function manifests(): array
    {
        $moduleRoot = base_path('modules');
        if (! is_dir($moduleRoot)) {
            return [];
        }

        $manifests = [];
        $directories = glob($moduleRoot.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR) ?: [];

        foreach ($directories as $directory) {
            $manifestPath = $directory.DIRECTORY_SEPARATOR.'module.json';
            if (! is_file($manifestPath)) {
                continue;
            }

            $decoded = json_decode((string) file_get_contents($manifestPath), true);
            if (! is_array($decoded)) {
                continue;
            }

            $key = self::stringOrNull($decoded['key'] ?? null);
            if ($key === null || $key === '') {
                continue;
            }

            $decoded['key'] = $key;
            $decoded['source_path'] = $directory;
            $manifests[$key] = $decoded;
        }

        ksort($manifests);

        return $manifests;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function manifestFor(string $moduleKey): ?array
    {
        return self::manifests()[$moduleKey] ?? null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? trim($value) : null;
    }
}
