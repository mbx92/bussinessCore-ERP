<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rnd_project_id')->constrained('rnd_projects')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('qty', 18, 2)->default(1);
            $table->decimal('estimated_unit_price', 18, 2)->default(0);
            $table->decimal('total_price', 18, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['rnd_project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_budget_items');
    }
};
