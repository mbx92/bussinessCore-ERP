<?php

namespace App\Http\Controllers;

use App\Models\RndBudgetItem;
use App\Models\RndProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RndBudgetItemController extends Controller
{
    public function store(Request $request, RndProject $rndProject): RedirectResponse
    {
        $validated = $this->validated($request);

        $rndProject->budgetItems()->create([
            ...$validated,
            'total_price' => $this->total($validated),
            'sort_order' => (int) $rndProject->budgetItems()->max('sort_order') + 1,
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Item budget berhasil ditambahkan.']);
    }

    public function update(Request $request, RndProject $rndProject, RndBudgetItem $rndBudgetItem): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndBudgetItem);
        $validated = $this->validated($request);

        $rndBudgetItem->update([
            ...$validated,
            'total_price' => $this->total($validated),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Item budget berhasil diperbarui.']);
    }

    public function destroy(RndProject $rndProject, RndBudgetItem $rndBudgetItem): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndBudgetItem);
        $rndBudgetItem->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Item budget berhasil dihapus.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'qty' => 'required|numeric|min:0.01',
            'estimated_unit_price' => 'required|numeric|min:0',
        ]);
    }

    private function ensureOwnership(RndProject $project, RndBudgetItem $item): void
    {
        abort_unless($item->rnd_project_id === $project->id, 404);
    }

    private function total(array $validated): float
    {
        return (float) $validated['qty'] * (float) $validated['estimated_unit_price'];
    }
}
