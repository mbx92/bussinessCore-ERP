<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RndProductOutput extends Model
{
    protected $fillable = [
        'rnd_project_id',
        'name',
        'description',
        'units_produced',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'units_produced' => 'decimal:2',
        ];
    }

    public function project()
    {
        return $this->belongsTo(RndProject::class, 'rnd_project_id');
    }
}
