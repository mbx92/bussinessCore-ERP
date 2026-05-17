<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->boolean('is_cash_bank')->default(false)->after('is_active');
        });

        DB::table('accounts')
            ->where('type', 'asset')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('name', 'like', '%Kas%')
                    ->orWhere('name', 'like', '%Bank%')
                    ->orWhere('name', 'like', '%Cash%')
                    ->orWhere('name', 'like', '%Giro%');
            })
            ->where('name', 'not like', '%Piutang%')
            ->where('name', 'not like', '%Persediaan%')
            ->where('name', 'not like', '%Peralatan%')
            ->where('name', 'not like', '%Kendaraan%')
            ->where('name', 'not like', '%Akumulasi%')
            ->where('name', 'not like', '%Sewa Dibayar%')
            ->where('name', 'not like', '%Asuransi Dibayar%')
            ->update(['is_cash_bank' => true]);
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table): void {
            $table->dropColumn('is_cash_bank');
        });
    }
};
