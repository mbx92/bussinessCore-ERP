<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->text('thermal_pos_header_template')->nullable()->after('thermal_paper_width');
            $table->text('thermal_pos_item_line_template')->nullable()->after('thermal_pos_header_template');
            $table->text('thermal_pos_footer_template')->nullable()->after('thermal_pos_item_line_template');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'thermal_pos_header_template',
                'thermal_pos_item_line_template',
                'thermal_pos_footer_template',
            ]);
        });
    }
};
