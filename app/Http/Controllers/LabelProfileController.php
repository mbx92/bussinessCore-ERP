<?php

namespace App\Http\Controllers;

use App\Models\LabelProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LabelProfileController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('ERP/Admin/LabelProfiles', [
            'profiles' => LabelProfile::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'width_mm' => 'required|numeric|min:10|max:500',
            'height_mm' => 'required|numeric|min:10|max:500',
            'dpi' => 'required|integer|in:203,300,600',
            'margin_left_mm' => 'required|numeric|min:0|max:50',
            'margin_top_mm' => 'required|numeric|min:0|max:50',
            'gap_mm' => 'required|numeric|min:0|max:30',
            'protocol' => 'required|string|in:zpl,epl',
        ]);

        LabelProfile::query()->create($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Profil label berhasil ditambahkan.']);
    }

    public function update(Request $request, LabelProfile $labelProfile): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'width_mm' => 'required|numeric|min:10|max:500',
            'height_mm' => 'required|numeric|min:10|max:500',
            'dpi' => 'required|integer|in:203,300,600',
            'margin_left_mm' => 'required|numeric|min:0|max:50',
            'margin_top_mm' => 'required|numeric|min:0|max:50',
            'gap_mm' => 'required|numeric|min:0|max:30',
            'protocol' => 'required|string|in:zpl,epl',
        ]);

        $labelProfile->update($validated);

        return back()->with('flash', ['type' => 'success', 'message' => 'Profil label berhasil diperbarui.']);
    }

    public function destroy(LabelProfile $labelProfile): RedirectResponse
    {
        $labelProfile->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Profil label berhasil dihapus.']);
    }
}
