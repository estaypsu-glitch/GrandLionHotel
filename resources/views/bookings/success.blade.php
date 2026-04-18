@extends('layouts.app')

@section('title', 'Booking Confirmed')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <section class="soft-card p-4 p-lg-5 text-center">
                <p class="ta-eyebrow mb-1">Reservation Complete</p>
                <h1 class="h2 mb-3">Booking Confirmed</h1>
                <p class="mb-1">Reservation #: <strong>{{ $booking->id }}</strong></p>
                <p class="mb-1">Room: <strong>{{ $booking->room->name ?? 'N/A' }}</strong></p>
                <p class="mb-1">Dates: <strong>{{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</strong></p>
                <p class="mb-1">Total Paid: <strong>&#8369;{{ number_format($booking->total_price, 2) }}</strong></p>
                <p class="mb-4">Payment Status: <strong>{{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}</strong></p>

                @if($booking->payment?->transaction_reference)
                    <p class="small text-secondary mb-4">Transaction Reference: <strong>{{ $booking->payment->transaction_reference }}</strong></p>
                @endif

                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('bookings.show', $booking) }}" class="btn btn-ta">View booking details</a>
                    @if($booking->payment_status === 'paid')
                        <a href="{{ route('bookings.receipt', $booking) }}" class="btn btn-ta-outline">Download receipt</a>
                    @endif
                    <a href="{{ route('rooms.index') }}" class="btn btn-ta-outline">Book another room</a>
                </div>
            </section>
        </div>
    </div>
@endsection
