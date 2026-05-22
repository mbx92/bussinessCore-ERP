<?php

namespace App\Models;

use App\Support\EnabledModuleRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpSetting extends Model
{
    public const MODULE_MENU_LAYOUT_GRID = 'grid';
    public const MODULE_MENU_LAYOUT_LIST = 'list';

    protected $fillable = [
        'app_name',
        'app_tagline',
        'app_logo_path',
        'module_menu_layout',
        'enabled_modules',
        'installed_at',
        'thermal_printer_enabled',
        'thermal_printer_host',
        'thermal_printer_port',
        'thermal_paper_width',
        'thermal_pos_header_template',
        'thermal_pos_item_line_template',
        'thermal_pos_footer_template',
        'thermal_pos_margin_left_mm',
        'thermal_pos_header_align',
        'thermal_pos_item_align',
        'thermal_pos_footer_align',
        'thermal_pos_section_gap',
        'thermal_pos_header_emphasis',
        'label_smb_enabled',
        'label_smb_unc',
        'label_smb_protocol',
        'label_smb_profile_id',
        'label_lan_enabled',
        'label_lan_host',
        'label_lan_port',
        'label_lan_profile_id',
        'maintenance_global_enabled',
        'maintenance_global_message',
        'maintenance_modules',
    ];

    protected function casts(): array
    {
        return [
            'thermal_printer_enabled' => 'boolean',
            'thermal_printer_port' => 'integer',
            'thermal_pos_margin_left_mm' => 'decimal:2',
            'thermal_pos_section_gap' => 'integer',
            'thermal_pos_header_emphasis' => 'boolean',
            'enabled_modules' => 'array',
            'installed_at' => 'datetime',
            'label_smb_enabled' => 'boolean',
            'label_smb_profile_id' => 'integer',
            'label_lan_enabled' => 'boolean',
            'label_lan_port' => 'integer',
            'label_lan_profile_id' => 'integer',
            'maintenance_global_enabled' => 'boolean',
            'maintenance_modules' => 'array',
        ];
    }

    public function labelProfile(): BelongsTo
    {
        return $this->belongsTo(LabelProfile::class, 'label_smb_profile_id');
    }

    public function labelLanProfile(): BelongsTo
    {
        return $this->belongsTo(LabelProfile::class, 'label_lan_profile_id');
    }

    /**
     * @return list<string>
     */
    public static function moduleMenuLayoutOptions(): array
    {
        return [
            self::MODULE_MENU_LAYOUT_GRID,
            self::MODULE_MENU_LAYOUT_LIST,
        ];
    }

    public function resolvedModuleMenuLayout(): string
    {
        return in_array($this->module_menu_layout, self::moduleMenuLayoutOptions(), true)
            ? $this->module_menu_layout
            : self::MODULE_MENU_LAYOUT_GRID;
    }

    /**
     * Profil ukuran label untuk cetak TSPL lewat LAN: khusus LAN jika diisi, jika tidak memakai profil SMB.
     */
    public function resolveLabelProfileForLanPrinting(): ?LabelProfile
    {
        if ($this->label_lan_profile_id) {
            return $this->labelLanProfile ?? LabelProfile::query()->find($this->label_lan_profile_id);
        }

        return $this->labelProfile;
    }

    /**
     * @return array<string, array{enabled: bool, message: string|null}>
     */
    public static function defaultMaintenanceModules(): array
    {
        $keys = ['accounting', 'sales', 'purchasing', 'inventory', 'projects', 'hr', 'reporting', 'administration'];

        return array_fill_keys($keys, ['enabled' => false, 'message' => null]);
    }

    /**
     * @return array<string, array{enabled: bool, message: string|null}>
     */
    public function mergedMaintenanceModules(): array
    {
        $base = self::defaultMaintenanceModules();
        $saved = $this->maintenance_modules ?? [];
        if (! is_array($saved)) {
            return $base;
        }
        foreach ($base as $key => $defaults) {
            if (! isset($saved[$key]) || ! is_array($saved[$key])) {
                continue;
            }
            $base[$key]['enabled'] = self::coerceMaintenanceEnabled($saved[$key]['enabled'] ?? false);
            $msg = $saved[$key]['message'] ?? null;
            $base[$key]['message'] = is_string($msg) && trim($msg) !== '' ? trim($msg) : null;
        }

        return $base;
    }

    public static function coerceMaintenanceEnabled(mixed $v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (int) $v !== 0;
        }
        if (is_string($v)) {
            $t = strtolower(trim($v));
            if (in_array($t, ['', '0', 'false', 'no', 'off'], true)) {
                return false;
            }

            return in_array($t, ['1', 'true', 'yes', 'on'], true);
        }

        return false;
    }

    /**
     * @return list<string>
     */
    public function enabledModuleKeys(): array
    {
        $modules = $this->enabled_modules;

        if (! is_array($modules) || $modules === []) {
            return EnabledModuleRegistry::allModuleKeys();
        }

        $allowed = array_fill_keys(EnabledModuleRegistry::allModuleKeys(), true);

        return array_values(array_filter(
            array_unique(array_map('strval', $modules)),
            static fn (string $key): bool => isset($allowed[$key]),
        ));
    }

    public function isModuleEnabled(string $moduleKey): bool
    {
        return in_array($moduleKey, $this->enabledModuleKeys(), true);
    }

    public function getIsInstalledAttribute(): bool
    {
        return $this->installed_at !== null;
    }
}
