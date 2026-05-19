<?php

namespace App\Http\Controllers;

use App\Models\RndProject;
use App\Models\RndPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class RndPurchaseController extends Controller
{
    public function store(Request $request, RndProject $rndProject): RedirectResponse
    {
        $validated = $this->validated($request);

        $rndProject->purchases()->create([
            ...$this->normalizedPayload($validated),
            'receipt_path' => $request->file('receipt')?->store("rnd/projects/{$rndProject->id}/purchases", 'public'),
        ]);

        return back()->with('flash', ['type' => 'success', 'message' => 'Pembelian R&D berhasil dicatat.']);
    }

    public function update(Request $request, RndProject $rndProject, RndPurchase $rndPurchase): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndPurchase);
        $validated = $this->validated($request, $rndPurchase);
        $payload = $this->normalizedPayload($validated);

        if ($request->hasFile('receipt')) {
            if ($rndPurchase->receipt_path) {
                Storage::disk('public')->delete($rndPurchase->receipt_path);
            }

            $payload['receipt_path'] = $request->file('receipt')->store("rnd/projects/{$rndProject->id}/purchases", 'public');
        }

        $rndPurchase->update($payload);

        return back()->with('flash', ['type' => 'success', 'message' => 'Pembelian R&D berhasil diperbarui.']);
    }

    public function destroy(RndProject $rndProject, RndPurchase $rndPurchase): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndPurchase);

        if ($rndPurchase->receipt_path) {
            Storage::disk('public')->delete($rndPurchase->receipt_path);
        }

        $rndPurchase->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Pembelian R&D berhasil dihapus.']);
    }

    private function validated(Request $request, ?RndPurchase $purchase = null): array
    {
        return $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'supplier_id' => 'required|exists:vendors,id',
            'qty' => 'required|numeric|min:0.01',
            'unit_price' => 'required|numeric|min:0',
            'category' => ['required', Rule::in(['alat', 'bahan'])],
            'purchase_date' => 'required|date',
            'notes' => 'nullable|string',
            'receipt' => [$purchase ? 'nullable' : 'nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf'],
        ]);
    }

    private function normalizedPayload(array $validated): array
    {
        return [
            'master_product_id' => $validated['master_product_id'],
            'supplier_id' => $validated['supplier_id'],
            'qty' => $validated['qty'],
            'unit_price' => $validated['unit_price'],
            'total_price' => (float) $validated['qty'] * (float) $validated['unit_price'],
            'category' => $validated['category'],
            'purchase_date' => $validated['purchase_date'],
            'notes' => $validated['notes'] ?? null,
        ];
    }

    private function ensureOwnership(RndProject $project, RndPurchase $purchase): void
    {
        abort_unless($purchase->rnd_project_id === $project->id, 404);
    }
}
