<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->boolean('label_smb_enabled')->default(false)->after('thermal_paper_width');
            $table->string('label_smb_unc', 260)->nullable()->after('label_smb_enabled');
            $table->string('label_smb_protocol', 8)->default('zpl')->after('label_smb_unc');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn(['label_smb_enabled', 'label_smb_unc', 'label_smb_protocol']);
        });
    }
};
