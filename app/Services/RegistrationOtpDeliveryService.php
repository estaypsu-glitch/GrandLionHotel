<?php

namespace App\Services;

use App\Mail\RegistrationVerificationCodeMail;
use App\Models\RegistrationVerification;
use Illuminate\Support\Facades\Mail;

class RegistrationOtpDeliveryService
{
    public function send(RegistrationVerification $verification, string $code): void
    {
        // OTP is time-sensitive; send immediately so it does not depend on a queue worker.
        Mail::to($verification->email)->send(new RegistrationVerificationCodeMail($verification->name, $code));
    }
}
