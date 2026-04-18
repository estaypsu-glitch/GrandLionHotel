@extends('layouts.app')

@section('title', 'Create New Password')

@push('head')
    @include('auth.partials.premium-styles')
    <style>
        .reset-security-note {
            border: 1px dashed #d9c8b0;
            border-radius: 12px;
            background: #faf5ec;
            color: #5f6876;
            font-size: 0.85rem;
            padding: 0.65rem 0.8rem;
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
                        <p class="ta-eyebrow text-light mb-2">Step 2 of 2</p>
                        <h1 class="display-6 mb-2">Set A New Password</h1>
                        <p class="text-light mb-3">Your OTP is verified. Create a strong password to secure your account.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="auth-premium-chip">Strong password</span>
                            <span class="auth-premium-chip">Secure account</span>
                            <span class="auth-premium-chip">Instant sign in ready</span>
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
                            <h2 class="mb-1">Create New Password</h2>
                            <p class="text-secondary mb-0">Set your new password and confirm it.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="auth-premium-card">
                            <form method="POST" action="{{ route('password.update') }}" class="row g-3">
                                @csrf

                                <div class="col-12">
                                    <label class="auth-premium-label">Email</label>
                                    <input type="email" class="form-control auth-premium-input" name="email" value="{{ $email }}" readonly>
                                </div>

                                <div class="col-12">
                                    <label class="auth-premium-label">New password</label>
                                    <input type="password" class="form-control auth-premium-input @error('password') is-invalid @enderror" name="password" placeholder="Minimum 8 characters" required autocomplete="new-password">
                                    @error('password')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <label class="auth-premium-label">Confirm new password</label>
                                    <input type="password" class="form-control auth-premium-input @error('password_confirmation') is-invalid @enderror" name="password_confirmation" placeholder="Retype password" required autocomplete="new-password">
                                    @error('password_confirmation')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12">
                                    <div class="reset-security-note">
                                        Password must include at least 8 characters, one uppercase letter, and one number.
                                    </div>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-ta auth-premium-action">Reset password</button>
                                </div>
                            </form>
                        </div>

                        <div class="row g-2 mt-3">
                            <div class="col-md-6">
                                <a href="{{ route('password.reset', ['email' => $email]) }}" class="btn btn-ta-outline w-100">Back to OTP</a>
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
