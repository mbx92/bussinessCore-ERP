<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_types', function (Blueprint $table): void {
            $table->string('badge_color', 20)->nullable()->after('label');
        });

        DB::table('project_types')
            ->where('key', 'system_website_development')
            ->update(['badge_color' => 'accent']);

        DB::table('project_types')
            ->where('key', 'cctv_installation')
            ->update(['badge_color' => 'warning']);
    }

    public function down(): void
    {
        Schema::table('project_types', function (Blueprint $table): void {
            $table->dropColumn('badge_color');
        });
    }
};
