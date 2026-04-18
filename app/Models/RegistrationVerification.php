<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationVerification extends Model
{
    public const OTP_CHANNEL_EMAIL = 'email';

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'phone',
        'otp_channel',
        'password_encrypted',
        'code_hash',
        'code_expires_at',
        'attempts',
        'last_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'code_expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function otpDestination(): string
    {
        return (string) ($this->email ?? '');
    }
}
