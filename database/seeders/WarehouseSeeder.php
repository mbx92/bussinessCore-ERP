<?php

namespace Database\Seeders;

use App\ERP\Inventory\Models\Warehouse;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['code' => 'WH-MAIN', 'name' => 'Gudang Utama', 'address' => 'Gudang utama penyimpanan produk', 'is_active' => true],
            ['code' => 'WH-SVC', 'name' => 'Gudang Jasa', 'address' => 'Virtual warehouse untuk layanan jasa', 'is_active' => true],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::query()->firstOrCreate(
                ['code' => $warehouse['code']],
                $warehouse
            );
        }
    }
}
