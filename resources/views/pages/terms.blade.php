@extends('layouts.app')

@section('title', 'Terms')

@section('content')
    <section class="soft-card p-4 p-lg-5">
        <p class="ta-eyebrow mb-2">Policy</p>
        <h1 class="mb-4">Terms and Conditions</h1>
        <p class="text-secondary small">Effective date: April 14, 2026</p>

        <h3 class="h5">1. Reservations</h3>
        <p class="text-secondary">All bookings are subject to final room availability and successful payment confirmation.</p>

        <h3 class="h5">2. Check-In and Check-Out</h3>
        <p class="text-secondary">Standard check-in starts at 2:00 PM and check-out is at 12:00 PM, unless explicitly stated otherwise.</p>

        <h3 class="h5">3. Payment Policy</h3>
        <p class="text-secondary">Rates are displayed in Philippine Peso (PHP). Available payment methods are shown during checkout.</p>
        <p class="text-secondary mb-2">For online payments (InstaPay/Credit-Debit Card), the following rules apply:</p>
        <ul class="text-secondary mb-3">
            <li>Payment is subject to manual verification by authorized hotel staff.</li>
            <li>Payment is not treated as received until the booking payment status is marked as Paid.</li>
            <li>Guests must submit a valid transaction reference number and a clear payment proof image.</li>
            <li>Submitted proof may be rejected if details are incomplete, inconsistent, unreadable, or invalid.</li>
            <li>If rejected, the guest must resubmit correct proof before payment can be approved.</li>
            <li>Payment verification may take additional time depending on operational volume and review availability.</li>
            <li>Uploaded payment proof is used for payment validation, audit, and fraud-prevention purposes.</li>
        </ul>

        <h3 class="h5">4. Cancellations</h3>
        <p class="text-secondary">Cancellation and refund eligibility depend on your booking terms and selected room policy.</p>

        <h3 class="h5">5. Guest Responsibility</h3>
        <p class="text-secondary mb-0">Guests are responsible for providing accurate booking details and following property rules during their stay.</p>
    </section>
@endsection
