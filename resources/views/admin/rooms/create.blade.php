@extends('layouts.admin')

@section('title', 'Create Room')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Create Room</h1>
        <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Back to rooms</a>
    </div>

    <section class="soft-card p-4">
        <form method="POST" action="{{ route('admin.rooms.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <input type="text" class="form-control" name="type" value="{{ old('type') }}" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">View Type</label>
                <input type="text" class="form-control" name="view_type" value="{{ old('view_type') }}" placeholder="Nature View, Garden View, etc.">
            </div>
            <div class="col-12">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
            </div>
            <div class="col-md-3">
                <label class="form-label">Price per night</label>
                <input type="number" step="0.01" class="form-control" name="price_per_night" value="{{ old('price_per_night') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Capacity</label>
                <input type="number" class="form-control" name="capacity" min="1" value="{{ old('capacity') }}" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Image URL</label>
                <input type="url" class="form-control" name="image" value="{{ old('image') }}" placeholder="https://...">
            </div>
            <div class="col-12 d-flex justify-content-end gap-2">
                <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Cancel</a>
                <button type="submit" class="btn btn-ta">Save room</button>
            </div>
        </form>
    </section>
@endsection
