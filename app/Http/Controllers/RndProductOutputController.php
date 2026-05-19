<?php

namespace App\Http\Controllers;

use App\Models\RndProductOutput;
use App\Models\RndProject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RndProductOutputController extends Controller
{
    public function store(Request $request, RndProject $rndProject): RedirectResponse
    {
        $rndProject->productOutputs()->create($this->validated($request));

        return back()->with('flash', ['type' => 'success', 'message' => 'Output produk berhasil ditambahkan.']);
    }

    public function update(Request $request, RndProject $rndProject, RndProductOutput $rndProductOutput): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndProductOutput);
        $rndProductOutput->update($this->validated($request));

        return back()->with('flash', ['type' => 'success', 'message' => 'Output produk berhasil diperbarui.']);
    }

    public function destroy(RndProject $rndProject, RndProductOutput $rndProductOutput): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndProductOutput);
        $rndProductOutput->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Output produk berhasil dihapus.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'units_produced' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }

    private function ensureOwnership(RndProject $project, RndProductOutput $output): void
    {
        abort_unless($output->rnd_project_id === $project->id, 404);
    }
}
