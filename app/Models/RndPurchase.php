<?php

namespace App\Models;

use App\ERP\Purchasing\Models\Vendor;
use Illuminate\Database\Eloquent\Model;

class RndPurchase extends Model
{
    protected $fillable = [
        'rnd_project_id',
        'master_product_id',
        'supplier_id',
        'qty',
        'unit_price',
        'total_price',
        'category',
        'purchase_date',
        'receipt_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'purchase_date' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(RndProject::class, 'rnd_project_id');
    }

    public function product()
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Vendor::class, 'supplier_id');
    }
}
