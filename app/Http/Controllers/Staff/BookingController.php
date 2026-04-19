<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Mail\BookingPaidMail;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Room;
use App\Services\AvailabilityService;
use App\Services\PaymentService;
use App\Services\PricingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Illuminate\Validation\ValidationException;
use Throwable;

class BookingController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService,
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService
    ) {
    }

    public function index(Request $request)
    {
        $queue = $request->string('queue')->toString();
        $today = Carbon::today()->toDateString();

        $statusFilter = trim($request->string('status')->toString());
        $paymentStatusFilter = trim($request->string('payment_status')->toString());
        $keyword = trim($request->string('q')->toString());

        $applyCommonFilters = static function (Builder $query) use ($statusFilter, $paymentStatusFilter, $keyword): void {
            if ($statusFilter !== '') {
                $query->where('status', $statusFilter);
            }

            if ($paymentStatusFilter !== '') {
                $query->wherePaymentStatus($paymentStatusFilter);
            }

            if ($keyword === '') {
                return;
            }

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
        };

        $applyQueueFilter = static function (Builder $query, string $targetQueue) use ($today): void {
            if ($targetQueue === 'pending') {
                $query->where('status', 'pending');
                return;
            }

            if ($targetQueue === 'arrivals_today') {
                $query->whereDate('check_in', $today)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->whereNull('actual_check_in_at');
                return;
            }

            if ($targetQueue === 'departures_today') {
                $query->whereDate('check_out', $today)
                    ->where('status', 'confirmed')
                    ->whereNotNull('actual_check_in_at')
                    ->whereNull('actual_check_out_at');
                return;
            }

            if ($targetQueue === 'in_house') {
                $query->where('status', 'confirmed')
                    ->whereNotNull('actual_check_in_at')
                    ->whereNull('actual_check_out_at');
            }
        };

        $bookingsQuery = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);

        $applyCommonFilters($bookingsQuery);
        $applyQueueFilter($bookingsQuery, $queue);

        $bookings = $bookingsQuery
            ->orderByRaw("CASE status WHEN 'pending' THEN 0 WHEN 'confirmed' THEN 1 ELSE 2 END")
            ->orderBy('check_in')
            ->orderByDesc('booking_id')
            ->paginate(20)
            ->withQueryString();

        $counterQuery = Booking::query();
        $applyCommonFilters($counterQuery);

        $queueKeys = ['', 'pending', 'arrivals_today', 'departures_today', 'in_house'];
        $queueCounts = [];
        foreach ($queueKeys as $queueKey) {
            $queueCountQuery = clone $counterQuery;
            $applyQueueFilter($queueCountQuery, $queueKey);
            $queueCounts[$queueKey] = $queueCountQuery->count();
        }

        $queueMeta = [
            'arrivals_today' => $queueCounts['arrivals_today'] ?? 0,
            'departures_today' => $queueCounts['departures_today'] ?? 0,
            'pending_approvals' => $queueCounts['pending'] ?? 0,
            'unpaid_confirmed' => (clone $counterQuery)
                ->where('status', 'confirmed')
                ->wherePaymentStatus('unpaid')
                ->count(),
        ];

        return view('staff.bookings.index', compact('bookings', 'queue', 'queueCounts', 'queueMeta'));
    }

    public function show(Request $request, Booking $booking)
    {
        $booking->load(['user', 'room.roomStatus', 'payment.verifiedByStaff', 'guestDetail', 'assignedStaff']);
        $this->ensurePaidTransactionReference($booking);
        $returnTo = $this->resolveSafeReturnTo($request->query('return_to'));
        $backUrl = $returnTo ?? route('staff.bookings.index');
        $currentStayTotal = $this->resolveBookingStayTotal($booking);
        $transferRooms = $booking->canBeTransferredByStaff()
            ? $this->resolveTransferRooms($booking, $currentStayTotal)
            : collect();
        $transferRequiresSameTotal = $this->transferNeedsMatchingTotal($booking);

        return view('staff.bookings.show', compact(
            'booking',
            'returnTo',
            'backUrl',
            'transferRooms',
            'currentStayTotal',
            'transferRequiresSameTotal'
        ));
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
        ]);

        $previousStatus = $booking->status;
        $newStatus = $validated['status'];

        if ($previousStatus === $newStatus) {
            return redirect()->route('staff.bookings.show', $booking)->with('status', 'Booking status is already up to date.');
        }

        if (!$booking->canTransitionTo($newStatus)) {
            return back()->withErrors([
                'status' => 'Invalid transition from '.ucfirst($previousStatus).' to '.ucfirst($newStatus).'.',
            ]);
        }

        if ($newStatus === 'completed' && !$booking->canBeCheckedOutByStaff()) {
            return back()->withErrors(['status' => 'Only checked-in confirmed bookings can be completed.']);
        }

        if ($newStatus === 'completed' && $booking->payment_status !== 'paid') {
            return back()->withErrors(['status' => 'Payment must be marked as paid before checkout completion.']);
        }

        $updatePayload = $this->withAssignedStaff($booking, [
            'status' => $newStatus,
        ]);

        if ($newStatus === 'completed') {
            $updatePayload['actual_check_out_at'] = now();
        }

        if ($newStatus === 'cancelled') {
            if ($booking->actual_check_in_at) {
                return back()->withErrors(['status' => 'Checked-in booking cannot be cancelled. Check-out instead.']);
            }

            $updatePayload['actual_check_in_at'] = null;
            $updatePayload['actual_check_out_at'] = null;
        }

        $booking->update($updatePayload);
        if ($newStatus === 'cancelled' && $booking->payment_status === 'paid') {
            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => (float) ($booking->payment?->amount ?? $booking->total_price),
                    'method' => $booking->payment?->method ?? 'pending',
                    'status' => 'refund_pending',
                ]
            );
        }

        $booking->loadMissing(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);

        if ($previousStatus !== $newStatus) {
            if ($newStatus === 'confirmed') {
                $this->sendBookingMail($booking, new BookingConfirmedMail($booking));
            } elseif ($newStatus === 'cancelled') {
                $this->sendBookingMail($booking, new BookingCancelledMail($booking));
            }
        }

        return $this->redirectAfterBookingAction($request, $booking, 'Booking status updated.');
    }

    public function confirm(Request $request, Booking $booking)
    {
        if (!$booking->canBeConfirmedByStaff()) {
            return back()->withErrors(['booking' => 'Only pending bookings can be confirmed.']);
        }

        $booking->update($this->withAssignedStaff($booking, [
            'status' => 'confirmed',
        ]));
        $booking->loadMissing(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);

        $this->sendBookingMail($booking, new BookingConfirmedMail($booking));

        return $this->redirectAfterBookingAction($request, $booking, 'Booking confirmed successfully.');
    }

    public function cancel(Request $request, Booking $booking)
    {
        if (!in_array($booking->status, ['pending', 'confirmed'], true)) {
            return back()->withErrors(['booking' => 'This booking cannot be cancelled in its current state.']);
        }

        if ($booking->actual_check_in_at) {
            return back()->withErrors(['booking' => 'Checked-in booking cannot be cancelled. Check-out instead.']);
        }

        $newPaymentStatus = $booking->payment_status === 'paid' ? 'refund_pending' : $booking->payment_status;

        $booking->update($this->withAssignedStaff($booking, [
            'status' => 'cancelled',
            'actual_check_in_at' => null,
            'actual_check_out_at' => null,
        ]));

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

        $booking->loadMissing(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);

        $this->sendBookingMail($booking, new BookingCancelledMail($booking));

        return $this->redirectAfterBookingAction(
            $request,
            $booking,
            $newPaymentStatus === 'refund_pending'
                ? 'Booking cancelled and refund marked for processing.'
                : 'Booking cancelled successfully.'
        );
    }

    public function checkIn(Request $request, Booking $booking)
    {
        if (!$booking->canBeCheckedInByStaff()) {
            return back()->withErrors(['booking' => 'Only confirmed bookings can be checked in.']);
        }

        $validated = $request->validate([
            'actual_check_in_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $actualCheckInAt = $this->resolveStaffActionTimestamp(
            $validated['actual_check_in_at'] ?? null,
            'actual_check_in_at'
        );

        if ($actualCheckInAt->isFuture()) {
            return back()
                ->withInput()
                ->withErrors(['actual_check_in_at' => 'Actual check-in time cannot be in the future.']);
        }

        if ($actualCheckInAt->lt($booking->check_in->copy()->startOfDay())) {
            return back()
                ->withInput()
                ->withErrors(['actual_check_in_at' => 'Actual check-in time cannot be earlier than the booked check-in date.']);
        }

        $booking->update($this->withAssignedStaff($booking, [
            'actual_check_in_at' => $actualCheckInAt,
        ]));

        return $this->redirectAfterBookingAction($request, $booking, 'Guest checked in successfully.');
    }

    public function checkOut(Request $request, Booking $booking)
    {
        if (!$booking->canBeCheckedOutByStaff()) {
            return back()->withErrors(['booking' => 'Only checked-in confirmed bookings can be checked out.']);
        }

        if ($booking->payment_status !== 'paid') {
            return back()->withErrors(['booking' => 'Guest payment must be completed before checkout.']);
        }

        $validated = $request->validate([
            'actual_check_out_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
        ]);

        $actualCheckOutAt = $this->resolveStaffActionTimestamp(
            $validated['actual_check_out_at'] ?? null,
            'actual_check_out_at'
        );

        if ($actualCheckOutAt->isFuture()) {
            return back()
                ->withInput()
                ->withErrors(['actual_check_out_at' => 'Actual check-out time cannot be in the future.']);
        }

        if ($booking->actual_check_in_at && $actualCheckOutAt->lessThanOrEqualTo($booking->actual_check_in_at)) {
            return back()
                ->withInput()
                ->withErrors(['actual_check_out_at' => 'Actual check-out time must be later than the recorded check-in time.']);
        }

        $booking->update($this->withAssignedStaff($booking, [
            'status' => 'completed',
            'actual_check_out_at' => $actualCheckOutAt,
        ]));

        return $this->redirectAfterBookingAction($request, $booking, 'Guest checked out and booking marked completed.');
    }

    public function arrivals()
    {
        $today = Carbon::today()->toDateString();

        $arrivals = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail', 'assignedStaff'])
            ->whereDate('check_in', $today)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereNull('actual_check_in_at')
            ->orderBy('check_in')
            ->orderBy('booking_id')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total_arrivals' => Booking::whereDate('check_in', $today)
                ->whereIn('status', ['pending', 'confirmed'])
                ->whereNull('actual_check_in_at')
                ->count(),
            'checked_in' => Booking::whereDate('check_in', $today)
                ->whereIn('status', ['confirmed'])
                ->whereNotNull('actual_check_in_at')
                ->count(),
            'pending' => Booking::whereDate('check_in', $today)
                ->where('status', 'pending')
                ->whereNull('actual_check_in_at')
                ->count(),
        ];

        return view('staff.arrivals', compact('arrivals', 'stats'));
    }

    public function updateStaffNotes(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $booking->update($this->withAssignedStaff($booking, [
            'staff_notes' => $validated['staff_notes'] ?? null,
        ]));

        return redirect()->route('staff.bookings.show', $booking)->with('status', 'Internal staff notes saved.');
    }

    public function transferRoom(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:rooms,room_id'],
        ]);

        try {
            DB::transaction(function () use ($booking, $validated): void {
                $lockedBooking = Booking::query()
                    ->with(['room.roomStatus', 'payment', 'guestDetail'])
                    ->lockForUpdate()
                    ->findOrFail($booking->id);

                if (!$lockedBooking->canBeTransferredByStaff()) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Only pending or confirmed bookings can be moved to another room.',
                    ]);
                }

                $targetRoomId = (int) $validated['room_id'];
                if ($targetRoomId === (int) $lockedBooking->room_id) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Select a different room for this transfer.',
                    ]);
                }

                $targetRoom = Room::query()
                    ->with('roomStatus')
                    ->whereKey($targetRoomId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$targetRoom->is_available) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room is currently unavailable.',
                    ]);
                }

                if ((int) $lockedBooking->guests > (int) $targetRoom->capacity) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room cannot fit the guest count for this booking.',
                    ]);
                }

                $checkIn = $lockedBooking->check_in->toDateString();
                $checkOut = $lockedBooking->check_out->toDateString();

                if (!$this->availabilityService->isRoomAvailable($targetRoom, $checkIn, $checkOut, (int) $lockedBooking->id)) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room is not available for the guest stay dates.',
                    ]);
                }

                $currentStayTotal = $this->resolveBookingStayTotal($lockedBooking);
                $newStayTotal = $this->pricingService->calculateTotal($targetRoom, $checkIn, $checkOut);

                if ($this->transferNeedsMatchingTotal($lockedBooking) && !$this->amountsMatch($currentStayTotal, $newStayTotal)) {
                    throw ValidationException::withMessages([
                        'room_id' => 'This booking already has a settled or submitted payment. Choose a room with the same total amount.',
                    ]);
                }

                $lockedBooking->update($this->withAssignedStaff($lockedBooking, [
                    'room_id' => $targetRoom->id,
                    'room_transfer_request_reason' => null,
                    'room_transfer_requested_at' => null,
                ]));

                if ($lockedBooking->payment && $lockedBooking->payment_status === 'unpaid') {
                    $lockedBooking->payment->update([
                        'amount' => $newStayTotal,
                        'original_amount' => null,
                        'discount_rate' => null,
                        'discount_amount' => null,
                    ]);
                }
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['room_id' => $exception->getMessage()])->withInput();
        }

        return $this->redirectAfterBookingAction($request, $booking, 'Booking room updated successfully.');
    }

    public function recordPayment(Request $request, Booking $booking)
    {
        if ($booking->payment_status === 'paid') {
            return back()->withErrors(['payment' => 'Payment is already recorded as paid.']);
        }

        if ($booking->payment_status === 'pending_verification') {
            return back()->withErrors(['payment' => 'An online payment proof is waiting for review. Approve or reject it first.']);
        }

        if ($booking->status === 'cancelled') {
            return back()->withErrors(['payment' => 'Cancelled bookings cannot accept payment.']);
        }

        if ($booking->status === 'pending') {
            return back()->withErrors(['payment' => 'Confirm booking first before collecting payment.']);
        }

        if ($booking->status === 'completed') {
            return back()->withErrors(['payment' => 'Booking is already completed.']);
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(Payment::allowedMethods())],
            'qr_reference' => ['nullable', 'string', 'max:80'],
            'discount_type' => ['nullable', 'in:none,pwd,senior'],
            'discount_id' => ['nullable', 'string', 'max:80'],
        ]);

        $discountType = $validated['discount_type'] ?? 'none';
        $discountRate = match ($discountType) {
            'pwd', 'senior' => 0.20,
            default => 0.0,
        };

        $originalAmount = round((float) $booking->total_price, 2);
        $discountAmount = round($originalAmount * $discountRate, 2);
        $payableAmount = round(max(0, $originalAmount - $discountAmount), 2);
        $uploadedDiscountProofPath = trim((string) data_get($booking->reservation_meta, 'discount_id_photo_path', ''));

        if ($discountRate > 0 && blank($validated['discount_id'] ?? null) && $uploadedDiscountProofPath === '') {
            return back()->withErrors([
                'discount_id' => 'Provide a discount ID number or upload a discount ID photo before applying PWD/Senior discount.',
            ])->withInput();
        }

        $this->syncBookingDiscount(
            $booking,
            $discountRate > 0 ? $discountType : null,
            $discountRate > 0 ? ($validated['discount_id'] ?? null) : null,
            $discountRate > 0 ? $uploadedDiscountProofPath : null
        );

        $this->paymentService->charge($booking, $validated['method'], [
            'amount' => $payableAmount,
            'qr_reference' => $validated['qr_reference'] ?? null,
            'original_amount' => $originalAmount,
            'discount_rate' => $discountRate > 0 ? $discountRate : null,
            'discount_amount' => $discountRate > 0 ? $discountAmount : null,
        ]);
        $booking->refresh();
        $booking->update($this->withAssignedStaff($booking));
        $booking->loadMissing(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);

        $this->sendBookingMail($booking, new BookingPaidMail($booking));

        return $this->redirectAfterBookingAction($request, $booking, 'Payment recorded successfully.');
    }

    public function approveOnlinePayment(Request $request, Booking $booking)
    {
        $booking->loadMissing(['payment']);
        $payment = $booking->payment;

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['payment' => 'This booking can no longer accept payment verification updates.']);
        }

        if (!$payment || $payment->status !== 'pending_verification') {
            return back()->withErrors(['payment' => 'Only submitted online payments can be approved.']);
        }

        if (!Payment::isOnlineMethod((string) $payment->method)) {
            return back()->withErrors(['payment' => 'Only InstaPay or Credit/Debit Card submissions can be approved here.']);
        }

        $payment->update([
            'status' => 'paid',
            'source' => 'online_verified',
            'paid_at' => now(),
            'verified_at' => now(),
            'staff_id' => auth()->id(),
        ]);
        $payment->ensureTransactionReference((int) $booking->id);

        $booking->update($this->withAssignedStaff($booking));
        $booking->refresh()->load(['user', 'room', 'payment.verifiedByStaff', 'guestDetail', 'assignedStaff']);

        $this->sendBookingMail($booking, new BookingPaidMail($booking));

        return $this->redirectAfterBookingAction($request, $booking, 'Online payment verified and marked as paid.');
    }

    public function rejectOnlinePayment(Request $request, Booking $booking)
    {
        $booking->loadMissing(['payment']);
        $payment = $booking->payment;

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors(['payment' => 'This booking can no longer accept payment verification updates.']);
        }

        if (!$payment || $payment->status !== 'pending_verification') {
            return back()->withErrors(['payment' => 'Only submitted online payments can be rejected.']);
        }

        if (!Payment::isOnlineMethod((string) $payment->method)) {
            return back()->withErrors(['payment' => 'Only InstaPay or Credit/Debit Card submissions can be rejected here.']);
        }

        $payment->update([
            'status' => 'unpaid',
            'source' => 'online_rejected',
            'paid_at' => null,
            'verified_at' => now(),
            'staff_id' => auth()->id(),
            'transaction_reference' => null,
        ]);

        $booking->update($this->withAssignedStaff($booking));

        return $this->redirectAfterBookingAction($request, $booking, 'Online payment proof rejected. Customer can submit again.');
    }

    public function reschedule(Request $request, Booking $booking)
    {
        if (!$booking->canBeRescheduledByStaff()) {
            return back()->withErrors(['booking' => 'Only confirmed bookings before check-out can be rescheduled by staff.']);
        }

        $validated = $request->validate([
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);

        if (
            $booking->check_in->toDateString() === $validated['check_in']
            && $booking->check_out->toDateString() === $validated['check_out']
        ) {
            return back()
                ->withInput()
                ->withErrors(['check_in' => 'The new schedule must be different from the current booking dates.']);
        }

        if (!$this->availabilityService->isRoomAvailable(
            $booking->room,
            $validated['check_in'],
            $validated['check_out'],
            (int) $booking->id
        )) {
            return back()
                ->withInput()
                ->withErrors(['check_in' => 'The selected new schedule is not available for this room.']);
        }

        $newTotal = $this->pricingService->calculateTotal(
            $booking->room,
            $validated['check_in'],
            $validated['check_out']
        );

        $booking->update($this->withAssignedStaff($booking, [
            'check_in' => $validated['check_in'],
            'check_out' => $validated['check_out'],
            'requested_check_in' => null,
            'requested_check_out' => null,
            'reschedule_request_notes' => null,
            'reschedule_requested_at' => null,
        ]));

        if ($booking->payment && $booking->payment->status !== 'paid') {
            $booking->payment->update([
                'amount' => $newTotal,
                'original_amount' => null,
                'discount_rate' => null,
                'discount_amount' => null,
            ]);
        }

        return $this->redirectAfterBookingAction($request, $booking, 'Booking schedule updated successfully.');
    }

    public function applyRescheduleRequest(Request $request, Booking $booking)
    {
        if (!$booking->hasPendingRescheduleRequest()) {
            return back()->withErrors(['booking' => 'There is no pending schedule change request for this booking.']);
        }

        if (!$booking->canBeRescheduledByStaff()) {
            return back()->withErrors(['booking' => 'Only confirmed bookings before check-out can be rescheduled here.']);
        }

        $requestedCheckIn = $booking->requested_check_in?->toDateString();
        $requestedCheckOut = $booking->requested_check_out?->toDateString();

        if (!$requestedCheckIn || !$requestedCheckOut) {
            return back()->withErrors(['booking' => 'Requested schedule is incomplete.']);
        }

        if (!$this->availabilityService->isRoomAvailable(
            $booking->room,
            $requestedCheckIn,
            $requestedCheckOut,
            (int) $booking->id
        )) {
            return back()->withErrors(['booking' => 'Requested schedule is no longer available for this room.']);
        }

        $newTotal = $this->pricingService->calculateTotal(
            $booking->room,
            $requestedCheckIn,
            $requestedCheckOut
        );

        $booking->update($this->withAssignedStaff($booking, [
            'check_in' => $requestedCheckIn,
            'check_out' => $requestedCheckOut,
            'requested_check_in' => null,
            'requested_check_out' => null,
            'reschedule_request_notes' => null,
            'reschedule_requested_at' => null,
        ]));

        if ($booking->payment && $booking->payment->status !== 'paid') {
            $booking->payment->update([
                'amount' => $newTotal,
                'original_amount' => null,
                'discount_rate' => null,
                'discount_amount' => null,
            ]);
        }

        return $this->redirectAfterBookingAction($request, $booking, 'Requested schedule applied successfully.');
    }

    public function declineRescheduleRequest(Request $request, Booking $booking)
    {
        if (!$booking->hasPendingRescheduleRequest()) {
            return back()->withErrors(['booking' => 'There is no pending schedule change request for this booking.']);
        }

        $booking->update($this->withAssignedStaff($booking, [
            'requested_check_in' => null,
            'requested_check_out' => null,
            'reschedule_request_notes' => null,
            'reschedule_requested_at' => null,
        ]));

        return $this->redirectAfterBookingAction($request, $booking, 'Schedule change request declined.');
    }

    public function declineRoomTransferRequest(Request $request, Booking $booking)
    {
        if (!$booking->hasPendingRoomTransferRequest()) {
            return back()->withErrors(['booking' => 'There is no pending room transfer request for this booking.']);
        }

        $booking->update($this->withAssignedStaff($booking, [
            'room_transfer_request_reason' => null,
            'room_transfer_requested_at' => null,
        ]));

        return $this->redirectAfterBookingAction($request, $booking, 'Room transfer request declined.');
    }

    public function receipt(Booking $booking)
    {
        if ($booking->payment_status !== 'paid') {
            return redirect()
                ->route('staff.bookings.show', $booking)
                ->withErrors(['booking' => 'Receipt is available only for paid bookings.']);
        }

        $booking->loadMissing(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);
        $this->ensurePaidTransactionReference($booking);

        $pdf = Pdf::loadView('receipts.booking', [
            'booking' => $booking,
        ])->setPaper('a4');

        return $pdf->download('booking-receipt-'.$booking->id.'.pdf');
    }

    private function withAssignedStaff(Booking $booking, array $attributes = []): array
    {
        return array_merge($attributes, [
            'staff_id' => $booking->staff_id ?? auth()->id(),
        ]);
    }

    private function redirectAfterBookingAction(Request $request, Booking $booking, string $message)
    {
        $returnTo = $this->resolveSafeReturnTo($request->input('return_to'));

        if ($returnTo !== null) {
            return redirect()->to($returnTo)->with('status', $message);
        }

        return redirect()->route('staff.bookings.show', $booking)->with('status', $message);
    }

    private function resolveSafeReturnTo(?string $value): ?string
    {
        $candidate = trim((string) $value);
        if ($candidate === '') {
            return null;
        }

        $parts = parse_url($candidate);
        if ($parts === false || isset($parts['scheme']) || isset($parts['host'])) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '');
        if (!str_starts_with($path, '/staff/')) {
            return null;
        }

        return $candidate;
    }

    private function parseStaffLoggedTimestamp(string $value, string $field): Carbon
    {
        try {
            return Carbon::createFromFormat('Y-m-d\TH:i', trim($value), config('app.timezone'))
                ->setSecond(0);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                $field => 'Enter a valid date and time.',
            ]);
        }
    }

    private function resolveStaffActionTimestamp(?string $value, string $field): Carbon
    {
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return now()->copy()->setSecond(0);
        }

        return $this->parseStaffLoggedTimestamp($trimmed, $field);
    }

    private function resolveTransferRooms(Booking $booking, float $currentStayTotal): Collection
    {
        $requiresSameTotal = $this->transferNeedsMatchingTotal($booking);
        $checkIn = $booking->check_in->toDateString();
        $checkOut = $booking->check_out->toDateString();

        return Room::query()
            ->with('roomStatus')
            ->availableForBooking()
            ->whereKeyNot($booking->room_id)
            ->where('capacity', '>=', $booking->guests)
            ->orderBy('name')
            ->get()
            ->map(function (Room $room) use ($booking, $checkIn, $checkOut): Room {
                $stayTotal = $this->pricingService->calculateTotal($room, $checkIn, $checkOut);
                $room->setAttribute('transfer_stay_total', $stayTotal);
                $room->setAttribute(
                    'transfer_is_available',
                    $this->availabilityService->isRoomAvailable($room, $checkIn, $checkOut, (int) $booking->id)
                );

                return $room;
            })
            ->filter(function (Room $room) use ($requiresSameTotal, $currentStayTotal): bool {
                if (!$room->transfer_is_available) {
                    return false;
                }

                if (!$requiresSameTotal) {
                    return true;
                }

                return $this->amountsMatch((float) $room->transfer_stay_total, $currentStayTotal);
            })
            ->values();
    }

    private function transferNeedsMatchingTotal(Booking $booking): bool
    {
        return in_array($booking->payment_status, ['paid', 'pending_verification'], true);
    }

    private function resolveBookingStayTotal(Booking $booking): float
    {
        return $this->pricingService->calculateTotal(
            $booking->room,
            $booking->check_in->toDateString(),
            $booking->check_out->toDateString()
        );
    }

    private function amountsMatch(float $left, float $right): bool
    {
        return abs(round($left, 2) - round($right, 2)) < 0.01;
    }

    private function sendBookingMail(Booking $booking, Mailable $mailable): void
    {
        $email = $booking->guestEmail();
        if (blank($email) || $email === '-') {
            return;
        }

        try {
            Mail::to($email)->queue($mailable);
        } catch (Throwable $exception) {
            report($exception);
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

    private function syncBookingDiscount(
        Booking $booking,
        ?string $discountType,
        ?string $discountId = null,
        ?string $discountProofPath = null
    ): void {
        $normalizedType = strtolower(trim((string) $discountType));
        if ($normalizedType === '' || $normalizedType === 'none') {
            return;
        }

        $payload = array_filter([
            'discount_type' => $normalizedType,
            'discount_id' => $discountId,
            'discount_id_photo_path' => $discountProofPath,
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

    public function create()
    {
        $rooms = Room::query()->availableForBooking()
            ->orderBy('name')
            ->get();

        return view('staff.bookings.create', compact('rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email'],
            'customer_phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\\-\\s]{7,30}$/'],
            'room_id' => ['required', 'exists:rooms,room_id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after_or_equal:check_in'],
            'guests' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'payment_preference' => ['nullable', Rule::in(Payment::allowedMethods())],
        ], [
            'customer_phone.regex' => 'Phone must contain only digits, spaces, +, (), or -.',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $checkInDate = Carbon::parse($validated['check_in'])->startOfDay();
        $checkOutDate = Carbon::parse($validated['check_out'])->startOfDay();

        if ($checkOutDate->lessThanOrEqualTo($checkInDate)) {
            return back()->withErrors([
                'check_out' => 'For nightly bookings, check-out must be at least one day after check-in.',
            ])->withInput();
        }

        if ($validated['guests'] > $room->capacity) {
            return back()->withErrors(['guests' => 'Guest count exceeds room capacity (max ' . $room->capacity . ').'])->withInput();
        }

        if (!$this->availabilityService->isRoomAvailable($room, $validated['check_in'], $validated['check_out'])) {
            return back()->withErrors([
                'room_id' => 'Room not available for selected dates.',
            ])->withInput();
        }

        try {
            $booking = DB::transaction(function () use ($validated, $room): Booking {
                $lockedRoom = Room::query()
                    ->whereKey($room->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (!$lockedRoom->is_available) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Selected room is currently unavailable.',
                    ]);
                }

                if ((int) $validated['guests'] > (int) $lockedRoom->capacity) {
                    throw ValidationException::withMessages([
                        'guests' => 'Guest count exceeds room capacity (max '.$lockedRoom->capacity.').',
                    ]);
                }

                if (!$this->availabilityService->isRoomAvailable($lockedRoom, $validated['check_in'], $validated['check_out'])) {
                    throw ValidationException::withMessages([
                        'room_id' => 'Room not available for selected dates.',
                    ]);
                }

                $totalPrice = $this->pricingService->calculateTotal(
                    $lockedRoom,
                    $validated['check_in'],
                    $validated['check_out']
                );

                $booking = Booking::create([
                    'customer_id' => null,
                    'room_id' => $lockedRoom->id,
                    'check_in' => $validated['check_in'],
                    'check_out' => $validated['check_out'],
                    'status' => 'pending',
                    'notes' => $validated['notes'] ?? null,
                    'staff_id' => auth()->id(),
                ]);

                $customerName = trim((string) $validated['customer_name']);
                $nameParts = preg_split('/\s+/', $customerName, 2);
                $firstName = trim((string) ($nameParts[0] ?? ''));
                $lastName = trim((string) ($nameParts[1] ?? ''));

                $booking->guestDetail()->create(array_filter([
                    'first_name' => $firstName !== '' ? $firstName : $customerName,
                    'last_name' => $lastName !== '' ? $lastName : null,
                    'email' => $validated['customer_email'] ?? null,
                    'phone' => $validated['customer_phone'] ?? null,
                    'adults' => (int) $validated['guests'],
                    'kids' => 0,
                    'payment_preference' => $validated['payment_preference'] ?? null,
                    'staff_id' => auth()->id(),
                ], static fn (mixed $value): bool => !is_null($value) && $value !== ''));

                $booking->payment()->create([
                    'amount' => $totalPrice,
                    'method' => 'pending',
                    'status' => 'unpaid',
                ]);

                return $booking;
            }, 3);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['booking' => $exception->getMessage()])->withInput();
        }

        return redirect()->route('staff.bookings.show', $booking)
            ->with('status', 'Walk-in booking created successfully! Proceed to confirm and collect payment.');
    }
}
