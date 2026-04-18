@extends('layouts.app')

@section('title', 'Forgot Password')

@push('head')
    @include('auth.partials.premium-styles')
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-xl-11 col-xxl-10">
        <section class="soft-card overflow-hidden auth-premium-shell">
            <div class="row g-0">
                <div class="col-lg-6 d-none d-lg-block auth-premium-visual">
<img src="https://images.unsplash.com/photo-1587014611672-2b632dd3eb6d?auto=format&fit=crop&w=1600&q=80" alt="Secure key lock">
                    <div class="auth-premium-overlay"></div>
                    <div class="auth-premium-copy">
                        <p class="ta-eyebrow text-light mb-2">Password Recovery</p>
                        <h1 class="display-6 mb-2">Reset access securely.</h1>
                        <p class="text-light mb-3">We'll send a verification code to your email for immediate access restoration.</p>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="auth-premium-chip">6-digit code</span>
                            <span class="auth-premium-chip">10-min expiry</span>
                            <span class="auth-premium-chip">Encrypted secure</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="auth-premium-form-pane">
                        <div class="auth-brand-signature">
                            <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="auth-brand-mark">
                        </div>
                        <div class="mb-4">
                            <p class="ta-eyebrow mb-1">Password Reset</p>
                            <h2 class="mb-1">Recover Account Access</h2>
                            <p class="text-secondary mb-0">Enter your email. We'll send a secure code.</p>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('status') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        <div class="auth-premium-card">
                            <form method="POST" action="{{ route('password.email') }}" class="row g-3">
                                @csrf
                                <div class="col-12">
                                    <label class="auth-premium-label">Registered email</label>
                                    <input type="email" class="form-control auth-premium-input @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" placeholder="your@email.com" required autofocus>
                                    @error('email')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-12">
                                    <div class="auth-premium-meta mb-3">Only registered emails will receive a reset code. Code expires in 10 minutes.</div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-ta auth-premium-action">
                                        <span class="me-2">Send verification code</span>
                                        <i class="fas fa-arrow-right ms-auto opacity-75"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="text-center mt-4 pt-3">
                            <a href="{{ route('login') }}" class="btn btn-ta-outline px-4">
                                <i class="fas fa-arrow-left me-2"></i>Back to sign in
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
