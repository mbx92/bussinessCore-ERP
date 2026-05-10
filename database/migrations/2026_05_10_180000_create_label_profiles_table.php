<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->decimal('width_mm', 8, 2);
            $table->decimal('height_mm', 8, 2);
            $table->unsignedSmallInteger('dpi')->default(203);
            $table->decimal('margin_left_mm', 8, 2)->default(0);
            $table->decimal('margin_top_mm', 8, 2)->default(0);
            $table->decimal('gap_mm', 8, 2)->default(2);
            $table->string('protocol', 8)->default('zpl');
            $table->timestamps();
        });

        $now = now();
        DB::table('label_profiles')->insert([
            [
                'name' => '50 × 30 mm (ZPL)',
                'width_mm' => 50,
                'height_mm' => 30,
                'dpi' => 203,
                'margin_left_mm' => 2,
                'margin_top_mm' => 2,
                'gap_mm' => 2,
                'protocol' => 'zpl',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '3 × 5 inch ≈ 76 × 127 mm (ZPL)',
                'width_mm' => 76.2,
                'height_mm' => 127,
                'dpi' => 203,
                'margin_left_mm' => 4,
                'margin_top_mm' => 4,
                'gap_mm' => 3,
                'protocol' => 'zpl',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => '3 × 5 inch ≈ 76 × 127 mm (EPL)',
                'width_mm' => 76.2,
                'height_mm' => 127,
                'dpi' => 203,
                'margin_left_mm' => 4,
                'margin_top_mm' => 4,
                'gap_mm' => 3,
                'protocol' => 'epl',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('label_profiles');
    }
};
