<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_product_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rnd_project_id')->constrained('rnd_projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('units_produced', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['rnd_project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_product_outputs');
    }
};
