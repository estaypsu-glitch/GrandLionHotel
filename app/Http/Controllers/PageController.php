<?php

namespace App\Http\Controllers;

use App\Models\Room;

class PageController extends Controller
{
    public function about()
    {
        $roomTypes = Room::query()
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        $aboutStats = [
            'total_rooms' => Room::query()->count(),
            'available_rooms' => Room::query()->availableForBooking()->count(),
            'room_types' => $roomTypes->count(),
            'starting_rate' => Room::query()->min('price_per_night'),
        ];

        return view('pages.about', compact('aboutStats', 'roomTypes'));
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function gallery()
    {
        $rooms = Room::query()
            ->availableForBooking()
            ->latest()
            ->take(12)
            ->get();

        return view('pages.gallery', compact('rooms'));
    }
}
