@extends('layouts.app')

@section('title', 'Home')

@push('head')
    <style>
        .hero-bleed {
            position: relative;
            left: 50%;
            right: 50%;
            width: 100vw;
            margin-left: -50vw;
            margin-right: -50vw;
        }

        .hero-bleed .hero-carousel {
            border-radius: 0;
            border: 0;
            box-shadow: none;
            position: relative;
        }
        .hero-carousel .carousel-item,
        .hero-carousel .hero-image {
            height: min(82vh, 760px);
        }
        .hero-carousel .hero-image {
            object-fit: cover;
        }
        .hero-fixed-content {
            position: absolute;
            left: 6%;
            right: 6%;
            bottom: 12%;
            z-index: 2;
            text-align: left;
            pointer-events: none;
        }
        .hero-fixed-copy {
            max-width: 680px;
            pointer-events: auto;
        }
        .hero-carousel .carousel-indicators {
            z-index: 3;
        }
        .hero-carousel .carousel-control-prev,
        .hero-carousel .carousel-control-next {
            z-index: 3;
        }
        .hero-carousel .ta-eyebrow {
            color: rgba(255, 255, 255, 0.85);
        }
        .hero-carousel .carousel-control-prev,
        .hero-carousel .carousel-control-next {
            width: auto;
            top: 50%;
            bottom: auto;
            transform: translateY(-50%);
            opacity: 1;
        }
        .hero-carousel .carousel-control-prev {
            left: 1.4rem;
        }
        .hero-carousel .carousel-control-next {
            right: 1.4rem;
        }
        .hero-carousel .carousel-control-prev-icon,
        .hero-carousel .carousel-control-next-icon {
            width: 2.8rem;
            height: 2.8rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.48);
            background-color: rgba(16, 24, 40, 0.42);
            background-size: 1rem 1rem;
            backdrop-filter: blur(2px);
        }
        .hero-carousel .carousel-control-prev:hover .carousel-control-prev-icon,
        .hero-carousel .carousel-control-next:hover .carousel-control-next-icon {
            background-color: rgba(16, 24, 40, 0.62);
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(10, 20, 30, 0.72) 0%, rgba(10, 20, 30, 0.2) 60%, rgba(10, 20, 30, 0.05) 100%);
            pointer-events: none;
            z-index: 1;
        }
        .home-welcome-note {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            border: 1px solid rgba(184, 146, 84, 0.34);
            background: rgba(255, 255, 255, 0.94);
            color: #243446;
            border-radius: 999px;
            padding: 0.38rem 0.82rem;
            font-size: 0.84rem;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(16, 24, 40, 0.08);
        }
        .home-welcome-dot {
            width: 0.5rem;
            height: 0.5rem;
            border-radius: 999px;
            background: #b89254;
            display: inline-block;
        }
        .category-link {
            text-decoration: none;
            color: inherit;
            display: block;
            height: 100%;
        }
        .category-link .chip {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
            background: rgba(184, 146, 84, 0.14);
            font-size: 0.74rem;
            font-weight: 700;
            color: #7a5f33;
        }
        .feature-item {
            border: 1px solid var(--line);
            border-radius: 18px;
            background: #fff;
            padding: 1rem;
            height: 100%;
        }
        .step-count {
            width: 2rem;
            height: 2rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(184, 146, 84, 0.16);
            color: #7a5f33;
            font-weight: 800;
            font-size: 0.82rem;
        }
        @media (max-width: 991.98px) {
            .hero-carousel .carousel-item,
            .hero-carousel .hero-image {
                height: 560px;
            }
            .hero-fixed-content {
                bottom: 9%;
            }
            .hero-carousel h1 {
                font-size: 1.85rem;
            }
        }
        @media (max-width: 575.98px) {
            .hero-carousel .carousel-item,
            .hero-carousel .hero-image {
                height: 440px;
            }
            .hero-carousel .carousel-control-prev {
                left: 0.8rem;
            }
            .hero-carousel .carousel-control-next {
                right: 0.8rem;
            }
            .hero-carousel .carousel-control-prev-icon,
            .hero-carousel .carousel-control-next-icon {
                width: 2.45rem;
                height: 2.45rem;
            }
        }
    </style>
@endpush

@section('content')
    @if(session('account_created_name'))
        <div class="d-flex justify-content-end mb-3">
            <div class="home-welcome-note">
                <span class="home-welcome-dot" aria-hidden="true"></span>
                Welcome, {{ \Illuminate\Support\Str::limit(session('account_created_name'), 24) }}.
            </div>
        </div>
    @endif

    <section class="hero-bleed mb-4 mb-lg-5">
        <div id="homeHeroCarousel" class="carousel slide hero-carousel soft-card overflow-hidden" data-bs-ride="carousel" data-bs-interval="2500">
            <div class="carousel-indicators">
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                <button type="button" data-bs-target="#homeHeroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
            </div>

            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Luxury hotel exterior">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Hotel infinity pool">
                </div>
                <div class="carousel-item">
                    <img src="https://images.unsplash.com/photo-1522798514-97ceb8c4f1c8?auto=format&fit=crop&w=2200&q=80" class="d-block w-100 hero-image" alt="Modern hotel room interior">
                </div>
            </div>
            <div class="hero-overlay"></div>
            <div class="hero-fixed-content">
                <div class="hero-fixed-copy">
                    <p class="ta-eyebrow text-light mb-2">Welcome to The Grand Lion Hotel</p>
                    <h1 class="display-5 text-white mb-2">Stay where comfort meets nature-inspired views.</h1>
                    <p class="text-light mb-3">Explore professionally managed rooms with instant booking confirmation.</p>
                    <a href="{{ route('rooms.index') }}" class="btn btn-ta">Browse Rooms</a>
                </div>
            </div>

            <button class="carousel-control-prev" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#homeHeroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    <section class="soft-card p-3 p-lg-4 mb-5">
        <p class="ta-eyebrow mb-2">Quick Search</p>
        <form method="GET" action="{{ route('rooms.search') }}" class="row g-2">
            <div class="col-md-3 col-xl-3">
                <input type="text" name="type" class="form-control" placeholder="Room type or view (e.g. Nature View)">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <input type="number" name="guests" class="form-control" min="1" placeholder="Guests">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <input type="date" name="check_in" class="form-control" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}">
            </div>
            <div class="col-6 col-md-2 col-xl-2">
                <input type="date" name="check_out" class="form-control" min="{{ now()->addDay()->toDateString() }}" value="{{ now()->addDay()->toDateString() }}">
            </div>
            <div class="col-6 col-md-3 col-xl-3 d-grid">
                <button class="btn btn-ta" type="submit">Search Rooms</button>
            </div>
            <div class="col-12">
                <div class="form-check mt-1">
                    <input class="form-check-input" type="checkbox" name="available_only" value="1" id="availableOnlyHome" checked>
                    <label class="form-check-label text-secondary small" for="availableOnlyHome">Show available rooms only</label>
                </div>
            </div>
        </form>
    </section>

    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <p class="ta-eyebrow mb-1">Categories</p>
                <h2 class="mb-0">Browse by Room Type</h2>
            </div>
            <a href="{{ route('rooms.search') }}" class="btn btn-sm btn-ta-outline">Open filters</a>
        </div>
        <div class="row g-3">
            @forelse($roomCategories as $category)
                <div class="col-6 col-lg-3">
                    <a
                        href="{{ route('rooms.search', ['type' => $category->type, 'available_only' => 1]) }}"
                        class="soft-card result-card p-3 p-lg-4 category-link"
                    >
                        <span class="chip mb-2">Category</span>
                        <h3 class="h5 mb-1">{{ $category->type }}</h3>
                        <p class="text-secondary small mb-0">{{ $category->total }} room{{ $category->total === 1 ? '' : 's' }} available to explore</p>
                    </a>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info soft-card border-0 mb-0">Room categories will appear here once rooms are added.</div>
                </div>
            @endforelse
        </div>
    </section>

    <section class="row g-3 mb-5">
        <div class="col-sm-4">
            <div class="soft-card p-3 p-lg-4 h-100">
                <p class="ta-eyebrow mb-1">Inventory</p>
                <h3 class="mb-0">{{ $platformStats['total_rooms'] }}</h3>
                <p class="mb-0 text-secondary small">Total listed rooms</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="soft-card p-3 p-lg-4 h-100">
                <p class="ta-eyebrow mb-1">Bookable Now</p>
                <h3 class="mb-0">{{ $platformStats['available_rooms'] }}</h3>
                <p class="mb-0 text-secondary small">Rooms currently available</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="soft-card p-3 p-lg-4 h-100">
                <p class="ta-eyebrow mb-1">Starting Rate</p>
                <h3 class="mb-0">
                    @if(!is_null($platformStats['starting_price']))
                        &#8369;{{ number_format((float) $platformStats['starting_price'], 2) }}
                    @else
                        --
                    @endif
                </h3>
                <p class="mb-0 text-secondary small">Lowest nightly rate</p>
            </div>
        </div>
    </section>

    <section class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <p class="ta-eyebrow mb-1">Featured</p>
            <h2 class="mb-0">Top Room Picks</h2>
        </div>
        <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline">Browse all rooms</a>
    </section>

    <section class="row g-4">
        @forelse($featuredRooms as $room)
            <div class="col-md-6 col-xl-4">
                <article class="soft-card h-100 result-card overflow-hidden">
                    <img src="{{ $room->image_url }}" alt="{{ $room->name }}" class="w-100 object-cover" style="height: 220px;">
                    <div class="p-3 p-lg-4">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h3 class="h5 mb-1">{{ $room->name }}</h3>
                                <p class="hotel-meta mb-0">
                                    {{ $room->type }}
                                    @if(filled($room->view_type))
                                        &middot; {{ $room->view_type }}
                                    @endif
                                    &middot; Up to {{ $room->capacity }} guests
                                </p>
                            </div>
                            <span class="badge-status {{ $room->is_available ? 'available' : 'unavailable' }}">
                                {{ $room->is_available ? 'Available' : 'Unavailable' }}
                            </span>
                        </div>
                        <p class="text-secondary small mb-3">{{ \Illuminate\Support\Str::limit($room->description ?: 'Comfortable room with practical amenities and easy booking.', 95) }}</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="price-tag">&#8369;{{ number_format($room->price_per_night, 2) }}</div>
                                <small class="text-secondary">per night</small>
                            </div>
                            <a href="{{ route('rooms.show', $room) }}" class="btn btn-ta btn-sm">View details</a>
                        </div>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-info soft-card border-0">No rooms are available yet. Add rooms from the admin dashboard.</div>
            </div>
        @endforelse
    </section>

    <section class="soft-card p-3 p-lg-4 mt-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <p class="ta-eyebrow mb-1">How It Works</p>
                <h2 class="mb-0">Fast Booking in 3 Steps</h2>
            </div>
            <a href="{{ route('rooms.search') }}" class="btn btn-sm btn-ta">Start Booking</a>
        </div>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">1</span>
                    <h3 class="h5 mb-1">Choose a category</h3>
                    <p class="mb-0 text-secondary small">Pick a room type and set guest count based on your trip.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">2</span>
                    <h3 class="h5 mb-1">Compare top options</h3>
                    <p class="mb-0 text-secondary small">Review rates, amenities, and availability before checkout.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-item">
                    <span class="step-count mb-2">3</span>
                    <h3 class="h5 mb-1">Confirm your stay</h3>
                    <p class="mb-0 text-secondary small">Finish payment and receive your booking details immediately.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
