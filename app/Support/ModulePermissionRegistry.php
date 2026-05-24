<?php

namespace App\Support;

final class ModulePermissionRegistry
{
    /**
     * @return list<array{name: string, label: string, group: string}>
     */
    public static function definitions(): array
    {
        $definitions = [];

        foreach (config('erp_menu_permissions', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $definition = self::normalizeDefinition($row);
            if ($definition !== null) {
                $definitions[$definition['name']] = $definition;
            }
        }

        foreach (ModuleManifestReader::manifests() as $manifest) {
            $configPath = ($manifest['source_path'] ?? null).DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'permissions.php';
            if (! is_string($configPath) || ! is_file($configPath)) {
                continue;
            }

            $rows = require $configPath;
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $definition = self::normalizeDefinition($row);
                if ($definition !== null) {
                    $definitions[$definition['name']] = $definition;
                }
            }
        }

        ksort($definitions);

        return array_values($definitions);
    }

    /**
     * @return list<array{name: string, label: string, group: string}>
     */
    public static function menuDefinitions(): array
    {
        return array_values(array_filter(
            self::definitions(),
            static fn (array $definition): bool => str_starts_with($definition['name'], 'menu.'),
        ));
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        $names = [];

        foreach (self::definitions() as $definition) {
            $names[$definition['name']] = true;
        }

        foreach (ModuleManifestReader::manifests() as $manifest) {
            $permissions = $manifest['permissions'] ?? [];
            if (! is_array($permissions)) {
                continue;
            }

            foreach ($permissions as $permissionName) {
                if (is_string($permissionName) && $permissionName !== '') {
                    $names[$permissionName] = true;
                }
            }
        }

        ksort($names);

        return array_keys($names);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{name: string, label: string, group: string}|null
     */
    private static function normalizeDefinition(array $row): ?array
    {
        $name = is_string($row['name'] ?? null) ? trim($row['name']) : '';
        if ($name === '') {
            return null;
        }

        $label = is_string($row['label'] ?? null) && trim($row['label']) !== ''
            ? trim($row['label'])
            : $name;
        $group = is_string($row['group'] ?? null) && trim($row['group']) !== ''
            ? trim($row['group'])
            : 'Modules';

        return [
            'name' => $name,
            'label' => $label,
            'group' => $group,
        ];
    }
}
