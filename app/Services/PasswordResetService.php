<?php

namespace App\Services;

use App\Mail\PasswordResetCodeMail;
use App\Models\PasswordResetToken;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class PasswordResetService
{
    public function send(PasswordResetToken $token, string $code): void
    {
        $token->forceFill([
            'code_hash' => Hash::make($code),
            'code_expires_at' => now()->addMinutes(10),
            'attempts' => 0,
            'last_sent_at' => now(),
            'otp_channel' => PasswordResetToken::OTP_CHANNEL_EMAIL,
        ])->save();

        // Password reset OTP should be delivered immediately for better UX and reliability.
        Mail::to($token->email)->send(new PasswordResetCodeMail(
            recipientName: $this->extractName($token->email),
            code: $code
        ));
    }

    private function extractName(string $email): string
    {
        return explode('@', $email)[0];
    }
}
