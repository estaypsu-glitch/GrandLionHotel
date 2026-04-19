<?php

namespace App\Http\Controllers;

use App\Mail\BookingCancelledMail;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\PricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService
    ) {
    }

    public function create(Request $request, Room $room)
    {
        $profileRedirect = $this->redirectIfProfileIncomplete();
        if ($profileRedirect) {
            return $profileRedirect;
        }

        if (!$room->is_available) {
            return redirect()
                ->route('rooms.show', $room)
                ->withErrors(['room' => 'This room is currently unavailable for booking.']);
        }

        $prefill = $this->resolveBookingPrefill($request, $room);

        return view('bookings.create', compact('room', 'prefill'));
    }

    public function store(StoreBookingRequest $request)
    {
        $profileRedirect = $this->redirectIfProfileIncomplete($request);
        if ($profileRedirect) {
            return $profileRedirect;
        }

        $room = Room::findOrFail($request->integer('room_id'));

        if (!$room->is_available) {
            return $this->respondWithBookingError($request, [
                'room_id' => 'Selected room is currently unavailable.',
            ]);
        }

        if ($request->integer('guests') > $room->capacity) {
            return $this->respondWithBookingError($request, [
                'guests' => 'Guest count exceeds room capacity (max '.$room->capacity.').',
            ]);
        }

        $checkIn = $request->string('check_in')->toString();
        $checkOut = $request->string('check_out')->toString();

        $isAvailable = $this->availabilityService->isRoomAvailable($room, $checkIn, $checkOut);

        if (!$isAvailable) {
            return $this->respondWithBookingError($request, [
                'room_id' => 'Selected room is not available for the given dates.',
            ]);
        }

        $discountIdPhotoPath = null;
        if ($request->hasFile('discount_id_photo')) {
            $discountIdPhotoPath = $request->file('discount_id_photo')->store('discount-ids', 'public');
        }

        $reservationMeta = array_filter([
            'first_name' => $request->input('first_name'),
            'last_name' => $request->input('last_name'),
            'street_address' => $request->input('street_address'),
            'street_address_line_2' => $request->input('street_address_line_2'),
            'guest_city' => $request->input('guest_city'),
            'state_province' => $request->input('state_province'),
            'postal_code' => $request->input('postal_code'),
            'contact_phone' => $request->input('contact_phone'),
            'contact_email' => $request->input('contact_email'),
            'adults' => $request->filled('adults') ? $request->integer('adults') : null,
            'kids' => $request->filled('kids') ? $request->integer('kids') : null,
            'payment_preference' => $request->input('payment_preference'),
            'discount_type' => $request->input('discount_type'),
            'discount_id' => $request->input('discount_id'),
            'discount_id_photo_path' => $discountIdPhotoPath,
        ], static fn (mixed $value): bool => !is_null($value) && $value !== '');

        try {
            $booking = DB::transaction(function () use (
                $request,
                $room,
                $reservationMeta,
                $checkIn,
                $checkOut
            ): Booking {
                $lockedRoom = Room::query()
                    ->whereKey($room->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedRoom->is_available) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room is currently unavailable.',
                    ]);
                }

                if ($request->integer('guests') > $lockedRoom->capacity) {
                    throw ValidationException::withMessages([
                        'guests' => 'Guest count exceeds room capacity (max '.$lockedRoom->capacity.').',
                    ]);
                }

                if (!$this->availabilityService->isRoomAvailable($lockedRoom, $checkIn, $checkOut)) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room is not available for the given dates.',
                    ]);
                }

                $totalPrice = $this->pricingService->calculateTotal(
                    $lockedRoom,
                    $checkIn,
                    $checkOut
                );

                $booking = Booking::create([
                    'customer_id' => $request->user()->id,
                    'room_id' => $lockedRoom->id,
                    'check_in' => $request->date('check_in'),
                    'check_out' => $request->date('check_out'),
                    'status' => 'pending',
                    'notes' => $request->input('notes'),
                ]);

                $guestPayload = $this->toGuestDetailPayload(array_merge($reservationMeta, [
                    'adults' => $request->filled('adults')
                        ? $request->integer('adults')
                        : $request->integer('guests'),
                    'kids' => $request->filled('kids') ? $request->integer('kids') : 0,
                ]));

                $booking->guestDetail()->create($guestPayload);
                $this->syncBookingDiscount($booking, $reservationMeta);

                $booking->payment()->create([
                    'amount' => $totalPrice,
                    'method' => 'pending',
                    'status' => 'unpaid',
                ]);

                return $booking;
            }, 3);
        } catch (ValidationException $exception) {
            return $this->respondWithBookingError($request, $exception->errors());
        } catch (InvalidArgumentException $exception) {
            return $this->respondWithBookingError($request, [
                'booking' => $exception->getMessage(),
            ]);
        }

        $isAwaitingConfirmation = $booking->status === 'pending';
        $nextRedirect = $isAwaitingConfirmation
            ? route('bookings.show', $booking)
            : route('payments.checkout', $booking);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Booking created successfully.',
                'booking_id' => $booking->id,
                'redirect' => $nextRedirect,
                'next_step' => $isAwaitingConfirmation
                    ? 'Await staff confirmation before payment.'
                    : 'Proceed to payment checkout.',
            ], 201);
        }

        if ($isAwaitingConfirmation) {
            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Booking request submitted. Wait for staff confirmation before payment.');
        }

        return redirect()->route('payments.checkout', $booking);
    }

    public function success(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->loadMissing(['room', 'payment', 'guestDetail']);
        $this->ensurePaidTransactionReference($booking);

        return view('bookings.success', compact('booking'));
    }

    public function myBookings(Request $request)
    {
        $bookings = $request->user()->bookings()->with(['room', 'payment', 'guestDetail'])->latest()->paginate(10);
        $bookings->getCollection()->each(function (Booking $booking): void {
            $this->ensurePaidTransactionReference($booking);
        });

        $stats = [
            'upcoming' => $request->user()->bookings()
                ->whereDate('check_in', '>=', Carbon::today())
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),
            'completed' => $request->user()->bookings()->where('status', 'completed')->count(),
            'cancelled' => $request->user()->bookings()->where('status', 'cancelled')->count(),
        ];

        return view('bookings.my-bookings', compact('bookings', 'stats'));
    }

    public function show(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->loadMissing(['room', 'payment', 'guestDetail']);
        $this->ensurePaidTransactionReference($booking);

        return view('bookings.show', compact('booking'));
    }

    public function cancel(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);

        if (!$booking->canBeCancelled()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['booking' => 'This booking can no longer be cancelled online.']);
        }

        $newPaymentStatus = $booking->payment_status === 'paid' ? 'refund_pending' : $booking->payment_status;

        $booking->update([
            'status' => 'cancelled',
        ]);

        if ($newPaymentStatus === 'refund_pending') {
            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => (float) ($booking->payment?->amount ?? $booking->total_price),
                    'method' => $booking->payment?->method ?? 'pending',
                    'status' => 'refund_pending',
                ]
            );
        }

        $booking->loadMissing(['user', 'room', 'payment', 'assignedStaff', 'guestDetail']);

        try {
            Mail::to($booking->user->email)->queue(new BookingCancelledMail($booking));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', $newPaymentStatus === 'refund_pending'
                ? 'Booking cancelled. Your refund is being processed.'
                : 'Booking cancelled successfully.');
    }

    public function requestReschedule(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);

        if (!$booking->canRequestReschedule()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['booking' => 'Schedule change requests are only available for confirmed unpaid bookings before check-in.']);
        }

        $validated = $request->validate([
            'requested_check_in' => ['required', 'date', 'after_or_equal:today'],
            'requested_check_out' => ['required', 'date', 'after:requested_check_in'],
            'reschedule_request_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (
            $booking->check_in->toDateString() === $validated['requested_check_in']
            && $booking->check_out->toDateString() === $validated['requested_check_out']
        ) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['requested_check_in' => 'Requested dates must be different from the current booking schedule.']);
        }

        if (!$this->availabilityService->isRoomAvailable(
            $booking->room,
            $validated['requested_check_in'],
            $validated['requested_check_out'],
            (int) $booking->id
        )) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['requested_check_in' => 'The selected new schedule is not available for this room.']);
        }

        $booking->update([
            'requested_check_in' => $validated['requested_check_in'],
            'requested_check_out' => $validated['requested_check_out'],
            'reschedule_request_notes' => filled($validated['reschedule_request_notes'] ?? null)
                ? $validated['reschedule_request_notes']
                : null,
            'reschedule_requested_at' => now(),
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Schedule change request sent. Staff will review your requested dates.');
    }

    public function requestRoomTransfer(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);

        if (!$booking->canRequestRoomTransfer()) {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['booking' => 'Room transfer requests are only available for active bookings before final check-out.']);
        }

        $validated = $request->validate([
            'room_transfer_request_reason' => ['required', 'string', 'max:1000'],
        ], [
            'room_transfer_request_reason.required' => 'Please provide your reason for requesting a room transfer.',
            'room_transfer_request_reason.max' => 'Room transfer reason must not exceed 1000 characters.',
        ]);

        $booking->update([
            'room_transfer_request_reason' => trim((string) $validated['room_transfer_request_reason']),
            'room_transfer_requested_at' => now(),
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Room transfer request sent. Staff will review your reason and available rooms.');
    }

    public function receipt(Booking $booking)
    {
        $this->authorizeOwner($booking);

        if ($booking->payment_status !== 'paid') {
            return redirect()
                ->route('bookings.show', $booking)
                ->withErrors(['booking' => 'Receipt is available only for paid bookings.']);
        }

        $booking->loadMissing(['user', 'room', 'payment', 'assignedStaff']);
        $this->ensurePaidTransactionReference($booking);

        $pdf = Pdf::loadView('receipts.booking', [
            'booking' => $booking,
        ])->setPaper('a4');

        return $pdf->download('booking-receipt-'.$booking->id.'.pdf');
    }

    private function authorizeOwner(Booking $booking): void
    {
        if (auth()->id() !== $booking->customer_id) {
            abort(403);
        }
    }

    private function ensurePaidTransactionReference(Booking $booking): void
    {
        if ($booking->payment_status !== 'paid' || !$booking->payment) {
            return;
        }

        if (blank($booking->payment->transaction_reference)) {
            $booking->payment->ensureTransactionReference((int) $booking->id);
            $booking->setRelation('payment', $booking->payment->fresh());
        }
    }

    private function redirectIfProfileIncomplete(?Request $request = null)
    {
        $user = auth()->user();

        if ($user && !$user->hasCompleteProfile()) {
            if ($request && $request->expectsJson()) {
                return response()->json([
                    'message' => 'Complete your profile details before creating a booking.',
                    'errors' => [
                        'profile' => ['Complete your profile details before creating a booking.'],
                    ],
                    'redirect' => route('profile.edit'),
                ], 422);
            }

            return redirect()
                ->route('profile.edit')
                ->withErrors(['profile' => 'Complete your profile details before creating a booking.']);
        }

        return null;
    }

    private function respondWithBookingError(Request $request, array $errors, int $status = 422)
    {
        if ($request->expectsJson()) {
            $normalizedErrors = collect($errors)
                ->map(static fn ($message): array => is_array($message) ? array_values($message) : [(string) $message])
                ->all();

            return response()->json([
                'message' => collect($normalizedErrors)->flatten()->first() ?? 'Unable to create booking.',
                'errors' => $normalizedErrors,
            ], $status);
        }

        return back()->withInput()->withErrors($errors);
    }

    private function resolveBookingPrefill(Request $request, Room $room): array
    {
        $today = Carbon::today();
        $minimumCheckIn = $today->toDateString();
        $minimumCheckOut = $today->copy()->addDay()->toDateString();
        $prefill = [
            'check_in' => $minimumCheckIn,
            'check_out' => $minimumCheckOut,
            'guests' => max(1, min($room->capacity, $request->integer('guests', 1))),
            'adults' => max(1, min($room->capacity, $request->integer('adults', $request->integer('guests', 1)))),
            'kids' => max(0, min($room->capacity, $request->integer('kids', 0))),
            'minimum_check_in' => $minimumCheckIn,
            'minimum_check_out' => $minimumCheckOut,
            'has_date_selection' => false,
            'date_selection_valid' => false,
            'unavailable_for_selected_dates' => false,
            'availability_message' => null,
        ];

        if (($prefill['adults'] + $prefill['kids']) > $room->capacity) {
            $prefill['kids'] = max(0, $room->capacity - $prefill['adults']);
        }

        $guestTotal = max(1, min($room->capacity, $prefill['adults'] + $prefill['kids']));
        $prefill['guests'] = $guestTotal;

        $checkInInput = trim((string) $request->input('check_in', ''));
        $checkOutInput = trim((string) $request->input('check_out', ''));

        if ($checkInInput === '' || $checkOutInput === '') {
            return $prefill;
        }

        $prefill['has_date_selection'] = true;

        try {
            $checkIn = Carbon::createFromFormat('Y-m-d', $checkInInput)->startOfDay();
            $checkOut = Carbon::createFromFormat('Y-m-d', $checkOutInput)->startOfDay();
        } catch (Throwable) {
            $prefill['availability_message'] = 'Selected stay dates are invalid. Please use the date picker.';

            return $prefill;
        }

        $prefill['check_in'] = $checkIn->toDateString();
        $prefill['check_out'] = $checkOut->toDateString();

        if ($checkIn->lessThan($today)) {
            $prefill['availability_message'] = 'Stay dates cannot be in the past.';

            return $prefill;
        }

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $prefill['availability_message'] = 'For nightly bookings, check-out must be at least one day after check-in.';
            return $prefill;
        }

        $prefill['date_selection_valid'] = true;
        $prefill['minimum_check_out'] = $checkIn->copy()->addDay()->toDateString();

        if (!$this->availabilityService->isRoomAvailable($room, $prefill['check_in'], $prefill['check_out'])) {
            $prefill['unavailable_for_selected_dates'] = true;
            $prefill['availability_message'] = 'This room is unavailable for your selected dates. Please choose a different schedule.';
        }

        return $prefill;
    }

    private function toGuestDetailPayload(array $reservationMeta): array
    {
        $firstName = trim((string) data_get($reservationMeta, 'first_name', ''));
        $lastName = trim((string) data_get($reservationMeta, 'last_name', ''));

        if ($firstName === '') {
            $firstName = trim((string) data_get($reservationMeta, 'customer_name', ''));
        }

        return array_filter([
            'first_name' => $firstName !== '' ? $firstName : null,
            'last_name' => $lastName !== '' ? $lastName : null,
            'email' => data_get($reservationMeta, 'contact_email', data_get($reservationMeta, 'customer_email')),
            'phone' => data_get($reservationMeta, 'contact_phone', data_get($reservationMeta, 'customer_phone')),
            'address_line' => data_get($reservationMeta, 'street_address', data_get($reservationMeta, 'address_line')),
            'street_address_line_2' => data_get($reservationMeta, 'street_address_line_2'),
            'city' => data_get($reservationMeta, 'guest_city', data_get($reservationMeta, 'city')),
            'province' => data_get($reservationMeta, 'state_province', data_get($reservationMeta, 'province')),
            'postal_code' => data_get($reservationMeta, 'postal_code'),
            'adults' => data_get($reservationMeta, 'adults'),
            'kids' => data_get($reservationMeta, 'kids'),
            'payment_preference' => data_get($reservationMeta, 'payment_preference'),
            'staff_id' => data_get($reservationMeta, 'staff_id'),
        ], static fn ($value): bool => !is_null($value) && $value !== '');
    }

    private function syncBookingDiscount(Booking $booking, array $reservationMeta): void
    {
        $discountType = strtolower(trim((string) data_get($reservationMeta, 'discount_type', '')));
        if ($discountType === '' || $discountType === 'none') {
            return;
        }

        $payload = array_filter([
            'discount_type' => $discountType,
            'discount_id' => data_get($reservationMeta, 'discount_id'),
            'discount_id_photo_path' => data_get($reservationMeta, 'discount_id_photo_path'),
        ], static fn (mixed $value): bool => !is_null($value) && $value !== '');

        if (Schema::hasTable('booking_discounts')) {
            $booking->discount()->updateOrCreate(
                ['booking_id' => $booking->id],
                $payload
            );

            return;
        }

        if (Schema::hasColumn('booking_guest_details', 'discount_type')) {
            $booking->guestDetail()->update($payload);
        }
    }
}
