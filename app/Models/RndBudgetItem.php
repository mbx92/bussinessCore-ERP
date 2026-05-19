<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RndBudgetItem extends Model
{
    protected $fillable = [
        'rnd_project_id',
        'name',
        'qty',
        'estimated_unit_price',
        'total_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'estimated_unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(RndProject::class, 'rnd_project_id');
    }
}
