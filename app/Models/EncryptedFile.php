<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncryptedFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'mime_type',
        'path',
    ];

    // ── relationships ─────────────────────────────

    public function fileKeys()
    {
        return $this->hasMany(FileKey::class, 'file_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}