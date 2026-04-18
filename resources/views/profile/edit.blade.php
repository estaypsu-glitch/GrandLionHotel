@extends('layouts.app')

@section('title', 'My Profile')

@push('head')
    <style>
        .profile-page {
            display: grid;
            gap: 1.25rem;
        }
        .profile-panel,
        .profile-aside-card {
            border-radius: 20px;
            border: 1px solid var(--line);
            background: #fff;
            box-shadow: 0 14px 30px rgba(16, 24, 40, 0.08);
        }
        .profile-panel {
            padding: 1.25rem;
        }
        .profile-aside-card {
            padding: 1.1rem;
        }
        .profile-panel-title {
            margin-bottom: 0.2rem;
        }
        .profile-block {
            border: 1px solid #eadfcd;
            border-radius: 16px;
            background: #fff;
            padding: 0.95rem;
        }
        .profile-block + .profile-block {
            margin-top: 0.85rem;
        }
        .profile-block-title {
            margin-bottom: 0.7rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.78rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
            color: var(--muted);
        }
        .profile-label {
            margin-bottom: 0.34rem;
            color: var(--muted);
            font-size: 0.74rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        .profile-input-group .input-group-text {
            border-radius: 11px 0 0 11px;
            border-color: var(--line);
            background: #faf5ed;
            color: var(--muted);
            width: 2.8rem;
            justify-content: center;
        }
        .profile-input-group .form-control {
            border-radius: 0 11px 11px 0;
            border-left: 0;
            height: 46px;
        }
        .profile-input-group .form-control:focus {
            border-left: 0;
        }
        .profile-input-group:focus-within .input-group-text {
            border-color: rgba(184, 146, 84, 0.65);
            color: #8f6c38;
        }
        .profile-note {
            border-radius: 12px;
            border: 1px dashed var(--line);
            background: #faf5ed;
            color: var(--muted);
            font-size: 0.82rem;
            padding: 0.62rem 0.75rem;
        }
        .profile-meta {
            color: var(--muted);
            font-size: 0.78rem;
        }
        .profile-checklist {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 0.45rem;
        }
        .profile-check-item {
            display: flex;
            align-items: center;
            gap: 0.52rem;
            color: #5f6877;
            font-size: 0.88rem;
            font-weight: 600;
        }
        .profile-check-item.done {
            color: #0f6d46;
        }
        .profile-check-icon {
            width: 1.38rem;
            height: 1.38rem;
            border-radius: 999px;
            border: 1px solid #cfe6da;
            background: #f2fbf6;
            color: var(--success);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.76rem;
        }
        .profile-check-item:not(.done) .profile-check-icon {
            border-color: var(--line);
            background: #faf5ed;
            color: #8a92a0;
        }
        .profile-security-chips {
            margin-top: 0.55rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.36rem;
        }
        .profile-security-chip {
            border-radius: 999px;
            border: 1px solid var(--line);
            background: #fff;
            color: #5f6877;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            padding: 0.22rem 0.54rem;
        }
        .profile-field .invalid-feedback {
            display: block;
        }
        .profile-appear {
            opacity: 0;
            transform: translateY(8px);
            animation: profileAppear 0.42s ease forwards;
        }
        .profile-appear.delay-1 {
            animation-delay: 0.05s;
        }
        .profile-appear.delay-2 {
            animation-delay: 0.1s;
        }
        .profile-appear.delay-3 {
            animation-delay: 0.15s;
        }
        @keyframes profileAppear {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 575.98px) {
            .profile-panel,
            .profile-aside-card {
                border-radius: 16px;
                padding: 0.95rem;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $isCustomerProfile = $user->isCustomer();
        $nameParts = \App\Support\PersonName::split($user->name);
        $provinces = $isCustomerProfile ? config('philippines.provinces', []) : [];
        $profileChecklist = [
            'First name' => filled($nameParts['first_name']),
            'Last name' => filled($nameParts['last_name']),
            'Email address' => filled($user->email),
            'Phone number' => filled($user->phone),
        ];
        if ($isCustomerProfile) {
            $profileChecklist['Address line'] = filled($user->address_line);
            $profileChecklist['City'] = filled($user->city);
            $profileChecklist['Province'] = filled($user->province);
        }
        $totalProfileItems = count($profileChecklist);
        $completedProfileItems = collect($profileChecklist)->filter()->count();
        $profileCompletion = $totalProfileItems > 0 ? (int) round(($completedProfileItems / $totalProfileItems) * 100) : 0;
        $readinessText = $isCustomerProfile
            ? ($profileCompletion === 100 ? 'Ready for booking' : 'Needs attention')
            : ($profileCompletion === 100 ? 'Profile ready' : 'Needs attention');
    @endphp

    <div class="profile-page">
        <div class="row g-4">
            <div class="col-xl-8">
                <section class="profile-panel profile-appear delay-1">
                    <p class="ta-eyebrow mb-1">{{ $isCustomerProfile ? 'Guest Information' : 'Account Information' }}</p>
                    <h2 class="h4 profile-panel-title">Personal Details</h2>
                    <p class="text-secondary mb-4">
                        {{ $isCustomerProfile
                            ? 'Update your details to speed up future reservations and prevent booking issues.'
                            : 'Update your basic account details here.' }}
                    </p>

                    <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')

                        <div class="col-12">
                            <div class="profile-block">
                                <p class="profile-block-title"><i class="bi bi-person-badge"></i> Identity</p>
                                <div class="row g-3">
                                    <div class="col-md-6 profile-field">
                                        <label class="profile-label" for="profileFirstName">First name</label>
                                        <div class="input-group profile-input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input id="profileFirstName" type="text" class="form-control @error('first_name') is-invalid @enderror" name="first_name" value="{{ old('first_name', $nameParts['first_name']) }}" autocomplete="given-name" required>
                                        </div>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 profile-field">
                                        <label class="profile-label" for="profileLastName">Last name</label>
                                        <div class="input-group profile-input-group">
                                            <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                                            <input id="profileLastName" type="text" class="form-control @error('last_name') is-invalid @enderror" name="last_name" value="{{ old('last_name', $nameParts['last_name']) }}" autocomplete="family-name" required>
                                        </div>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 profile-field">
                                        <label class="profile-label" for="profilePhone">Phone number</label>
                                        <div class="input-group profile-input-group">
                                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                            <input id="profilePhone" type="text" class="form-control @error('phone') is-invalid @enderror" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+63..." required>
                                        </div>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6 profile-field">
                                        <label class="profile-label" for="profileEmail">Email address</label>
                                        <div class="input-group profile-input-group">
                                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                            <input id="profileEmail" type="email" class="form-control" value="{{ $user->email }}" disabled>
                                        </div>
                                        <small class="text-secondary">Email updates are locked for account protection.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @if($isCustomerProfile)
                            <div class="col-12">
                                <div class="profile-block">
                                    <p class="profile-block-title"><i class="bi bi-geo-alt"></i> Address</p>
                                    <div class="row g-3">
                                        <div class="col-12 profile-field">
                                            <label class="profile-label" for="profileAddressLine">Address line</label>
                                            <div class="input-group profile-input-group">
                                                <span class="input-group-text"><i class="bi bi-signpost-2"></i></span>
                                                <input id="profileAddressLine" type="text" class="form-control @error('address_line') is-invalid @enderror" name="address_line" value="{{ old('address_line', $user->address_line) }}" placeholder="Street, barangay, house number" required>
                                            </div>
                                            @error('address_line')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 profile-field">
                                            <label class="profile-label" for="profileCity">City</label>
                                            <div class="input-group profile-input-group">
                                                <span class="input-group-text"><i class="bi bi-buildings"></i></span>
                                                <input id="profileCity" type="text" class="form-control @error('city') is-invalid @enderror" name="city" value="{{ old('city', $user->city) }}" required>
                                            </div>
                                            @error('city')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 profile-field">
                                            <label class="profile-label" for="profileProvince">Province</label>
                                            <div class="input-group profile-input-group">
                                                <span class="input-group-text"><i class="bi bi-map"></i></span>
                                                <input
                                                    id="profileProvince"
                                                    type="text"
                                                    class="form-control @error('province') is-invalid @enderror"
                                                    name="province"
                                                    list="profile_province_list"
                                                    value="{{ old('province', $user->province) }}"
                                                    placeholder="Start typing to search province"
                                                    required
                                                    autocomplete="off"
                                                >
                                            </div>
                                            <datalist id="profile_province_list">
                                                @foreach($provinces as $province)
                                                    <option value="{{ $province }}"></option>
                                                @endforeach
                                            </datalist>
                                            @error('province')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="col-md-6 profile-field">
                                            <label class="profile-label" for="profileCountry">Country (optional)</label>
                                            <div class="input-group profile-input-group">
                                                <span class="input-group-text"><i class="bi bi-globe2"></i></span>
                                                <input id="profileCountry" type="text" class="form-control @error('country') is-invalid @enderror" name="country" value="{{ old('country', $user->country) }}" placeholder="Philippines">
                                            </div>
                                            @error('country')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1">
                            <p class="profile-meta mb-0">
                                Last account update:
                                {{ optional($user->updated_at)->format('M d, Y h:i A') ?: now()->format('M d, Y h:i A') }}
                            </p>
                            <button type="submit" class="btn btn-ta">Save profile details</button>
                        </div>
                    </form>
                </section>
            </div>

            <div class="col-xl-4">
                <section class="profile-aside-card mb-4 profile-appear delay-2">
                    <p class="ta-eyebrow mb-1">Profile Status</p>
                    <h2 class="h5 mb-2">{{ $readinessText }}</h2>
                    <p class="text-secondary small mb-3">
                        {{ $isCustomerProfile
                            ? ($profileCompletion === 100
                                ? 'Everything looks complete. You can proceed to booking anytime.'
                                : 'Fill remaining details below to avoid delays during reservation checkout.')
                            : ($profileCompletion === 100
                                ? 'Your basic account details are complete.'
                                : 'Add the remaining basic account details below.') }}
                    </p>

                    <ul class="profile-checklist mb-0">
                        @foreach($profileChecklist as $label => $isComplete)
                            <li class="profile-check-item {{ $isComplete ? 'done' : '' }}">
                                <span class="profile-check-icon">
                                    <i class="bi {{ $isComplete ? 'bi-check-lg' : 'bi-dash-lg' }}"></i>
                                </span>
                                <span>{{ $label }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="profile-aside-card profile-appear delay-3">
                    <p class="ta-eyebrow mb-1">Account Security</p>
                    <h2 class="h5 mb-2">Security Center</h2>
                    <p class="text-secondary small mb-2">Manage password protection in a dedicated page with stronger checks and clearer controls.</p>

                    <a href="{{ route('profile.security') }}" class="btn btn-ta w-100">Open Security Settings</a>
                </section>
            </div>
        </div>
    </div>
@endsection
