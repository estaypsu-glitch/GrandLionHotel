<?php

namespace App\Http\Controllers;

use App\Models\Room;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
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
