@extends('layouts.app')

@section('title', 'About')

@section('content')
    <section class="soft-card overflow-hidden mb-4">
        <div class="row g-0">
            <div class="col-lg-6 p-4 p-lg-5 d-flex flex-column justify-content-center">
                <p class="ta-eyebrow mb-2">About The Grand Lion Hotel</p>
                <h1 class="display-5 mb-3">A premium booking platform built for modern travelers.</h1>
                <p class="text-secondary mb-4">
                    The Grand Lion Hotel combines hospitality standards with a streamlined digital reservation flow, from room discovery to payment confirmation.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('rooms.search') }}" class="btn btn-ta">Find a room</a>
                    <a href="{{ route('blog.index') }}" class="btn btn-ta-outline">Read travel guides</a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1600&q=80" alt="Hotel lounge interior" class="w-100 h-100 object-cover" style="min-height: 320px;">
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Founded</p>
                <h3 class="mb-1">2020</h3>
                <p class="text-secondary small mb-0">Operating as a digitally first hospitality brand.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Guest Nights</p>
                <h3 class="mb-1">18k+</h3>
                <p class="text-secondary small mb-0">Managed bookings across business and leisure stays.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Guest Rating</p>
                <h3 class="mb-1">4.8/5</h3>
                <p class="text-secondary small mb-0">Average satisfaction from post-stay feedback.</p>
            </article>
        </div>
        <div class="col-6 col-lg-3">
            <article class="soft-card h-100 p-3 p-lg-4">
                <p class="ta-eyebrow mb-1">Support</p>
                <h3 class="mb-1">24/7</h3>
                <p class="text-secondary small mb-0">Assistance for bookings, billing, and stay updates.</p>
            </article>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 mb-4">
        <p class="ta-eyebrow mb-2">Our Story</p>
        <h2 class="mb-3">From Traditional Front Desk To Smart Hospitality</h2>
        <p class="text-secondary mb-4">
            We started with one goal: make hotel booking feel clear, trustworthy, and elegant. As guest expectations shifted to digital-first service, we redesigned the reservation journey to be simpler without losing the warmth of hospitality.
        </p>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Phase 1</p>
                    <h3 class="h5 mb-2">Service Foundations</h3>
                    <p class="text-secondary mb-0 small">Focused on dependable room readiness, responsive support, and transparent guest policies.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Phase 2</p>
                    <h3 class="h5 mb-2">Digital Booking Flow</h3>
                    <p class="text-secondary mb-0 small">Launched faster room search, cleaner checkout, and clearer payment records for every booking.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100 bg-light-subtle">
                    <p class="ta-eyebrow mb-1">Phase 3</p>
                    <h3 class="h5 mb-2">Guest Experience Optimization</h3>
                    <p class="text-secondary mb-0 small">Enhanced pre-arrival communication and post-stay feedback to continuously improve guest comfort.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-4 mb-4">
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">Mission</p>
                <h3 class="h4 mb-2">Reliable Booking</h3>
                <p class="text-secondary mb-0">Deliver a smooth reservation flow with clear room details and trustworthy availability data.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">Vision</p>
                <h3 class="h4 mb-2">Premium Hospitality Online</h3>
                <p class="text-secondary mb-0">Become a trusted local platform that reflects luxury hotel standards in digital form.</p>
            </article>
        </div>
        <div class="col-md-4">
            <article class="soft-card h-100 p-4">
                <p class="ta-eyebrow mb-2">Promise</p>
                <h3 class="h4 mb-2">Transparent & Fast</h3>
                <p class="text-secondary mb-0">Simple checkout, clear payment records, and responsive booking management for every guest.</p>
            </article>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 mb-4">
        <p class="ta-eyebrow mb-2">What We Prioritize</p>
        <h2 class="mb-3">Guest-First Standards In Every Step</h2>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shield-check"></i>
                        <h3 class="h5 mb-0">Security & Privacy</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Protected guest data handling and transparent account controls across booking touchpoints.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-lightning-charge"></i>
                        <h3 class="h5 mb-0">Fast Confirmation</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Streamlined flow from room selection to payment confirmation with minimal friction.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="border rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-chat-heart"></i>
                        <h3 class="h5 mb-0">Human Support</h3>
                    </div>
                    <p class="text-secondary mb-0 small">Friendly assistance for travel planning, booking updates, and post-stay concerns.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="soft-card p-4 p-lg-5 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="ta-eyebrow mb-1">Plan With Confidence</p>
            <h2 class="mb-1">Ready for your next premium stay?</h2>
            <p class="text-secondary mb-0">Browse available rooms or read our practical guides before you book.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('rooms.index') }}" class="btn btn-ta">Browse rooms</a>
            <a href="{{ route('blog.index') }}" class="btn btn-ta-outline">Visit blog</a>
        </div>
    </section>
@endsection
