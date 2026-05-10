<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->foreignId('label_smb_profile_id')
                ->nullable()
                ->after('label_smb_protocol')
                ->constrained('label_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_smb_profile_id');
        });
    }
};
