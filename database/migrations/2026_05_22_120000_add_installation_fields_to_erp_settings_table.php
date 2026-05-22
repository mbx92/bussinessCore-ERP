<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('erp_settings', 'enabled_modules')) {
                $table->json('enabled_modules')->nullable()->after('module_menu_layout');
            }

            if (! Schema::hasColumn('erp_settings', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('enabled_modules');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            if (Schema::hasColumn('erp_settings', 'installed_at')) {
                $table->dropColumn('installed_at');
            }

            if (Schema::hasColumn('erp_settings', 'enabled_modules')) {
                $table->dropColumn('enabled_modules');
            }
        });
    }
};
