<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_name');
            $table->string('stored_name')->unique();
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('sha256_hash', 64);   // integrity hash of the ORIGINAL file
            $table->string('iv', 32);             // AES-GCM IV (hex, 24 chars = 12 bytes)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};