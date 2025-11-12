<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InboxMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'temp_email_id',
        'message_id',
        'from_address',
        'from_name',
        'subject',
        'body_html',
        'body_text',
        'has_attachments',
        'two_fa_code',
        'is_read',
        'received_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'is_read' => 'boolean',
        'has_attachments' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-extract 2FA codes when creating a message
        static::creating(function ($message) {
            if (!$message->two_fa_code && $message->body_text) {
                $message->two_fa_code = self::extract2FACode($message->body_text);
            }
        });
    }

    /**
     * Get the temp email for this message
     */
    public function tempEmail(): BelongsTo
    {
        return $this->belongsTo(TempEmail::class);
    }

    /**
     * Get all attachments for this message
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Mark message as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update(['is_read' => true]);
        }
    }

    /**
     * Extract 2FA code from email body
     */
    protected static function extract2FACode(?string $text): ?string
    {
        if (!$text) {
            return null;
        }

        // Pattern 1: 6-digit codes
        if (preg_match('/\b(\d{6})\b/', $text, $matches)) {
            return $matches[1];
        }

        // Pattern 2: "code: XXXXX" or "verification code: XXXXX"
        if (preg_match('/(?:verification\s+)?code[:\s]+([A-Z0-9]{4,8})/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        // Pattern 3: "OTP: XXXXX"
        if (preg_match('/OTP[:\s]+([A-Z0-9]{4,8})/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        // Pattern 4: 4-8 digit/letter codes in brackets or parentheses
        if (preg_match('/[\[\(]([A-Z0-9]{4,8})[\]\)]/i', $text, $matches)) {
            return strtoupper($matches[1]);
        }

        return null;
    }

    /**
     * Get the sender display name
     */
    public function getSenderAttribute(): string
    {
        return $this->from_name ?: $this->from_address;
    }

    /**
     * Get a preview of the body text
     */
    public function getPreviewAttribute(): string
    {
        $text = strip_tags($this->body_text ?: $this->body_html);
        return Str::limit($text, 150);
    }
}
