@extends('layouts.admin')

@section('title', 'Edit Staff Account')

@section('content')
    @php($staffName = \App\Support\PersonName::split($staff->name))
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Edit Staff Account</h1>
        <a href="{{ route('admin.staff.index') }}" class="btn btn-ta-outline">Back to staff list</a>
    </div>

    <section class="soft-card p-4">
        <form method="POST" action="{{ route('admin.staff.update', $staff) }}" class="row g-3">
            @csrf
            @method('PUT')
            <div class="col-md-6">
                <label class="form-label">First name</label>
                <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $staffName['first_name']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Last name</label>
                <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $staffName['last_name']) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone (optional)</label>
                <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}" placeholder="+63...">
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
                <a href="{{ route('admin.staff.index') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Save changes</button>
            </div>
        </form>
    </section>
@endsection
