<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('label', 150);
            $table->text('description')->nullable();
            $table->boolean('supports_budget_items')->default(false);
            $table->boolean('supports_project_board')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('project_types')->insert([
            [
                'key' => 'system_website_development',
                'label' => 'System/Website Development',
                'description' => 'Project software, website, dan implementasi sistem yang memakai board task/kanban.',
                'supports_budget_items' => false,
                'supports_project_board' => true,
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cctv_installation',
                'label' => 'CCTV Installation',
                'description' => 'Project instalasi CCTV dengan rincian item budget/material dan lifecycle lapangan.',
                'supports_budget_items' => true,
                'supports_project_board' => false,
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $legacyKeys = collect(DB::table('projects')->select('project_type')->whereNotNull('project_type')->pluck('project_type'))
            ->merge(DB::table('project_budgets')->select('project_type')->whereNotNull('project_type')->pluck('project_type'))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim((string) $value))
            ->unique()
            ->values();

        foreach ($legacyKeys as $index => $key) {
            DB::table('project_types')->updateOrInsert(
                ['key' => $key],
                [
                    'label' => str($key)->replace('_', ' ')->title()->toString(),
                    'description' => null,
                    'supports_budget_items' => $key === 'cctv_installation',
                    'supports_project_board' => $key === 'system_website_development',
                    'is_active' => true,
                    'is_default' => false,
                    'sort_order' => 100 + $index,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_types');
    }
};
