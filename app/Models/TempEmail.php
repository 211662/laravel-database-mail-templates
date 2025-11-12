<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TempEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'username',
        'domain_id',
        'expires_at',
        'last_checked_at',
        'is_active',
        'ip_address',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the domain for this temp email
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get all messages for this temp email
     */
    public function messages(): HasMany
    {
        return $this->hasMany(InboxMessage::class)->latest('received_at');
    }

    /**
     * Scope to get only active and non-expired emails
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to get expired emails
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Check if the email is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark the email as checked
     */
    public function markAsChecked(): void
    {
        $this->update(['last_checked_at' => now()]);
    }

    /**
     * Get unread messages count
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->messages()->where('is_read', false)->count();
    }

    /**
     * Generate a new random temp email
     */
    public static function generate(?Domain $domain = null, int $lifetimeHours = 2): self
    {
        if (!$domain) {
            $domain = Domain::active()->public()->inRandomOrder()->first();
            
            if (!$domain) {
                throw new \Exception('No active domains available');
            }
        }

        $username = self::generateUsername();
        
        return self::create([
            'email' => "{$username}@{$domain->domain}",
            'username' => $username,
            'domain_id' => $domain->id,
            'expires_at' => now()->addHours($lifetimeHours),
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Generate a random username
     */
    protected static function generateUsername(): string
    {
        return Str::lower(Str::random(12));
    }

    /**
     * Get time remaining until expiration
     */
    public function getTimeRemainingAttribute(): string
    {
        if ($this->isExpired()) {
            return 'Expired';
        }

        return $this->expires_at->diffForHumans();
    }
}
