<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'inbox_message_id',
        'filename',
        'content_type',
        'size',
        'storage_path',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    /**
     * Get the message for this attachment
     */
    public function inboxMessage(): BelongsTo
    {
        return $this->belongsTo(InboxMessage::class);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get download URL
     */
    public function getDownloadUrlAttribute(): string
    {
        return route('api.attachment.download', $this->id);
    }

    /**
     * Delete the file when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($attachment) {
            if (Storage::exists($attachment->storage_path)) {
                Storage::delete($attachment->storage_path);
            }
        });
    }
}
