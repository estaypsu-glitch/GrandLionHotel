<?php

namespace App\Http\Controllers;

use App\Models\Room;

class HomeController extends Controller
{
    public function index()
    {
        $featuredRooms = Room::query()
            ->availableForBooking()
            ->latest()
            ->take(6)
            ->get();
        $roomCategories = Room::query()
            ->selectRaw('type, COUNT(*) as total')
            ->whereNotNull('type')
            ->where('type', '!=', '')
            ->availableForBooking()
            ->groupBy('type')
            ->orderByDesc('total')
            ->orderBy('type')
            ->take(8)
            ->get();

        $platformStats = [
            'total_rooms' => Room::count(),
            'available_rooms' => Room::query()->availableForBooking()->count(),
            'starting_price' => Room::min('price_per_night'),
        ];

        return view('home', compact('featuredRooms', 'roomCategories', 'platformStats'));
    }
}
