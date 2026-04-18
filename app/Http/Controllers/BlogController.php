<?php

namespace App\Http\Controllers;

class BlogController extends Controller
{
    public function index()
    {
        $posts = collect($this->posts());
        $featured = $posts->first();
        $latest = $posts->slice(1)->values();
        $topics = $posts->pluck('category')->unique()->values();

        return view('blog.index', compact('featured', 'latest', 'topics'));
    }

    public function show(string $slug)
    {
        $posts = collect($this->posts());
        $post = $posts->firstWhere('slug', $slug);

        abort_unless($post, 404);

        $related = $posts
            ->where('slug', '!=', $slug)
            ->where('category', $post['category'])
            ->take(2)
            ->values();

        if ($related->count() < 2) {
            $related = $related->concat(
                $posts
                    ->where('slug', '!=', $slug)
                    ->whereNotIn('slug', $related->pluck('slug'))
                    ->take(2 - $related->count())
            )->values();
        }

        return view('blog.show', compact('post', 'related'));
    }

    private function posts(): array
    {
        return [
            [
                'slug' => 'best-time-to-book-a-city-hotel',
                'title' => 'Best Time To Book A City Hotel In 2026',
                'excerpt' => 'Smart booking windows and date strategies to get better rates without sacrificing quality.',
                'image' => 'https://images.unsplash.com/photo-1455587734955-081b22074882?auto=format&fit=crop&w=1400&q=80',
                'date' => 'March 1, 2026',
                'category' => 'Booking Strategy',
                'read_time' => '5 min read',
                'intro' => 'Booking at the right moment often saves more than chasing random promos. A clear timing strategy helps you secure better rates without giving up comfort.',
                'highlights' => [
                    'Book city stays 30 to 45 days before arrival for the best price and room balance.',
                    'Reserve earlier for holidays, concerts, and long weekends when inventory shrinks fast.',
                    'Compare refundable and non-refundable rates based on how fixed your travel dates are.',
                ],
                'sections' => [
                    [
                        'heading' => 'Understand the demand window',
                        'body' => 'City hotels usually price rooms around expected occupancy. Once events fill nearby venues, rates can rise quickly. Checking your destination calendar first gives you an edge before demand spikes.',
                    ],
                    [
                        'heading' => 'Use weekday check-ins to lower total cost',
                        'body' => 'For many business-oriented cities, Tuesday and Wednesday check-ins are often less expensive than Friday arrivals. Even shifting one night can reduce the total reservation amount.',
                    ],
                    [
                        'heading' => 'Watch total value, not headline price',
                        'body' => 'A slightly higher nightly rate may include breakfast, airport transfer, or flexible cancellation. Compare final value and policies before deciding based only on the base price.',
                    ],
                ],
                'tags' => ['Rate Strategy', 'Trip Planning', 'City Stay'],
                'author' => [
                    'name' => 'Lara Mendoza',
                    'role' => 'Hospitality Editor',
                ],
            ],
            [
                'slug' => 'how-to-pick-the-right-room-type',
                'title' => 'How To Pick The Right Room Type For Your Trip',
                'excerpt' => 'From standard to suite, choose based on stay duration, purpose, and comfort needs.',
                'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?auto=format&fit=crop&w=1400&q=80',
                'date' => 'February 20, 2026',
                'category' => 'Room Selection',
                'read_time' => '4 min read',
                'intro' => 'The right room depends on your trip goals, not just your budget. Matching layout and amenities to your schedule improves both comfort and productivity.',
                'highlights' => [
                    'For solo or short business trips, prioritize desk space, stable Wi-Fi, and quick access.',
                    'For families and longer stays, choose layouts with lounge space and larger storage.',
                    'Always confirm bed configuration, cancellation policy, and included amenities.',
                ],
                'sections' => [
                    [
                        'heading' => 'Choose based on trip purpose',
                        'body' => 'If you are mostly out for meetings or tours, you can often save with a well-designed standard room. If you plan to rest, work, or dine in-room, extra space pays off.',
                    ],
                    [
                        'heading' => 'Account for stay length',
                        'body' => 'Two nights and seven nights feel very different. On longer bookings, features like wardrobe size, seating area, and natural light make daily routines more comfortable.',
                    ],
                    [
                        'heading' => 'Review details before confirming',
                        'body' => 'Look closely at inclusions like breakfast, parking, and early check-in access. Small policy details can matter more than room photos when you are finalizing your booking.',
                    ],
                ],
                'tags' => ['Room Types', 'Comfort', 'Family Travel'],
                'author' => [
                    'name' => 'Miguel Santos',
                    'role' => 'Stay Experience Writer',
                ],
            ],
            [
                'slug' => 'hotel-safety-and-travel-checklist',
                'title' => 'Hotel Safety And Travel Checklist',
                'excerpt' => 'A practical pre-check-in checklist for safer and smoother hotel stays.',
                'image' => 'https://images.unsplash.com/photo-1576675784201-0e142b423952?auto=format&fit=crop&w=1400&q=80',
                'date' => 'January 28, 2026',
                'category' => 'Travel Safety',
                'read_time' => '6 min read',
                'intro' => 'A safer trip starts before arrival. A few preparation steps can prevent check-in delays, payment confusion, and avoidable security risks.',
                'highlights' => [
                    'Save your booking confirmation, payment proof, and hotel contact in one place.',
                    'Keep IDs ready and verify check-in/check-out windows before arrival.',
                    'During your stay, use in-room safes and review billing before departure.',
                ],
                'sections' => [
                    [
                        'heading' => 'Prepare documents in advance',
                        'body' => 'Store digital and printed copies of your reservation details. Having your confirmation number and receipt ready helps front desk teams resolve issues quickly.',
                    ],
                    [
                        'heading' => 'Secure essentials in-room',
                        'body' => 'Use the room safe for passports, cards, and electronics when you are away. Keep your door locked and avoid sharing room numbers publicly in common areas.',
                    ],
                    [
                        'heading' => 'Do a quick checkout audit',
                        'body' => 'Review mini-bar, room service, and incidental charges before leaving. A two-minute billing check prevents follow-up disputes after your trip.',
                    ],
                ],
                'tags' => ['Checklist', 'Safety Tips', 'Guest Guide'],
                'author' => [
                    'name' => 'Andrea Cruz',
                    'role' => 'Guest Safety Contributor',
                ],
            ],
            [
                'slug' => 'weekend-stay-itinerary-near-the-city-center',
                'title' => 'Weekend Stay Itinerary Near The City Center',
                'excerpt' => 'A balanced two-day plan for guests who want food, culture, and rest without rushing.',
                'image' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=1400&q=80',
                'date' => 'January 10, 2026',
                'category' => 'Local Guide',
                'read_time' => '5 min read',
                'intro' => 'City weekends are better with structure. A simple itinerary helps you enjoy top spots while keeping enough downtime between activities.',
                'highlights' => [
                    'Plan mornings for light sightseeing and evenings for dining experiences.',
                    'Book one anchor activity per day to avoid overpacked schedules.',
                    'Keep one open block for spontaneous plans or rest.',
                ],
                'sections' => [
                    [
                        'heading' => 'Day 1: Arrival and nearby exploration',
                        'body' => 'After check-in, focus on attractions within walking distance so you can settle in. A relaxed first evening keeps your energy high for the second day.',
                    ],
                    [
                        'heading' => 'Day 2: Signature experience',
                        'body' => 'Reserve one premium activity in advance, such as a museum pass or rooftop dinner. Build the rest of the day around flexible plans and easy transit routes.',
                    ],
                    [
                        'heading' => 'Departure: Keep the final morning light',
                        'body' => 'Schedule checkout with enough buffer for transport and last-minute purchases. A low-pressure departure closes your weekend stay on a better note.',
                    ],
                ],
                'tags' => ['Weekend Plan', 'City Guide', 'Leisure'],
                'author' => [
                    'name' => 'Paolo Reyes',
                    'role' => 'City Travel Editor',
                ],
            ],
            [
                'slug' => 'business-travel-check-in-routine',
                'title' => 'Business Travel Check-In Routine That Saves Time',
                'excerpt' => 'A repeatable routine for faster arrivals, organized schedules, and better workday flow.',
                'image' => 'https://images.unsplash.com/photo-1468824357306-a439d58ccb1c?auto=format&fit=crop&w=1400&q=80',
                'date' => 'December 22, 2025',
                'category' => 'Business Travel',
                'read_time' => '4 min read',
                'intro' => 'Frequent business trips become easier with a reliable check-in routine. Small habits reduce friction and free time for meetings and recovery.',
                'highlights' => [
                    'Use one travel folder for IDs, invoices, and schedule details.',
                    'Request key room features in advance: desk, quiet floor, and fast Wi-Fi.',
                    'Set a 10-minute setup routine once you reach your room.',
                ],
                'sections' => [
                    [
                        'heading' => 'Before arrival: simplify logistics',
                        'body' => 'Confirm transport, check-in time, and invoice details before landing. This removes back-and-forth at the front desk during busy arrival periods.',
                    ],
                    [
                        'heading' => 'At check-in: prioritize essentials',
                        'body' => 'Ask about Wi-Fi access, breakfast window, and quiet-room availability immediately. Getting these details early helps you structure your next business day.',
                    ],
                    [
                        'heading' => 'In-room setup: ready in 10 minutes',
                        'body' => 'Charge devices, test your connection, set meeting reminders, and prep next-day attire. A short routine lowers stress and keeps your focus on work priorities.',
                    ],
                ],
                'tags' => ['Productivity', 'Business Stay', 'Routine'],
                'author' => [
                    'name' => 'Nina Valdez',
                    'role' => 'Business Travel Columnist',
                ],
            ],
        ];
    }
}
