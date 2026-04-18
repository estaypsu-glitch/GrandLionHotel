@extends('layouts.app')

@section('title', 'Sign In')

@push('head')
    @include('auth.partials.premium-styles')
@endpush

@section('content')
    @php
        $registrationCompletedEmail = session('registration_completed_email');
    @endphp
    <div class="row justify-content-center">
        <div class="col-xl-11 col-xxl-10">
            <section class="soft-card overflow-hidden auth-premium-shell">
                <div class="row g-0">
                    <div class="col-lg-6 d-none d-lg-block auth-premium-visual">
                        <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?auto=format&fit=crop&w=1600&q=80" alt="Premium hotel suite interior">
                        <div class="auth-premium-overlay"></div>
                        <div class="auth-premium-copy">
                            <p class="ta-eyebrow text-light mb-2">Member Access</p>
                            <h1 class="display-6 mb-0">Sign in to manage your bookings.</h1>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="auth-premium-form-pane">
                            <div class="auth-brand-signature">
                                <img src="{{ asset('brand/lion_logo.png') }}" alt="The Grand Lion Hotel" class="auth-brand-mark">
                            </div>
                            <div class="mb-4">
                                <p class="ta-eyebrow mb-1">Welcome Back</p>
                                <h2 class="mb-0">Sign in to The Grand Lion Hotel</h2>
                            </div>

                            @if($registrationCompletedEmail)
                                <div class="auth-premium-meta mb-3">
                                    Account created successfully for <strong>{{ $registrationCompletedEmail }}</strong>. Sign in to continue.
                                </div>
                            @endif

                            <div class="auth-premium-switch mb-3">
                                <a class="auth-premium-switch-link active" href="{{ route('login') }}">Sign in</a>
                                <a class="auth-premium-switch-link" href="{{ route('register') }}">Create account</a>
                            </div>

                            <div class="auth-premium-card">
                                <form method="POST" action="{{ route('login.perform') }}" class="row g-3">
                                    @csrf
                                    <div class="col-12">
                                        <label class="auth-premium-label">Email address</label>
                                        <input
                                            type="email"
                                            class="form-control auth-premium-input @error('email') is-invalid @enderror"
                                            name="email"
                                            value="{{ old('email', $registrationCompletedEmail) }}"
                                            placeholder="you@example.com"
                                            autocomplete="email"
                                            required
                                            autofocus
                                        >
                                        @error('email')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="auth-premium-label">Password</label>
                                        <div class="auth-password-wrap">
                                            <input
                                                type="password"
                                                class="form-control auth-premium-input @error('password') is-invalid @enderror"
                                                id="login_password"
                                                name="password"
                                                placeholder="Enter your password"
                                                autocomplete="current-password"
                                                required
                                            >
                                            <button type="button" class="auth-password-toggle" data-password-toggle="login_password" aria-label="Show password">Show</button>
                                        </div>
                                        @error('password')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-12 d-flex justify-content-between align-items-center">
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember" @checked(old('remember'))>
                                            <label class="form-check-label" for="remember">Keep me signed in</label>
                                        </div>
                                        <a href="{{ route('password.request') }}" class="small text-decoration-none fw-medium text-ta-gold">Forgot password?</a>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-ta auth-premium-action">Sign in</button>
                                        </div>

                                    <div class="auth-oauth-divider mb-3">or continue with Google</div>
                                    <div class="mb-3">
                                        <a href="{{ route('auth.google.redirect.login') }}" class="auth-oauth-btn">
                                            <span class="auth-oauth-icon"></span>
                                            Continue with Google
                                        </a>
                                    </div>
                                </form>
                            </div>

                            <p class="text-secondary mt-4 mb-0">
                                No account yet?
                                <a href="{{ route('register') }}" class="auth-premium-link">Create account</a>
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
