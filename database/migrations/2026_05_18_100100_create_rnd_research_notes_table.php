<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rnd_research_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rnd_project_id')->constrained('rnd_projects')->cascadeOnDelete();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['rnd_project_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rnd_research_notes');
    }
};
