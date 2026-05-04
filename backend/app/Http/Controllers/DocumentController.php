<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    public function index(Request $request, string $employmentId): JsonResponse
    {
        $employment = Employment::findOrFail($employmentId);

        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId && $employment->entity_id !== $activeEntityId) {
            return $this->error('Akses ditolak.', 403);
        }

        $docs = Document::where('employment_id', $employmentId)
            ->with('uploadedBy:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($doc) => [
                'id'          => $doc->id,
                'type'        => $doc->type,
                'label'       => $doc->label,
                'expires_at'  => $doc->expires_at?->toDateString(),
                'uploaded_by' => $doc->uploadedBy?->only(['id', 'name']),
                'created_at'  => $doc->created_at,
            ]);

        return $this->success($docs);
    }

    public function store(Request $request, string $employmentId): JsonResponse
    {
        $employment = Employment::findOrFail($employmentId);

        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId && $employment->entity_id !== $activeEntityId) {
            return $this->error('Akses ditolak.', 403);
        }

        $validated = $request->validate([
            'type'       => 'required|in:KTP,NPWP,IJAZAH,SK_PENGANGKATAN,KONTRAK,SERTIFIKAT,LAINNYA',
            'label'      => 'nullable|string|max:255',
            'expires_at' => 'nullable|date',
            'file'       => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $file      = $request->file('file');
        $ext       = $file->getClientOriginalExtension();
        $storedId  = (string) Str::uuid();
        $path      = $file->storeAs("documents/{$employmentId}", "{$storedId}.{$ext}", 'local');

        $label = $validated['label'] ?? $file->getClientOriginalName();

        $doc = Document::create([
            'employment_id' => $employmentId,
            'type'          => $validated['type'],
            'label'         => $label,
            'file_url'      => $path,
            'expires_at'    => $validated['expires_at'] ?? null,
            'uploaded_by'   => $request->user()->id,
        ]);

        return $this->success([
            'id'         => $doc->id,
            'type'       => $doc->type,
            'label'      => $doc->label,
            'expires_at' => $doc->expires_at?->toDateString(),
            'created_at' => $doc->created_at,
        ], 'Dokumen berhasil diupload.', 201);
    }

    public function download(Request $request, string $employmentId, string $documentId): StreamedResponse|JsonResponse
    {
        $employment = Employment::findOrFail($employmentId);
        $doc        = Document::where('employment_id', $employmentId)->findOrFail($documentId);

        $user    = $request->user();
        $isOwner = $employment->user_id === $user->id;
        $isAdmin = $user->hasRole('super_admin') || $user->hasRole('holding_admin')
                || $user->hasRole('entity_admin') || $user->hasRole('manager');

        if (!$isOwner && !$isAdmin) {
            return $this->error('Akses ditolak.', 403);
        }

        $path = $doc->file_url;

        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return $this->error('Path tidak valid.', 400);
        }

        if (!Storage::disk('local')->exists($path)) {
            return $this->error('File tidak ditemukan.', 404);
        }

        return Storage::disk('local')->download($path, $doc->label ?? basename($path));
    }

    public function destroy(Request $request, string $employmentId, string $documentId): JsonResponse
    {
        $employment = Employment::findOrFail($employmentId);

        $activeEntityId = $request->attributes->get('active_entity_id');
        if ($activeEntityId && $employment->entity_id !== $activeEntityId) {
            return $this->error('Akses ditolak.', 403);
        }

        $doc = Document::where('employment_id', $employmentId)->findOrFail($documentId);

        Storage::disk('local')->delete($doc->file_url);
        $doc->delete();

        return $this->success(null, 'Dokumen berhasil dihapus.');
    }
}
