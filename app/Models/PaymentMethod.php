<?php

namespace App\Models;

use App\ERP\Shared\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    use Auditable;

    public const SALES_CHANNEL_KEYS = [
        'retail',
        'grosir',
        'reseller',
        'marketplace',
        'online',
    ];

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function salesChannelAssignments(): HasMany
    {
        return $this->hasMany(PaymentMethodSalesChannel::class);
    }

    /**
     * @return list<string>
     */
    public function salesChannelsList(): array
    {
        if (! $this->relationLoaded('salesChannelAssignments')) {
            $this->load('salesChannelAssignments');
        }

        return $this->salesChannelAssignments
            ->pluck('sales_channel')
            ->values()
            ->all();
    }

    /**
     * @param  list<string>  $channels
     */
    public function syncSalesChannels(array $channels): void
    {
        $normalized = collect($channels)
            ->map(fn ($channel) => strtolower(trim((string) $channel)))
            ->filter()
            ->unique()
            ->intersect(self::SALES_CHANNEL_KEYS)
            ->values()
            ->all();

        $this->salesChannelAssignments()->delete();

        foreach ($normalized as $channel) {
            $this->salesChannelAssignments()->create([
                'sales_channel' => $channel,
            ]);
        }
    }

    public function isAvailableForSalesChannel(string $salesChannel): bool
    {
        $channels = $this->salesChannelsList();

        if ($channels === []) {
            return true;
        }

        return in_array(strtolower(trim($salesChannel)), $channels, true);
    }

    public function scopeAvailableForSalesChannel(Builder $query, string $salesChannel): Builder
    {
        $channel = strtolower(trim($salesChannel));

        return $query->where(function (Builder $builder) use ($channel): void {
            $builder
                ->whereDoesntHave('salesChannelAssignments')
                ->orWhereHas('salesChannelAssignments', fn (Builder $relation) => $relation->where('sales_channel', $channel));
        });
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public static function salesChannelOptions(): array
    {
        return [
            ['key' => 'retail', 'label' => 'Retail'],
            ['key' => 'grosir', 'label' => 'Grosir'],
            ['key' => 'reseller', 'label' => 'Reseller'],
            ['key' => 'marketplace', 'label' => 'Marketplace'],
            ['key' => 'online', 'label' => 'Online'],
        ];
    }

    public static function salesChannelLabel(string $key): string
    {
        return collect(self::salesChannelOptions())->firstWhere('key', $key)['label'] ?? strtoupper($key);
    }
}
