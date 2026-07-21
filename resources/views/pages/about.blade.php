@extends('layouts.app')

@section('title', 'About')

@section('content')
    @php
        $aboutStats = $aboutStats ?? [
            'total_rooms' => 0,
            'available_rooms' => 0,
            'room_types' => 0,
            'starting_rate' => null,
        ];
        $roomTypes = $roomTypes ?? collect();
    @endphp

    <section class="soft-card overflow-hidden mb-4">
        <div class="row g-0">
            <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                <p class="ta-eyebrow mb-2">About The Grand Lion Hotel</p>
                <h1 class="display-5 mb-3">A comfortable local stay built for restful nights, family trips, and practical business travel.</h1>
                <p class="text-secondary mb-4">
                    The Grand Lion Hotel focuses on what matters most to guests: clean rooms, clear rates, responsive support, and a reservation process that feels simple from inquiry to check-in.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="chip">Check-in from 2:00 PM</span>
                    <span class="chip">Check-out at 12:00 PM</span>
                    <span class="chip">Cash and online payment options</span>
                    <span class="chip">Reservation support for stay updates</span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('rooms.index') }}" class="btn btn-ta">Browse rooms</a>
                    <a href="{{ route('gallery') }}" class="btn btn-ta-outline">View gallery</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1600&q=80" alt="Grand Lion Hotel lounge interior" class="w-100 h-100 object-cover" style="min-height: 320px;">
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Rooms</p>
                <h3 class="mb-1">{{ $aboutStats['total_rooms'] }}</h3>
                <p class="text-secondary small mb-0">Current rooms managed under The Grand Lion Hotel.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Room Types</p>
                <h3 class="mb-1">{{ $aboutStats['room_types'] }}</h3>
                <p class="text-secondary small mb-0">A room mix prepared for solo, couple, family, and premium stays.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Bookable Today</p>
                <h3 class="mb-1">{{ $aboutStats['available_rooms'] }}</h3>
                <p class="text-secondary small mb-0">Rooms currently open for guest booking and reservation review.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Starting Rate</p>
                <h3 class="mb-1">
                    @if(!is_null($aboutStats['starting_rate']))
                        &#8369;{{ number_format((float) $aboutStats['starting_rate'], 2) }}
                    @else
                        --
                    @endif
                </h3>
                <p class="text-secondary small mb-0">Published base nightly rate for our entry room category.</p>
            </article>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 mb-4">
        <p class="ta-eyebrow mb-2">Our Story</p>
        <h2 class="mb-3">A hotel experience centered on comfort, clarity, and dependable service.</h2>
        <p class="text-secondary mb-4">
            The Grand Lion Hotel is designed for guests who want a stay that feels organized and welcoming from the start. Instead of trying to be oversized or complicated, we focus on well-prepared rooms, honest room details, and guest support that stays responsive before arrival, during the stay, and after booking changes.
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Room Readiness</p>
                    <h3 class="h5 mb-2">Prepared Before Arrival</h3>
                    <p class="text-secondary mb-0 small">We prioritize room status visibility, dependable housekeeping turnover, and clear availability before a booking is confirmed.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Reservation Flow</p>
                    <h3 class="h5 mb-2">Clear Booking Process</h3>
                    <p class="text-secondary mb-0 small">Guests can review room choices, nightly rates, date-based discounts, and payment status without guessing what comes next.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Guest Care</p>
                    <h3 class="h5 mb-2">Support That Feels Human</h3>
                    <p class="text-secondary mb-0 small">From payment verification to cancellation, refund review, reschedule requests, and room concerns, we keep the process practical and easy to follow.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">Who We Serve</p>
                <h3 class="h4 mb-2">Families, Couples, and Business Guests</h3>
                <p class="text-secondary mb-0">Our room mix is built for quick overnight stays, family visits, and guests who need a clean, quiet, and organized place to settle in.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">What We Value</p>
                <h3 class="h4 mb-2">Comfort Without Confusion</h3>
                <p class="text-secondary mb-0">We believe guests should understand their room, rate, payment status, and next step without needing to chase updates.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">Stay Standard</p>
                <h3 class="h4 mb-2">Clear, Responsive, and Well-Maintained</h3>
                <p class="text-secondary mb-0">That means transparent booking details, practical policies, and room preparation that supports a smoother guest experience.</p>
            </article>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 mb-4">
        <p class="ta-eyebrow mb-2">Stay Details</p>
        <h2 class="mb-3">Practical information guests usually ask first.</h2>
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-box-arrow-in-right"></i>
                        <h3 class="h5 mb-0">Check-In</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Standard check-in starts at 2:00 PM unless a specific arrangement is confirmed in advance.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-box-arrow-right"></i>
                        <h3 class="h5 mb-0">Check-Out</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Standard check-out is at 12:00 PM to support room turnover and incoming guest preparation.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-credit-card-2-front"></i>
                        <h3 class="h5 mb-0">Payments</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Cash and supported online payment methods are shown during checkout, with online submissions subject to verification.</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-arrow-repeat"></i>
                        <h3 class="h5 mb-0">Booking Changes</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Reschedules, cancellations, and refund review follow the booking terms and operational approval flow.</p>
                </div>
            </div>
        </div>

        <div class="border rounded-4 p-3 p-lg-4">
            <p class="ta-eyebrow mb-2">Current Room Mix</p>
            @if($roomTypes->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-3">
                    @foreach($roomTypes as $type)
                        <span class="chip">{{ $type }}</span>
                    @endforeach
                </div>
            @endif
            <p class="text-secondary mb-0">
                We keep our room selection practical: categories are planned to serve different group sizes, budgets, and stay purposes while keeping the booking details easy to compare.
            </p>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Plan Your Stay</p>
            <h2 class="mb-1">Ready to check available rooms?</h2>
            <p class="text-secondary mb-0">Explore room options, compare rates, or review our stay policies before you book.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('rooms.index') }}" class="btn btn-ta">Browse rooms</a>
            <a href="{{ route('terms') }}" class="btn btn-ta-outline">View policies</a>
        </div>
    </section>
@endsection
