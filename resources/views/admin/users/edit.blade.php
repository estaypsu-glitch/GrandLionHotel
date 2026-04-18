@extends('layouts.admin')

@section('title', 'Edit Customer Account')

@section('content')
    @php($userName = \App\Support\PersonName::split($user->name))
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Edit Customer Account</h1>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ta-outline">Back to customers</a>
    </div>

    <section class="soft-card p-4 mb-4">
        <div class="row g-3 mb-1">
            <div class="col-md-4">
                <p class="small text-secondary mb-1 text-uppercase">Account ID</p>
                <p class="mb-0 fw-semibold">#{{ $user->id }}</p>
            </div>
            <div class="col-md-4">
                <p class="small text-secondary mb-1 text-uppercase">Booking Count</p>
                <p class="mb-0 fw-semibold">{{ $user->bookings_count }}</p>
            </div>
            <div class="col-md-4">
                <p class="small text-secondary mb-1 text-uppercase">Created</p>
                <p class="mb-0 fw-semibold">{{ $user->created_at?->format('M d, Y h:i A') }}</p>
            </div>
        </div>
    </section>

    <section class="soft-card p-4">
        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label class="form-label">First name</label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $userName['first_name']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last name</label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $userName['last_name']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone (optional)</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" placeholder="+63...">
            </div>
            <div class="col-md-6">
                <label class="form-label">Address line (optional)</label>
                <input type="text" name="address_line" class="form-control" value="{{ old('address_line', $user->address_line) }}" placeholder="Street, barangay, house number">
            </div>
            <div class="col-md-4">
                <label class="form-label">City (optional)</label>
                <input type="text" name="city" class="form-control" value="{{ old('city', $user->city) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Province (optional)</label>
                <input type="text" name="province" class="form-control" value="{{ old('province', $user->province) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label">Country (optional)</label>
                <input type="text" name="country" class="form-control" value="{{ old('country', $user->country) }}" placeholder="Philippines">
            </div>
            <div class="col-md-6">
                <label class="form-label">New password (optional)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Confirm new password</label>
                <input type="password" name="password_confirmation" class="form-control">
            </div>
            <div class="col-12 d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.users.index') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Save changes</button>
            </div>
        </form>
    </section>
@endsection
