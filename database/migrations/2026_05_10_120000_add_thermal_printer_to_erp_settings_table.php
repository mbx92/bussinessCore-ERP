<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->boolean('thermal_printer_enabled')->default(false)->after('app_logo_path');
            $table->string('thermal_printer_host', 64)->nullable()->after('thermal_printer_enabled');
            $table->unsignedSmallInteger('thermal_printer_port')->default(9100)->after('thermal_printer_host');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn(['thermal_printer_enabled', 'thermal_printer_host', 'thermal_printer_port']);
        });
    }
};
