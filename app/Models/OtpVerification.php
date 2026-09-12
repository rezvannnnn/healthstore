<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone',
        'code_hash',
        'expires_at',
        'attempts',
        'verified_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Determine whether this OTP is still valid.
     */
    public function isValid(): bool
    {
        return $this->verified_at === null
            && $this->expires_at->isFuture();
    }

    /**
     * Determine whether this OTP has already been verified.
     */
    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * Determine whether the maximum number of attempts has been reached.
     */
    public function hasExceededAttempts(
        int $maxAttempts = 5
    ): bool {
        return $this->attempts >= $maxAttempts;
    }
}