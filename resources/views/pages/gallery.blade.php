@extends('layouts.app')

@section('title', 'Gallery')

@push('head')
    <style>
        .gallery-link {
            display: block;
            text-decoration: none;
            color: inherit;
        }
        .gallery-card {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            border: 1px solid var(--line);
        }
        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 34px rgba(14, 19, 31, 0.16);
            border-color: #d2c2a8;
        }
        .gallery-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            display: block;
        }
        .gallery-details {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            color: #fff;
            padding: 0.9rem 1rem;
            background: linear-gradient(180deg, rgba(16, 24, 40, 0.04) 0%, rgba(16, 24, 40, 0.9) 100%);
        }
        .gallery-room-name {
            margin: 0;
            font-size: 1rem;
            line-height: 1.2;
            color: #fff;
            font-weight: 800;
        }
        .gallery-room-meta {
            margin: 0.18rem 0 0;
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.88);
        }
        .gallery-room-price {
            margin: 0.2rem 0 0;
            font-size: 0.86rem;
            font-weight: 700;
            color: #f9e9ca;
        }
        .gallery-empty {
            border: 1px dashed var(--line);
            border-radius: 16px;
            background: rgba(255, 255, 255, 0.78);
        }
    </style>
@endpush

@section('content')
    <section class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
        <div>
            <p class="ta-eyebrow mb-1">Visual Tour</p>
            <h1 class="mb-1">Hotel Gallery</h1>
            <p class="text-secondary mb-0">Click any room photo to open that room's full details and booking page.</p>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta">Book a room</a>
    </section>

    <section class="row g-3">
        @forelse($rooms as $room)
            <div class="col-md-6 col-lg-4">
                <a href="{{ route('rooms.show', $room) }}" class="gallery-link" aria-label="View details for {{ $room->name }}">
                    <article class="gallery-card soft-card">
                        <img class="gallery-image" src="{{ $room->image_url }}" alt="{{ $room->name }}">
                        <div class="gallery-details">
                            <p class="gallery-room-name">{{ $room->name }}</p>
                            <p class="gallery-room-meta">
                                {{ $room->type ?? 'Room' }}
                                @if(filled($room->view_type))
                                    &middot; {{ $room->view_type }}
                                @endif
                                &middot; {{ $room->capacity }} guests
                            </p>
                            <p class="gallery-room-price">&#8369;{{ number_format((float) $room->price_per_night, 2) }} per night</p>
                        </div>
                    </article>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="gallery-empty p-4 text-center">
                    <p class="mb-2">No rooms are available for gallery preview yet.</p>
                    <a href="{{ route('rooms.index') }}" class="btn btn-ta btn-sm">Browse Rooms</a>
                </div>
            </div>
        @endforelse
    </section>
@endsection
