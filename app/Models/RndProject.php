<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RndProject extends Model
{
    public const STATUSES = ['idea', 'research', 'development', 'done', 'cancelled'];

    protected $fillable = [
        'name',
        'description',
        'category',
        'status',
        'pic_user_id',
        'start_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
        ];
    }

    public function picUser()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function researchNotes()
    {
        return $this->hasMany(RndResearchNote::class)->latest();
    }

    public function budgetItems()
    {
        return $this->hasMany(RndBudgetItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function purchases()
    {
        return $this->hasMany(RndPurchase::class)->latest('purchase_date')->latest('id');
    }

    public function productOutputs()
    {
        return $this->hasMany(RndProductOutput::class)->latest();
    }

    public function scopeWithSummary(Builder $query): Builder
    {
        return $query
            ->with('picUser:id,name')
            ->withSum('budgetItems as estimated_budget_total', 'total_price')
            ->withSum('purchases as actual_spend_total', 'total_price')
            ->withSum([
                'purchases as alat_total' => fn ($purchaseQuery) => $purchaseQuery->where('category', 'alat'),
            ], 'total_price')
            ->withSum([
                'purchases as bahan_total' => fn ($purchaseQuery) => $purchaseQuery->where('category', 'bahan'),
            ], 'total_price')
            ->withSum('productOutputs as units_produced_total', 'units_produced');
    }

    public function getEstimatedBudgetTotalValueAttribute(): float
    {
        return (float) ($this->estimated_budget_total ?? $this->budgetItems()->sum('total_price'));
    }

    public function getActualSpendTotalValueAttribute(): float
    {
        return (float) ($this->actual_spend_total ?? $this->purchases()->sum('total_price'));
    }

    public function getAlatTotalValueAttribute(): float
    {
        return (float) ($this->alat_total ?? $this->purchases()->where('category', 'alat')->sum('total_price'));
    }

    public function getBahanTotalValueAttribute(): float
    {
        return (float) ($this->bahan_total ?? $this->purchases()->where('category', 'bahan')->sum('total_price'));
    }

    public function getUnitsProducedTotalValueAttribute(): float
    {
        return (float) ($this->units_produced_total ?? $this->productOutputs()->sum('units_produced'));
    }

    public function getVarianceValueAttribute(): float
    {
        return $this->estimated_budget_total_value - $this->actual_spend_total_value;
    }

    public function getHppPerUnitValueAttribute(): float
    {
        $units = $this->units_produced_total_value;

        if ($units <= 0) {
            return 0.0;
        }

        return $this->actual_spend_total_value / $units;
    }
}
