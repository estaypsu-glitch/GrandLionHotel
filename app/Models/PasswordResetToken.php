<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    const OTP_CHANNEL_EMAIL = 'email';

    protected $primaryKey = 'email';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'token',
        'code_hash',
        'code_expires_at',
        'attempts',
        'last_sent_at',
        'otp_channel',
    ];

    protected $casts = [
        'code_expires_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public static function findValidByEmail(string $email): ?static
    {
        return static::where('email', $email)
            ->where('code_expires_at', '>', now())
            ->first();
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function isExpired(): bool
    {
        return now()->greaterThan($this->code_expires_at);
    }

    public function attemptsExceeded(int $max = 5): bool
    {
        return $this->attempts >= $max;
    }

    public function canResend(int $cooldownSeconds = 60): bool
    {
        return !$this->last_sent_at || $this->last_sent_at->diffInSeconds(now()) >= $cooldownSeconds;
    }

    public function resetForNewCode(): void
    {
        $this->update([
            'attempts' => 0,
            'code_expires_at' => now()->addMinutes(10),
        ]);
    }
}
