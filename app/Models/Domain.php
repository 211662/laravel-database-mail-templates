<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Domain extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'is_active',
        'is_custom',
        'user_id',
        'mx_record',
        'priority',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_custom' => 'boolean',
        'priority' => 'integer',
    ];

    /**
     * Get all temp emails for this domain
     */
    public function tempEmails(): HasMany
    {
        return $this->hasMany(TempEmail::class);
    }

    /**
     * Scope to get only active domains
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only public domains (not custom)
     */
    public function scopePublic($query)
    {
        return $query->where('is_custom', false);
    }

    /**
     * Get the full email address format
     */
    public function getFullDomainAttribute(): string
    {
        return '@' . $this->domain;
    }
}
