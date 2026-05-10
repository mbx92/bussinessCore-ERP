<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->boolean('maintenance_global_enabled')->default(false)->after('label_lan_profile_id');
            $table->text('maintenance_global_message')->nullable()->after('maintenance_global_enabled');
            $table->json('maintenance_modules')->nullable()->after('maintenance_global_message');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn([
                'maintenance_global_enabled',
                'maintenance_global_message',
                'maintenance_modules',
            ]);
        });
    }
};
