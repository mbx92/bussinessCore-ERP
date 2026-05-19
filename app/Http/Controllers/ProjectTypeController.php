<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectTypeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Projects/Types', [
            'types' => ProjectType::query()
                ->ordered()
                ->get()
                ->map(fn (ProjectType $type): array => [
                    'id' => $type->id,
                    'key' => $type->key,
                    'label' => $type->label,
                    'badge_color' => $type->badge_color,
                    'description' => $type->description,
                    'supports_budget_items' => $type->supports_budget_items,
                    'supports_project_board' => $type->supports_project_board,
                    'is_active' => $type->is_active,
                    'is_default' => $type->is_default,
                    'sort_order' => $type->sort_order,
                    'project_count' => Project::query()->where('project_type', $type->key)->count(),
                    'budget_count' => ProjectBudget::query()->where('project_type', $type->key)->count(),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'alpha_dash', 'unique:project_types,key'],
            'label' => ['required', 'string', 'max:150'],
            'badge_color' => ['nullable', 'in:ghost,primary,secondary,accent,info,success,warning,error'],
            'description' => ['nullable', 'string'],
            'supports_budget_items' => ['nullable', 'boolean'],
            'supports_project_board' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated): void {
            $type = ProjectType::query()->create([
                'key' => $validated['key'],
                'label' => $validated['label'],
                'badge_color' => $validated['badge_color'] ?? null,
                'description' => $validated['description'] ?? null,
                'supports_budget_items' => (bool) ($validated['supports_budget_items'] ?? false),
                'supports_project_board' => (bool) ($validated['supports_project_board'] ?? false),
                'is_active' => (bool) ($validated['is_active'] ?? true),
                'is_default' => (bool) ($validated['is_default'] ?? false),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);

            $this->syncDefaultState($type, $type->is_default);
        });

        return back()->with('flash', ['type' => 'success', 'message' => 'Tipe project berhasil ditambahkan.']);
    }

    public function update(Request $request, ProjectType $projectType)
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:150'],
            'badge_color' => ['nullable', 'in:ghost,primary,secondary,accent,info,success,warning,error'],
            'description' => ['nullable', 'string'],
            'supports_budget_items' => ['nullable', 'boolean'],
            'supports_project_board' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($projectType, $validated): void {
            $projectType->update([
                'label' => $validated['label'],
                'badge_color' => $validated['badge_color'] ?? null,
                'description' => $validated['description'] ?? null,
                'supports_budget_items' => (bool) ($validated['supports_budget_items'] ?? false),
                'supports_project_board' => (bool) ($validated['supports_project_board'] ?? false),
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'is_default' => (bool) ($validated['is_default'] ?? false),
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
            ]);

            $this->syncDefaultState($projectType, $projectType->is_default);
        });

        return back()->with('flash', ['type' => 'success', 'message' => 'Tipe project berhasil diperbarui.']);
    }

    private function syncDefaultState(ProjectType $projectType, bool $requestedDefault): void
    {
        if ($requestedDefault) {
            ProjectType::query()
                ->whereKeyNot($projectType->id)
                ->update(['is_default' => false]);

            if (! $projectType->is_active) {
                $projectType->forceFill(['is_active' => true])->save();
            }

            return;
        }

        if (! ProjectType::query()->where('is_default', true)->where('is_active', true)->exists()) {
            $projectType->forceFill([
                'is_default' => true,
                'is_active' => true,
            ])->save();
        }
    }
}
