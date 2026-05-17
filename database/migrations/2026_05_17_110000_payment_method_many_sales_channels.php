<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_method_sales_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->string('sales_channel', 50);
            $table->timestamps();

            $table->unique(['payment_method_id', 'sales_channel'], 'payment_method_sales_channel_unique');
        });

        if (Schema::hasColumn('payment_methods', 'sales_channel')) {
            foreach (DB::table('payment_methods')->whereNotNull('sales_channel')->orderBy('id')->get() as $row) {
                DB::table('payment_method_sales_channels')->insert([
                    'payment_method_id' => $row->id,
                    'sales_channel' => $row->sales_channel,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('sales_channel');
            });
        }
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('sales_channel', 50)->default('retail')->after('description');
        });

        DB::table('payment_method_sales_channels')
            ->orderBy('payment_method_id')
            ->orderBy('id')
            ->get()
            ->groupBy('payment_method_id')
            ->each(function ($rows, $paymentMethodId): void {
                $first = $rows->first();
                if ($first) {
                    DB::table('payment_methods')
                        ->where('id', $paymentMethodId)
                        ->update(['sales_channel' => $first->sales_channel]);
                }
            });

        Schema::dropIfExists('payment_method_sales_channels');
    }
};
