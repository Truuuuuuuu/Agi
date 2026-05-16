<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileController extends Controller
{
    // ─── List files for the authenticated user ─────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $files = $request->user()
            ->files()
            ->latest()
            ->get()
            ->map(fn (File $f) => [
                'id' => $f->id,
                'original_name' => $f->original_name,
                'mime_type' => $f->mime_type,
                'size' => $f->formatted_size,
                'sha256_hash' => $f->sha256_hash,
                'iv' => $f->iv,
                'uploaded_at' => $f->created_at->diffForHumans(),
            ]);

        return response()->json($files);
    }

    // ─── Upload: receive the AES-GCM encrypted blob + metadata ───────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:51200'],   // 50 MB max
            'iv' => ['required', 'string', 'size:24'],   // 12-byte IV → 24 hex chars
            'sha256_hash' => ['required', 'string', 'size:64'],   // SHA-256 hex of ORIGINAL
            'original_name' => ['required', 'string', 'max:255'],
        ]);

        $uploaded = $request->file('file');
        $storedName = Str::uuid().'.enc';

        // Store the already-encrypted blob; no server-side encryption needed
        $uploaded->storeAs('encrypted', $storedName, 'private');

        $file = $request->user()->files()->create([
            'original_name' => $request->input('original_name'),
            'stored_name' => $storedName,
            'mime_type' => $uploaded->getClientMimeType(),
            'size' => $uploaded->getSize(),
            'sha256_hash' => $request->input('sha256_hash'),
            'iv' => $request->input('iv'),
        ]);

        return response()->json([
            'message' => 'File uploaded successfully.',
            'file' => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->formatted_size,
                'sha256_hash' => $file->sha256_hash,
                'iv' => $file->iv,
                'uploaded_at' => $file->created_at->diffForHumans(),
            ],
        ], 201);
    }

    // ─── Download: stream the encrypted blob; decryption happens client-side ──
    public function download(Request $request, File $file):  BinaryFileResponse
    {
        // Ensure the file belongs to the authenticated user
        if ($file->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $path = Storage::disk('private')->path("encrypted/{$file->stored_name}");

        if (! file_exists($path)) {
            abort(404, 'File not found on disk.');
        }

        return response()->download($path, $file->original_name.'.enc', [
            'Content-Type' => 'application/octet-stream',
            'X-File-IV' => $file->iv,
            'X-File-SHA256' => $file->sha256_hash,
            'X-Original-Name' => rawurlencode($file->original_name),
            'X-Original-Mime' => $file->mime_type,
        ]);
    }

    // ─── Delete ────────────────────────────────────────────────────────────────
    public function destroy(Request $request, File $file): JsonResponse
    {
        if ($file->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        Storage::disk('private')->delete("encrypted/{$file->stored_name}");
        $file->delete();

        return response()->json(['message' => 'File deleted.']);
    }
}
