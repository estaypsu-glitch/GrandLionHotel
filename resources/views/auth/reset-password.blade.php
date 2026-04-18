@extends('layouts.app')

@section('title', 'Reset Password')

@push('head')
    @include('auth.partials.premium-styles')
    <style>
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
            transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
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

        .otp-help-text {
            font-size: 0.82rem;
            color: #7a8494;
            margin-top: 0.45rem;
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

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11 col-xxl-10">
        <section class="soft-card overflow-hidden auth-premium-shell">
            <div class="row g-0">
                <div class="col-lg-6 d-none d-lg-block auth-premium-visual">
                    <img src="https://images.unsplash.com/photo-1576092768241-dec231879920?auto=format&fit=crop&w=1600&q=80" alt="Premium security vault">
                    <div class="auth-premium-overlay"></div>
                    <div class="auth-premium-copy">
                        <p class="ta-eyebrow text-light mb-2">Secure Reset</p>
                        <h1 class="display-6 mb-2">Verify Your OTP Code</h1>
                        <p class="text-light mb-3">Enter the one-time code from your email to continue.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="auth-premium-chip">6-digit verification</span>
                            <span class="auth-premium-chip">Encrypted process</span>
                            <span class="auth-premium-chip">Fast secure reset</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="auth-premium-form-pane">
                        <div class="auth-brand-signature">
                            <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="auth-brand-mark">
                        </div>

                        <div class="mb-4">
                            <p class="ta-eyebrow mb-1">Reset Password</p>
                            <h2 class="mb-1">Verify OTP Code</h2>
                            <p class="text-secondary mb-0">Enter the 6-digit code we sent to your email.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="auth-premium-card">
                            <form method="POST" action="{{ route('password.verify') }}" class="row g-3">
                                @csrf

                                <div class="col-12">
                                    <label class="auth-premium-label">Email</label>
                                    <input type="email" class="form-control auth-premium-input" name="email" value="{{ $email }}" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="auth-premium-label">Verification code</label>
                                    <div class="auth-premium-meta mb-2">Enter the code sent to <strong>{{ $email }}</strong>.</div>
                                    @php
                                        $oldCode = preg_replace('/\D/', '', (string) old('code', ''));
                                    @endphp
                                    <input type="hidden" name="code" id="reset-code-hidden" value="{{ $oldCode }}">
                                    <div class="otp-code-group" id="otp-code-group">
                                        @for ($i = 0; $i < 6; $i++)
                                            <input
                                                type="text"
                                                inputmode="numeric"
                                                pattern="[0-9]*"
                                                maxlength="1"
                                                class="otp-code-slot @error('code') is-invalid @enderror"
                                                data-otp-index="{{ $i }}"
                                                value="{{ $oldCode[$i] ?? '' }}"
                                                autocomplete="one-time-code"
                                                {{ $i === 0 ? 'autofocus' : '' }}
                                            >
                                        @endfor
                                    </div>
                                    <div class="otp-help-text">Tip: You can paste the full 6-digit code.</div>
                                    @error('code')
                                        <div class="invalid-feedback d-block mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-ta auth-premium-action">Continue</button>
                                </div>
                            </form>
                        </div>

                        <div class="row g-2 mt-3">
                            <div class="col-md-6">
                                <form method="POST" action="{{ route('password.email') }}">
                                    @csrf
                                    <input type="hidden" name="email" value="{{ $email }}">
                                    <button type="submit" class="btn btn-ta-outline w-100">Resend code</button>
                                </form>
                            </div>
                            <div class="col-md-6">
                                <a href="{{ route('login') }}" class="btn btn-ta-outline w-100">Back to sign in</a>
                            </div>
                        </div>
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
    var form = document.querySelector('form[action="{{ route('password.verify') }}"]');
    var hiddenCode = document.getElementById('reset-code-hidden');
    var slots = Array.prototype.slice.call(document.querySelectorAll('[data-otp-index]'));

    if (!form || !hiddenCode || !slots.length) {
        return;
    }

    function syncHiddenCode() {
        hiddenCode.value = slots.map(function (slot) {
            return slot.value.replace(/\D/g, '').slice(0, 1);
        }).join('');
    }

    function focusSlot(index) {
        if (!slots[index]) {
            return;
        }
        slots[index].focus();
        slots[index].select();
    }

    slots.forEach(function (slot, index) {
        slot.addEventListener('input', function (event) {
            var value = event.target.value.replace(/\D/g, '');

            if (value.length > 1) {
                value = value.slice(-1);
            }

            event.target.value = value;
            syncHiddenCode();

            if (value && index < slots.length - 1) {
                focusSlot(index + 1);
            }
        });

        slot.addEventListener('keydown', function (event) {
            if (event.key === 'Backspace' && !slot.value && index > 0) {
                slots[index - 1].value = '';
                syncHiddenCode();
                focusSlot(index - 1);
                event.preventDefault();
            }

            if (event.key === 'ArrowLeft' && index > 0) {
                focusSlot(index - 1);
                event.preventDefault();
            }

            if (event.key === 'ArrowRight' && index < slots.length - 1) {
                focusSlot(index + 1);
                event.preventDefault();
            }
        });

        slot.addEventListener('paste', function (event) {
            var pasted = (event.clipboardData || window.clipboardData)
                .getData('text')
                .replace(/\D/g, '')
                .slice(0, slots.length);

            if (!pasted) {
                return;
            }

            event.preventDefault();

            slots.forEach(function (target, i) {
                target.value = pasted[i] || '';
            });

            syncHiddenCode();
            focusSlot(Math.max(0, Math.min(pasted.length - 1, slots.length - 1)));
        });

        slot.addEventListener('focus', function () {
            slot.select();
        });
    });

    syncHiddenCode();

    form.addEventListener('submit', function () {
        syncHiddenCode();
    });
});
</script>
@endpush
