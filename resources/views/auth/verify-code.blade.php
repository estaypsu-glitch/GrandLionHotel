@extends('layouts.app')

@section('title', 'Verify Email')

@push('head')
    @include('auth.partials.premium-styles')
    <style>
        .auth-inline-message {
            border: 1px solid rgba(6, 118, 71, 0.25);
            background: linear-gradient(180deg, rgba(244, 251, 247, 0.98) 0%, rgba(237, 248, 242, 0.98) 100%);
            color: #075f3c;
            border-radius: 12px;
            padding: 0.75rem 0.9rem;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .otp-summary-shell {
            border: 1px solid #e2d8ca;
            background: linear-gradient(180deg, #fbf8f3 0%, #f7f1e8 100%);
            border-radius: 14px;
            padding: 0.8rem 0.95rem;
        }

        .otp-meta-line {
            font-size: 0.82rem;
            color: #687385;
        }

        .otp-countdown-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(184, 146, 84, 0.35);
            background: rgba(184, 146, 84, 0.1);
            padding: 0.28rem 0.62rem;
            font-size: 0.8rem;
            line-height: 1;
        }

        .otp-code-group {
            display: grid;
            grid-template-columns: repeat(6, minmax(44px, 1fr));
            gap: 0.55rem;
        }

        .otp-code-slot {
            height: 54px;
            border-radius: 12px;
            border: 1px solid #d8cab6;
            background: #fff;
            font-size: 1.2rem;
            font-weight: 700;
            text-align: center;
            color: #1f2937;
            font-family: 'Manrope', sans-serif;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .otp-code-slot:focus {
            border-color: rgba(184, 146, 84, 0.75);
            box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.22);
            transform: translateY(-1px);
            outline: none;
        }

        .otp-code-slot.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.15rem rgba(220, 53, 69, 0.15);
        }

        @media (max-width: 420px) {
            .otp-code-group {
                grid-template-columns: repeat(6, minmax(38px, 1fr));
                gap: 0.45rem;
            }

            .otp-code-slot {
                height: 50px;
            }
        }
    </style>
@endpush

@php
    $maskEmail = static function (?string $email): string {
        $email = (string) $email;
        if ($email === '' || !str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $localLength = strlen($local);

        if ($localLength <= 2) {
            $maskedLocal = str_repeat('*', max(1, $localLength));
        } elseif ($localLength <= 4) {
            $maskedLocal = substr($local, 0, 1)
                .str_repeat('*', max(2, $localLength - 2))
                .substr($local, -1);
        } else {
            $maskedLocal = substr($local, 0, 2)
                .str_repeat('*', max(2, $localLength - 4))
                .substr($local, -2);
        }

        return $maskedLocal.'@'.$domain;
    };

    $maskedVerificationEmail = $maskEmail($verification->email ?? '');
    $oldCode = preg_replace('/\D/', '', (string) old('code', ''));
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-11 col-xxl-10">
            <section class="soft-card overflow-hidden auth-premium-shell">
                <div class="row g-0">
                    <div class="col-lg-6 d-none d-lg-block auth-premium-visual">
                        <img src="https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1600&q=80" alt="Email confirmation concept">
                        <div class="auth-premium-overlay"></div>
                        <div class="auth-premium-copy">
                            <p class="ta-eyebrow text-light mb-2">Email Confirmation</p>
                            <h1 class="display-6 mb-2">Verify your account with a secure code.</h1>
                            <p class="text-light mb-3">Enter the 6-digit OTP from your email to activate your account.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="auth-premium-chip">One-time code</span>
                                <span class="auth-premium-chip">2-minute validity</span>
                                <span class="auth-premium-chip">Secure registration</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="auth-premium-form-pane">
                            <div class="auth-brand-signature">
                                <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="auth-brand-mark">
                            </div>

                            <div class="mb-4">
                                <p class="ta-eyebrow mb-1">Final Step</p>
                                <h2 class="mb-1">Enter Confirmation Code</h2>
                                <p class="text-secondary mb-0">
                                    We sent a 6-digit OTP to <strong>{{ $maskedVerificationEmail }}</strong>.
                                </p>
                            </div>

                            @if(session('status'))
                                <div class="auth-inline-message mb-3">{{ session('status') }}</div>
                            @endif

                            <div class="auth-premium-card">
                                <div class="otp-summary-shell mb-3">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                                        <span class="otp-meta-line">Code validity</span>
                                        <span
                                            class="otp-countdown-pill text-secondary"
                                            id="registerOtpCountdownText"
                                            data-otp-expiry-ts="{{ $verification->code_expires_at?->timestamp }}"
                                        >
                                            <span data-countdown-label>Time left</span>
                                            <strong id="registerOtpCountdownValue">--:--</strong>
                                        </span>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('register.verify.perform') }}" class="mb-3" id="registerOtpForm">
                                    @csrf
                                    <label class="auth-premium-label">Confirmation code</label>
                                    <input type="hidden" name="code" id="register-code-hidden" value="{{ $oldCode }}">
                                    <div class="otp-code-group">
                                        @for ($i = 0; $i < 6; $i++)
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="1"
                                                class="otp-code-slot @error('code') is-invalid @enderror"
                                                data-register-otp-index="{{ $i }}"
                                                value="{{ $oldCode[$i] ?? '' }}"
                                                autocomplete="one-time-code"
                                            >
                                        @endfor
                                    </div>
                                    <div class="otp-meta-line mt-2">Tip: You can paste the full 6-digit OTP.</div>
                                    @error('code')
                                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                    @enderror
                                    <button type="submit" class="btn btn-ta auth-premium-action mt-3 w-100" id="registerVerifySubmitBtn">
                                        Verify and create account
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('register.verify.resend') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-ta-outline w-100">Resend code</button>
                                </form>
                            </div>

                            <p class="text-secondary mt-4 mb-0">
                                Wrong email?
                                <a href="{{ route('register') }}" class="auth-premium-link">Start registration again</a>
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var verifyForm = document.getElementById('registerOtpForm');
            var hiddenCode = document.getElementById('register-code-hidden');
            var otpSlots = Array.prototype.slice.call(document.querySelectorAll('[data-register-otp-index]'));
            var verifySubmit = document.getElementById('registerVerifySubmitBtn');

            var syncHiddenCode = function () {
                if (!hiddenCode || !otpSlots.length) {
                    return;
                }

                hiddenCode.value = otpSlots.map(function (slot) {
                    return slot.value.replace(/\D/g, '').slice(0, 1);
                }).join('');
            };

            var focusSlot = function (index) {
                if (!otpSlots[index]) {
                    return;
                }

                otpSlots[index].focus();
                otpSlots[index].select();
            };

            if (hiddenCode && otpSlots.length) {
                otpSlots.forEach(function (slot, index) {
                    slot.addEventListener('input', function (event) {
                        var value = event.target.value.replace(/\D/g, '');

                        if (!value) {
                            event.target.value = '';
                            syncHiddenCode();
                            return;
                        }

                        if (value.length === 1) {
                            event.target.value = value;
                            syncHiddenCode();
                            if (index < otpSlots.length - 1) {
                                focusSlot(index + 1);
                            }
                            return;
                        }

                        var digits = value.slice(0, otpSlots.length - index).split('');
                        digits.forEach(function (digit, digitIndex) {
                            if (otpSlots[index + digitIndex]) {
                                otpSlots[index + digitIndex].value = digit;
                            }
                        });

                        syncHiddenCode();
                        focusSlot(Math.min(index + digits.length, otpSlots.length - 1));
                    });

                    slot.addEventListener('keydown', function (event) {
                        if (event.key === 'Backspace' && !slot.value && index > 0) {
                            otpSlots[index - 1].value = '';
                            syncHiddenCode();
                            focusSlot(index - 1);
                            event.preventDefault();
                        }

                        if (event.key === 'ArrowLeft' && index > 0) {
                            focusSlot(index - 1);
                            event.preventDefault();
                        }

                        if (event.key === 'ArrowRight' && index < otpSlots.length - 1) {
                            focusSlot(index + 1);
                            event.preventDefault();
                        }
                    });

                    slot.addEventListener('paste', function (event) {
                        var pasted = (event.clipboardData || window.clipboardData)
                            .getData('text')
                            .replace(/\D/g, '')
                            .slice(0, otpSlots.length);

                        if (!pasted) {
                            return;
                        }

                        event.preventDefault();

                        otpSlots.forEach(function (target, i) {
                            target.value = pasted[i] || '';
                        });

                        syncHiddenCode();
                        focusSlot(Math.max(0, Math.min(pasted.length - 1, otpSlots.length - 1)));
                    });

                    slot.addEventListener('focus', function () {
                        slot.select();
                    });
                });

                syncHiddenCode();

                var firstEmptyIndex = otpSlots.findIndex(function (slot) {
                    return !slot.value;
                });
                focusSlot(firstEmptyIndex >= 0 ? firstEmptyIndex : 0);
            }

            if (verifyForm) {
                verifyForm.addEventListener('submit', function () {
                    syncHiddenCode();
                });
            }

            var countdownText = document.getElementById('registerOtpCountdownText');
            var countdownValue = document.getElementById('registerOtpCountdownValue');
            var countdownLabel = countdownText ? countdownText.querySelector('[data-countdown-label]') : null;
            if (!countdownText || !countdownValue) {
                return;
            }

            var expiryTimestamp = Number(countdownText.getAttribute('data-otp-expiry-ts'));
            if (!Number.isFinite(expiryTimestamp) || expiryTimestamp <= 0) {
                return;
            }

            var intervalId = null;
            var setExpiredState = function () {
                countdownText.classList.remove('text-secondary');
                countdownText.classList.add('text-danger');
                if (countdownLabel) {
                    countdownLabel.textContent = 'Expired';
                }
                countdownValue.textContent = '00:00';

                if (verifySubmit) {
                    verifySubmit.disabled = true;
                    verifySubmit.classList.add('disabled');
                }
            };

            var updateCountdown = function () {
                var remainingSeconds = Math.max(0, Math.ceil(expiryTimestamp - (Date.now() / 1000)));
                var minutes = Math.floor(remainingSeconds / 60);
                var seconds = remainingSeconds % 60;

                countdownValue.textContent = String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0');

                if (remainingSeconds <= 0) {
                    setExpiredState();

                    if (intervalId !== null) {
                        clearInterval(intervalId);
                        intervalId = null;
                    }
                }
            };

            updateCountdown();
            intervalId = setInterval(updateCountdown, 1000);
        });
    </script>
@endpush
