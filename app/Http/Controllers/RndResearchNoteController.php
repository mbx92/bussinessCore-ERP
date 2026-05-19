<?php

namespace App\Http\Controllers;

use App\Models\RndProject;
use App\Models\RndResearchNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RndResearchNoteController extends Controller
{
    public function store(Request $request, RndProject $rndProject): RedirectResponse
    {
        $validated = $this->validated($request);

        $note = $rndProject->researchNotes()->create([
            'title' => $validated['title'],
            'content' => $this->sanitizeHtml($validated['content'] ?? ''),
            'created_by' => $request->user()->id,
        ]);

        $this->storeAttachments($request, $rndProject, $note);

        return back()->with('flash', ['type' => 'success', 'message' => 'Catatan riset berhasil disimpan.']);
    }

    public function update(Request $request, RndProject $rndProject, RndResearchNote $rndResearchNote): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndResearchNote);
        $validated = $this->validated($request);

        $rndResearchNote->update([
            'title' => $validated['title'],
            'content' => $this->sanitizeHtml($validated['content'] ?? ''),
        ]);

        $this->storeAttachments($request, $rndProject, $rndResearchNote);

        return back()->with('flash', ['type' => 'success', 'message' => 'Catatan riset berhasil diperbarui.']);
    }

    public function destroy(RndProject $rndProject, RndResearchNote $rndResearchNote): RedirectResponse
    {
        $this->ensureOwnership($rndProject, $rndResearchNote);

        foreach ($rndResearchNote->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }

        $rndResearchNote->delete();

        return back()->with('flash', ['type' => 'success', 'message' => 'Catatan riset berhasil dihapus.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'attachments' => 'nullable|array',
            'attachments.*' => 'file|max:10240|mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt',
        ]);
    }

    private function ensureOwnership(RndProject $project, RndResearchNote $note): void
    {
        abort_unless($note->rnd_project_id === $project->id, 404);
    }

    private function storeAttachments(Request $request, RndProject $project, RndResearchNote $note): void
    {
        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("rnd/projects/{$project->id}/notes", 'public');

            $note->attachments()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function sanitizeHtml(?string $value): string
    {
        $html = trim((string) $value);
        if ($html === '') {
            return '';
        }

        $html = preg_replace('#<(script|style)[^>]*>.*?</\\1>#si', '', $html) ?? $html;
        $html = preg_replace('/\\son[a-z]+=\"[^\"]*\"/i', '', $html) ?? $html;
        $html = preg_replace("/\\son[a-z]+='[^']*'/i", '', $html) ?? $html;
        $html = preg_replace('/javascript:/i', '', $html) ?? $html;

        return strip_tags($html, '<p><br><strong><em><u><ul><ol><li><a><blockquote><code><pre><h1><h2><h3>');
    }
}
