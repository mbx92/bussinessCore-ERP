<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_sale_additional_charges', function (Blueprint $table) {
            $table->string('kind', 24)->default('add_to_total')->after('amount');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->decimal('sales_channel_admin_fee', 18, 2)->default(0)->after('additional_fee');
        });

        DB::table('pos_sale_additional_charges')->update(['kind' => 'add_to_total']);
    }

    public function down(): void
    {
        Schema::table('pos_sale_additional_charges', function (Blueprint $table) {
            $table->dropColumn('kind');
        });

        Schema::table('pos_sales', function (Blueprint $table) {
            $table->dropColumn('sales_channel_admin_fee');
        });
    }
};
