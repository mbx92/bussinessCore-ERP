<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->boolean('label_lan_enabled')->default(false)->after('label_smb_profile_id');
            $table->string('label_lan_host', 64)->nullable()->after('label_lan_enabled');
            $table->unsignedSmallInteger('label_lan_port')->default(9100)->after('label_lan_host');
            $table->foreignId('label_lan_profile_id')
                ->nullable()
                ->after('label_lan_port')
                ->constrained('label_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('label_lan_profile_id');
            $table->dropColumn(['label_lan_enabled', 'label_lan_host', 'label_lan_port']);
        });
    }
};
