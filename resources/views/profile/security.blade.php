@extends('layouts.app')

@section('title', 'Account Security')

@push('head')
    <style>
        .security-card {
            max-width: 760px;
            margin: 0 auto;
            border-radius: 20px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 14px 30px rgba(16, 24, 40, 0.08);
            padding: 1.2rem;
        }
        .security-input {
            min-height: 50px;
        }
        .security-header-copy {
            color: #6f7785;
            font-size: 0.92rem;
            margin: 0.3rem 0 0;
        }
        .security-password-wrap {
            position: relative;
        }
        .security-password-wrap input[type="password"]::-ms-reveal,
        .security-password-wrap input[type="password"]::-ms-clear {
            display: none;
        }
        .security-password-wrap .form-control {
            padding-right: 3.2rem;
        }
        .security-password-toggle {
            position: absolute;
            right: 0.4rem;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            border-radius: 8px;
            background: transparent;
            color: var(--muted);
            font-size: 0.66rem;
            font-weight: 800;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            padding: 0.34rem 0.48rem;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .security-password-toggle:hover {
            background: #f5efe5;
            color: #3f4755;
        }
        .security-password-toggle:focus {
            outline: 0;
            box-shadow: 0 0 0 0.2rem rgba(184, 146, 84, 0.22);
        }
        .security-hint {
            border-radius: 12px;
            border: 1px dashed var(--line);
            background: #faf5ed;
            color: #5f6877;
            font-size: 0.82rem;
            padding: 0.62rem 0.75rem;
        }
        .security-field .invalid-feedback {
            display: block;
        }
    </style>
@endpush

@section('content')
    <div class="security-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <p class="ta-eyebrow mb-1">Account Security</p>
                <h1 class="h4 mb-0">Change Password</h1>
                <p class="security-header-copy">Update your password to protect your account and bookings.</p>
            </div>
            <a href="{{ route('profile.edit') }}" class="btn btn-ta-outline">Back</a>
        </div>

        <form method="POST" action="{{ route('profile.password.update') }}" class="row g-3">
            @csrf
            @method('PATCH')

            <div class="col-12 security-field">
                <label class="form-label" for="currentPasswordInput">Current Password</label>
                <div class="security-password-wrap">
                    <input
                        id="currentPasswordInput"
                        type="password"
                        class="form-control security-input @error('current_password') is-invalid @enderror"
                        name="current_password"
                        placeholder="Enter current password"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="security-password-toggle" data-toggle-password="currentPasswordInput" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @error('current_password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 security-field">
                <label class="form-label" for="newPasswordInput">New Password</label>
                <div class="security-password-wrap">
                    <input
                        id="newPasswordInput"
                        type="password"
                        class="form-control security-input @error('password') is-invalid @enderror"
                        name="password"
                        placeholder="Enter new password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="security-password-toggle" data-toggle-password="newPasswordInput" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-md-6 security-field">
                <label class="form-label" for="confirmPasswordInput">Confirm New Password</label>
                <div class="security-password-wrap">
                    <input
                        id="confirmPasswordInput"
                        type="password"
                        class="form-control security-input @error('password_confirmation') is-invalid @enderror"
                        name="password_confirmation"
                        placeholder="Retype new password"
                        required
                        autocomplete="new-password"
                    >
                    <button type="button" class="security-password-toggle" data-toggle-password="confirmPasswordInput" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <div class="security-hint">Password must be at least 10 characters and include uppercase, lowercase, number, and symbol.</div>
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="1" id="logoutOtherDevicesInput" name="logout_other_devices" {{ old('logout_other_devices') ? 'checked' : '' }}>
                    <label class="form-check-label" for="logoutOtherDevicesInput">Sign out this account from other devices</label>
                </div>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('profile.edit') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Save New Password</button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var targetId = button.getAttribute('data-toggle-password');
                    var input = document.getElementById(targetId);
                    if (!input) {
                        return;
                    }

                    var isPassword = input.getAttribute('type') === 'password';
                    input.setAttribute('type', isPassword ? 'text' : 'password');
                    var icon = button.querySelector('i');
                    if (icon) {
                        icon.className = isPassword ? 'bi bi-eye-slash' : 'bi bi-eye';
                    }
                    button.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            });
        });
    </script>
@endpush
