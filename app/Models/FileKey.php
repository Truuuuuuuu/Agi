<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileKey extends Model
{
    protected $fillable = [
        'file_id',
        'user_id',
        'encrypted_key',
    ];

    // ── relationships ─────────────────────────────

    public function file()
    {
        return $this->belongsTo(EncryptedFile::class, 'file_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}