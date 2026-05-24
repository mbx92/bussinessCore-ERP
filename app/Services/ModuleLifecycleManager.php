<?php

namespace App\Services;

use App\Models\ErpSetting;
use App\Models\SystemModule;
use App\Support\EnabledModuleRegistry;
use App\Support\ModuleManifestReader;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ModuleLifecycleManager
{
    /**
     * @return list<string>
     */
    public function allModuleKeys(): array
    {
        return EnabledModuleRegistry::allModuleKeys();
    }

    public function syncDiscoveredModules(): void
    {
        if (! $this->hasSystemModulesTable()) {
            return;
        }

        $manifests = ModuleManifestReader::manifests();

        foreach (EnabledModuleRegistry::installableModules() as $moduleKey => $meta) {
            $manifest = $manifests[$moduleKey] ?? null;
            $dependencies = $manifest['dependencies'] ?? [];

            SystemModule::query()->updateOrCreate(
                ['key' => $moduleKey],
                [
                    'name' => $meta['label'],
                    'version' => is_string($manifest['version'] ?? null) ? $manifest['version'] : '0.1.0',
                    'status' => SystemModule::query()->where('key', $moduleKey)->value('status') ?? SystemModule::STATUS_DISCOVERED,
                    'is_core' => ! isset($manifests[$moduleKey]),
                    'dependencies' => is_array($dependencies) ? array_values(array_filter($dependencies, 'is_string')) : [],
                    'metadata' => $manifest,
                ],
            );
        }
    }

    /**
     * @param  list<string>  $moduleKeys
     */
    public function markInstalledAndEnabled(array $moduleKeys): void
    {
        $this->syncDiscoveredModules();

        if (! $this->hasSystemModulesTable()) {
            return;
        }

        $moduleKeys = array_values(array_unique(array_filter($moduleKeys, 'is_string')));
        if ($moduleKeys === []) {
            return;
        }

        $allowed = array_fill_keys($this->allModuleKeys(), true);
        foreach ($moduleKeys as $moduleKey) {
            if (! isset($allowed[$moduleKey])) {
                continue;
            }

            $record = SystemModule::query()->where('key', $moduleKey)->first();
            if (! $record) {
                continue;
            }

            $record->fill([
                'installed_version' => $record->version,
                'status' => SystemModule::STATUS_ENABLED,
                'installed_at' => $record->installed_at ?? now(),
                'enabled_at' => now(),
                'last_error' => null,
            ])->save();
        }
    }

    /**
     * @return list<string>
     */
    public function enabledModuleKeys(?ErpSetting $setting = null): array
    {
        $this->syncDiscoveredModules();

        if ($this->hasSystemModulesTable()) {
            $enabled = SystemModule::query()
                ->where('status', SystemModule::STATUS_ENABLED)
                ->orderBy('key')
                ->pluck('key')
                ->all();

            if ($enabled !== []) {
                return $enabled;
            }
        }

        $modules = $setting?->getAttribute('enabled_modules');
        if (! is_array($modules) || $modules === []) {
            return $this->allModuleKeys();
        }

        $allowed = array_fill_keys($this->allModuleKeys(), true);

        return array_values(array_filter(
            array_unique(array_map('strval', $modules)),
            static fn (string $key): bool => isset($allowed[$key]),
        ));
    }

    public function isEnabled(string $moduleKey, ?ErpSetting $setting = null): bool
    {
        return in_array($moduleKey, $this->enabledModuleKeys($setting), true);
    }

    private function hasSystemModulesTable(): bool
    {
        try {
            return Schema::hasTable('system_modules');
        } catch (Throwable) {
            return false;
        }
    }
}
