<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncryptedFile extends Model
{
    protected $fillable = [
        'user_id',
        'original_name',
        'size',
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

    public function getFormattedSizeAttribute()
    {
        $bytes = $this->size;

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    public function getTotalFilesAttribute(): int
    {
        return EncryptedFile::all()->count();
    }
}