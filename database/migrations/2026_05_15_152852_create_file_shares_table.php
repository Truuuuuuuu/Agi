<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('file_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // File metadata (NOT the key — key stays in the browser)
            $table->string('original_name');          // original filename for display
            $table->string('stored_name')->unique();  // UUID filename on disk
            $table->string('mime_type')->nullable();  // original MIME type (for icon display)
            $table->unsignedBigInteger('size_bytes'); // original file size (for display)
            $table->string('disk')->default('local'); // storage disk

            // Encrypted file info
            // The ciphertext blob is stored on disk (storage/app/encrypted/)
            // We store the IV + salt so the owner can decrypt later
            // Key is NEVER stored here — zero-knowledge server
            $table->text('iv_base64');                // AES-GCM IV (base64)
            $table->text('salt_base64');              // PBKDF2 salt (base64)
            $table->string('algorithm')->default('AES-GCM'); // encryption algorithm

            // Access tracking
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamp('last_accessed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_shares');
    }
};