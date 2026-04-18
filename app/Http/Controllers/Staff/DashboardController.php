<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $stats = [
            'pending_bookings' => Booking::where('status', 'pending')->count(),
            'confirmed_bookings' => Booking::where('status', 'confirmed')->count(),
            'completed_bookings' => Booking::where('status', 'completed')->count(),
            'cancelled_bookings' => Booking::where('status', 'cancelled')->count(),
            'arrivals_today' => Booking::whereDate('check_in', $today)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNull('actual_check_in_at')
                ->count(),
            'departures_today' => Booking::whereDate('check_out', $today)
                ->where('status', 'confirmed')
                ->whereNotNull('actual_check_in_at')
                ->whereNull('actual_check_out_at')
                ->count(),
            'in_house' => Booking::where('status', 'confirmed')
                ->whereNotNull('actual_check_in_at')
                ->whereNull('actual_check_out_at')
                ->count(),
            'unpaid_confirmed' => Booking::where('status', 'confirmed')
                ->wherePaymentStatus('unpaid')
                ->count(),
        ];

        $arrivalsToday = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail'])
            ->whereDate('check_in', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('actual_check_in_at')
            ->orderBy('check_in')
            ->orderBy('booking_id')
            ->take(8)
            ->get();

        $inHouseGuests = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail'])
            ->where('status', 'confirmed')
            ->whereNotNull('actual_check_in_at')
            ->whereNull('actual_check_out_at')
            ->orderBy('check_out')
            ->take(8)
            ->get();

        return view('staff.dashboard', compact('stats', 'arrivalsToday', 'inHouseGuests'));
    }
}
