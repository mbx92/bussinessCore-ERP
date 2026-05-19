<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProjectType extends Model
{
    protected $fillable = [
        'key',
        'label',
        'description',
        'supports_budget_items',
        'supports_project_board',
        'is_active',
        'is_default',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'supports_budget_items' => 'boolean',
            'supports_project_board' => 'boolean',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderBy('label');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function defaultKey(?string $fallback = null): string
    {
        return static::query()
            ->active()
            ->ordered()
            ->value('key')
            ?? $fallback
            ?? 'system_website_development';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public static function activeOptions(): Collection
    {
        return static::query()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (self $type): array => [
                'id' => $type->id,
                'key' => $type->key,
                'label' => $type->label,
                'description' => $type->description,
                'supports_budget_items' => $type->supports_budget_items,
                'supports_project_board' => $type->supports_project_board,
                'is_default' => $type->is_default,
            ])
            ->values();
    }
}
