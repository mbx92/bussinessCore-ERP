<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rnd_project_id')->constrained('rnd_projects')->cascadeOnDelete();
            $table->foreignId('master_product_id')->constrained('master_products')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('vendors')->restrictOnDelete();
            $table->decimal('qty', 18, 2)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->string('category', 32);
            $table->date('purchase_date');
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rnd_project_id', 'purchase_date']);
            $table->index(['rnd_project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_purchases');
    }
};
