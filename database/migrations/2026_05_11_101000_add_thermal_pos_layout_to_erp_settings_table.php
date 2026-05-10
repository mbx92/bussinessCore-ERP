<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->decimal('thermal_pos_margin_left_mm', 5, 2)->default(2)->after('thermal_pos_footer_template');
            $table->string('thermal_pos_header_align', 12)->default('center')->after('thermal_pos_margin_left_mm');
            $table->string('thermal_pos_item_align', 12)->default('left')->after('thermal_pos_header_align');
            $table->string('thermal_pos_footer_align', 12)->default('right')->after('thermal_pos_item_align');
            $table->unsignedTinyInteger('thermal_pos_section_gap')->default(0)->after('thermal_pos_footer_align');
            $table->boolean('thermal_pos_header_emphasis')->default(true)->after('thermal_pos_section_gap');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'thermal_pos_margin_left_mm',
                'thermal_pos_header_align',
                'thermal_pos_item_align',
                'thermal_pos_footer_align',
                'thermal_pos_section_gap',
                'thermal_pos_header_emphasis',
            ]);
        });
    }
};
