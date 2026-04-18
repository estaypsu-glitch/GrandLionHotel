<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use App\Support\AccountDirectory;
use App\Support\PersonName;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $keyword = trim($request->string('q')->toString());
        $selectedDate = $this->resolveSelectedDate($request->string('earnings_date')->toString());
        $selectedDateString = $selectedDate->toDateString();

        $staffBaseQuery = Staff::query();

        $staffQuery = (clone $staffBaseQuery)
            ->withCount([
                'assignedBookings',
            ])
            ->when($keyword !== '', function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('email', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%');
                });
            });

        $staffMembers = $staffQuery
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $earningsSnapshot = Payment::query()
            ->selectRaw('bookings.staff_id as staff_id')
            ->selectRaw('COALESCE(SUM(payments.amount), 0) as revenue_total')
            ->selectRaw('COUNT(payments.payment_id) as paid_bookings')
            ->join('bookings', 'bookings.booking_id', '=', 'payments.booking_id')
            ->whereNotNull('bookings.staff_id')
            ->where('payments.status', 'paid')
            ->whereDate('payments.paid_at', $selectedDateString)
            ->groupBy('bookings.staff_id')
            ->get()
            ->keyBy('staff_id');

        $staffMembers->getCollection()->transform(
            static function (Staff $staff) use ($earningsSnapshot): Staff {
                $staffDailySnapshot = $earningsSnapshot->get($staff->id);

                $staff->setAttribute('daily_revenue', (float) ($staffDailySnapshot->revenue_total ?? 0));
                $staff->setAttribute('daily_paid_bookings', (int) ($staffDailySnapshot->paid_bookings ?? 0));

                return $staff;
            }
        );

        $stats = [
            'total_staff' => (clone $staffBaseQuery)->count(),
            'recent_30_days' => (clone $staffBaseQuery)
                ->whereDate('created_at', '>=', now()->subDays(30))
                ->count(),
            'staff_with_sales_for_date' => $earningsSnapshot
                ->filter(static fn ($snapshot): bool => (float) ($snapshot->revenue_total ?? 0) > 0)
                ->count(),
            'paid_bookings_for_date' => (int) $earningsSnapshot->sum(static fn ($snapshot): int => (int) ($snapshot->paid_bookings ?? 0)),
            'revenue_for_date' => (float) $earningsSnapshot->sum(static fn ($snapshot): float => (float) ($snapshot->revenue_total ?? 0)),
        ];

        $selectedDateLabel = $selectedDate->format('M d, Y');

        return view('admin.staff.index', compact('staffMembers', 'stats', 'selectedDateString', 'selectedDateLabel'));
    }

    public function create()
    {
        return view('admin.staff.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (AccountDirectory::emailExists((string) $value)) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        Staff::create([
            'name' => PersonName::combine($validated['first_name'], $validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'password' => Hash::make($validated['password']),
            'admin_id' => $request->user()->id,
        ]);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account created successfully.');
    }

    public function show(Request $request, Staff $staff)
    {
        $statusFilter = trim($request->string('status')->toString());
        $keyword = trim($request->string('q')->toString());
        $selectedDate = $this->resolveSelectedDate($request->string('earnings_date')->toString());
        $selectedDateString = $selectedDate->toDateString();

        $assignedBookingsBase = Booking::query()->where('staff_id', $staff->id);

        $bookingsQuery = (clone $assignedBookingsBase)
            ->with(['user', 'room', 'payment', 'guestDetail', 'assignedStaff'])
            ->when($statusFilter !== '', static function (Builder $query) use ($statusFilter): void {
                $query->where('status', $statusFilter);
            })
            ->when($keyword !== '', static function (Builder $query) use ($keyword): void {
                $query->where(function (Builder $nested) use ($keyword): void {
                    $nested->where('booking_id', $keyword)
                        ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('room', fn (Builder $roomQuery) => $roomQuery->where('name', 'like', '%'.$keyword.'%'))
                        ->orWhereHas('guestDetail', static function (Builder $detailQuery) use ($keyword): void {
                            $detailQuery->where('email', 'like', '%'.$keyword.'%')
                                ->orWhere('phone', 'like', '%'.$keyword.'%')
                                ->orWhere('first_name', 'like', '%'.$keyword.'%')
                                ->orWhere('last_name', 'like', '%'.$keyword.'%');
                        });
                });
            });

        $bookings = $bookingsQuery
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderBy('check_in')
            ->orderByDesc('booking_id')
            ->paginate(15)
            ->withQueryString();

        $customerCount = (clone $assignedBookingsBase)
            ->with('user:customer_id,email')
            ->get()
            ->map(static function (Booking $booking): string {
                $email = strtolower(trim($booking->guestEmail()));
                if ($email !== '' && $email !== '-') {
                    return 'email:'.$email;
                }

                $name = strtolower(trim($booking->guestName()));
                $phone = preg_replace('/\D+/', '', $booking->guestPhone()) ?? '';

                return 'name:'.$name.'|phone:'.$phone;
            })
            ->filter(static fn (string $identifier): bool => $identifier !== '' && $identifier !== 'name:|phone:')
            ->unique()
            ->count();

        $stats = [
            'assigned_total' => (clone $assignedBookingsBase)->count(),
            'pending_or_confirmed' => (clone $assignedBookingsBase)->whereIn('status', ['pending', 'confirmed'])->count(),
            'completed' => (clone $assignedBookingsBase)->where('status', 'completed')->count(),
            'cancelled' => (clone $assignedBookingsBase)->where('status', 'cancelled')->count(),
            'customers' => $customerCount,
            'paid_revenue_for_date' => (float) Payment::query()
                ->join('bookings', 'bookings.booking_id', '=', 'payments.booking_id')
                ->where('bookings.staff_id', $staff->id)
                ->where('payments.status', 'paid')
                ->whereDate('payments.paid_at', $selectedDateString)
                ->sum('payments.amount'),
            'paid_bookings_for_date' => (int) Payment::query()
                ->join('bookings', 'bookings.booking_id', '=', 'payments.booking_id')
                ->where('bookings.staff_id', $staff->id)
                ->where('payments.status', 'paid')
                ->whereDate('payments.paid_at', $selectedDateString)
                ->count('payments.payment_id'),
        ];

        $selectedDateLabel = $selectedDate->format('M d, Y');

        return view('admin.staff.show', compact('staff', 'bookings', 'stats', 'selectedDateString', 'selectedDateLabel'));
    }

    public function edit(Staff $staff)
    {
        return view('admin.staff.edit', compact('staff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'email' => [
                'required',
                'email',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($staff): void {
                    if (AccountDirectory::emailExists((string) $value, $staff)) {
                        $fail('This email is already registered.');
                    }
                },
            ],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'password' => ['nullable', 'confirmed', Password::min(8)],
        ]);

        $data = [
            'name' => PersonName::combine($validated['first_name'], $validated['last_name']),
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'admin_id' => $staff->admin_id ?? $request->user()->id,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $staff->update($data);

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account updated successfully.');
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();

        return redirect()
            ->route('admin.staff.index')
            ->with('status', 'Staff account removed successfully.');
    }
    private function resolveSelectedDate(string $rawDate): Carbon
    {
        $date = trim($rawDate);
        if ($date === '') {
            return now()->startOfDay();
        }

        try {
            return Carbon::parse($date)->startOfDay();
        } catch (\Throwable) {
            return now()->startOfDay();
        }
    }
}
