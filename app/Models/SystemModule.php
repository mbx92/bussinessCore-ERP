<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemModule extends Model
{
    public const STATUS_DISCOVERED = 'discovered';
    public const STATUS_INSTALLED = 'installed';
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_DISABLED = 'disabled';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'key',
        'name',
        'version',
        'installed_version',
        'status',
        'is_core',
        'dependencies',
        'metadata',
        'installed_at',
        'enabled_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'is_core' => 'boolean',
            'dependencies' => 'array',
            'metadata' => 'array',
            'installed_at' => 'datetime',
            'enabled_at' => 'datetime',
        ];
    }
}
