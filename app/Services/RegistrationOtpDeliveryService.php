<?php

namespace App\Services;

use App\Mail\RegistrationVerificationCodeMail;
use App\Models\RegistrationVerification;
use Illuminate\Support\Facades\Mail;

class RegistrationOtpDeliveryService
{
    public function send(RegistrationVerification $verification, string $code): void
    {
        Mail::to($verification->email)->queue(new RegistrationVerificationCodeMail($verification->name, $code));
    }
}
