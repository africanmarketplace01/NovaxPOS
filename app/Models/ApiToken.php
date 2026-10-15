<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    protected $table = 'api_tokens';

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    protected $hidden = ['token'];

    /* ── Relationships ─────────────────────────────────────────── */

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /* ── Helpers ───────────────────────────────────────────────── */

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities ?? ['*'];
        return in_array('*', $abilities) || in_array($ability, $abilities);
    }
}
