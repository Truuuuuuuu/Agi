<?php

namespace App\Http\Controllers;

use App\Models\EncryptedFile;
use App\Models\FileKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // LIST FILES (owned + shared via file_keys)
    // ─────────────────────────────────────────────────────────────
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $files = EncryptedFile::whereHas('fileKeys', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })
            ->latest()
            ->get()
            ->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->mime_type,
                'size' => $file->formatted_size ?? null,
                'uploaded_at' => $file->created_at->diffForHumans(),
            ]);

        return response()->json($files);
    }

    // ─────────────────────────────────────────────────────────────
    // UPLOAD FILE (AES file encryption + RSA wrapped key)
    // ─────────────────────────────────────────────────────────────
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file',
            'encrypted_key' => 'required',
            'original_name' => 'required|string',
            'original_size' => 'required|integer',
        ]);

        // 1. store encrypted file
        $path = $request->file('file')->store('encrypted-files');

        // 2. create file record
        $file = EncryptedFile::create([
            'user_id' => $request->user()->id,
            'original_name' => $request->original_name,
            'mime_type' => $request->file('file')->getMimeType(),
            'path' => $path,
        ]);

        // 3. store encrypted AES key (for owner)
        FileKey::create([
            'file_id' => $file->id,
            'user_id' => $request->user()->id,
            'encrypted_key' => base64_encode(
                $request->file('encrypted_key')->getContent()
            ),
        ]);

        return response()->json([
            'file' => $file,
        ]);
    }

    public function serve(Request $request, EncryptedFile $file): BinaryFileResponse
    {
        $userId = $request->user()->id;

        FileKey::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (! Storage::exists($file->path)) {
            abort(404, 'File not found on disk.');
        }

        return response()->file(Storage::path($file->path), [
            'Content-Type' => 'application/octet-stream',
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DOWNLOAD (return encrypted file + user-specific key)
    // ─────────────────────────────────────────────────────────────
    public function download(Request $request, EncryptedFile $file): JsonResponse
    {
        $userId = $request->user()->id;

        $fileKey = FileKey::where('file_id', $file->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        if (! Storage::exists($file->path)) {
            abort(404, 'File not found on disk.');
        }

        return response()->json([
            'file_url'      => url("/api/files/{$file->id}/serve"), 
            'encrypted_key' => $fileKey->encrypted_key,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // DELETE FILE (only owner)
    // ─────────────────────────────────────────────────────────────
    public function destroy(Request $request, EncryptedFile $file): JsonResponse
    {
        if ($file->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        // delete file from storage
        Storage::delete($file->path);

        // delete related keys (important)
        FileKey::where('file_id', $file->id)->delete();

        // delete file record
        $file->delete();

        return response()->json([
            'message' => 'File deleted.',
        ]);
    }
}
