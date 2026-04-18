@extends('layouts.app')

@section('title', 'Register')

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
    </style>
@endpush

@section('content')
    <div class="row justify-content-center">
        <div class="col-xl-11 col-xxl-10">
            <section class="soft-card overflow-hidden auth-premium-shell">
                <div class="row g-0">
                    <div class="col-lg-6 d-none d-lg-block auth-premium-visual">
                        <img src="https://images.unsplash.com/photo-1578683010236-d716f9a3f461?auto=format&fit=crop&w=1600&q=80" alt="Luxury hotel room interior">
                        <div class="auth-premium-overlay"></div>
                        <div class="auth-premium-copy">
                            <p class="ta-eyebrow text-light mb-2">New Guest Setup</p>
                            <h1 class="display-6 mb-0">Create your account and start booking.</h1>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="auth-premium-form-pane">
                            <div class="auth-brand-signature">
                                <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="auth-brand-mark">
                            </div>
                            <div class="mb-4">
                                <p class="ta-eyebrow mb-1">Create Account</p>
                                <h2 class="mb-0">Join The Grand Lion Hotel</h2>
                            </div>

                            <div class="auth-premium-switch mb-3">
                                <a class="auth-premium-switch-link" href="{{ route('login') }}">Sign in</a>
                                <a class="auth-premium-switch-link active" href="{{ route('register') }}">Create account</a>
                            </div>

                            @if(session('status'))
                                <div class="auth-inline-message mb-3">{{ session('status') }}</div>
                            @endif

                            <div class="auth-premium-card">
                                <form method="POST" action="{{ route('register.perform') }}" class="row g-3">
                                    @csrf
                                    <div class="col-md-6">
                                        <label class="auth-premium-label">First name</label>
                                        <input
                                            type="text"
                                            class="form-control auth-premium-input @error('first_name') is-invalid @enderror"
                                            name="first_name"
                                            value="{{ old('first_name') }}"
                                            placeholder="Your first name"
                                            autocomplete="given-name"
                                            required
                                        >
                                        @error('first_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="auth-premium-label">Last name</label>
                                        <input
                                            type="text"
                                            class="form-control auth-premium-input @error('last_name') is-invalid @enderror"
                                            name="last_name"
                                            value="{{ old('last_name') }}"
                                            placeholder="Your last name"
                                            autocomplete="family-name"
                                            required
                                        >
                                        @error('last_name')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-premium-label">Email address</label>
                                        <input
                                            type="email"
                                            class="form-control auth-premium-input @error('email') is-invalid @enderror"
                                            name="email"
                                            value="{{ old('email') }}"
                                            placeholder="you@example.com"
                                            autocomplete="email"
                                            required
                                        >
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-premium-label">Phone number</label>
                                        <input
                                            type="text"
                                            class="form-control auth-premium-input @error('phone') is-invalid @enderror"
                                            name="phone"
                                            value="{{ old('phone') }}"
                                            placeholder="+63..."
                                            maxlength="30"
                                            inputmode="tel"
                                            pattern="[0-9+()\-\s]{7,30}"
                                            autocomplete="tel"
                                            required
                                        >
                                        @error('phone')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-premium-label">Password</label>
                                        <div class="auth-password-wrap">
                                            <input
                                                type="password"
                                                class="form-control auth-premium-input @error('password') is-invalid @enderror"
                                                name="password"
                                                id="register_password"
                                                placeholder="Minimum 8 characters"
                                                autocomplete="new-password"
                                                required
                                            >
                                            <button type="button" class="auth-password-toggle" data-password-toggle="register_password" aria-label="Show password">Show</button>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-premium-label">Confirm password</label>
                                        <div class="auth-password-wrap">
                                            <input
                                                type="password"
                                                class="form-control auth-premium-input @error('password_confirmation') is-invalid @enderror"
                                                name="password_confirmation"
                                                id="register_password_confirmation"
                                                placeholder="Retype password"
                                                autocomplete="new-password"
                                                required
                                            >
                                            <button type="button" class="auth-password-toggle" data-password-toggle="register_password_confirmation" aria-label="Show password confirmation">Show</button>
                                        </div>
                                        @error('password_confirmation')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-ta auth-premium-action">Create account</button>
                                        </div>

                                    <div class="auth-oauth-divider mb-3">or continue with Google</div>
                                    <div class="mb-3">
                                        <a href="{{ route('auth.google.redirect.register') }}" class="auth-oauth-btn">
                                            <span class="auth-oauth-icon"></span>
                                            Continue with Google
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <p class="text-secondary mt-4 mb-0">
                                Already registered?
                                <a href="{{ route('login') }}" class="auth-premium-link">Sign in</a>
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
        (() => {
            const toggles = document.querySelectorAll('[data-password-toggle]');

            toggles.forEach((toggleButton) => {
                toggleButton.addEventListener('click', () => {
                    const inputId = toggleButton.getAttribute('data-password-toggle');
                    const targetInput = inputId ? document.getElementById(inputId) : null;
                    if (!targetInput) {
                        return;
                    }

                    const showPassword = targetInput.type === 'password';
                    targetInput.type = showPassword ? 'text' : 'password';
                    toggleButton.textContent = showPassword ? 'Hide' : 'Show';
                    toggleButton.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                });
            });
        })();
    </script>
@endpush
