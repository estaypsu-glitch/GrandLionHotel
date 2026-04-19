<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = trim($request->string('status')->toString());
        $paymentStatusFilter = trim($request->string('payment_status')->toString());
        $keyword = trim($request->string('q')->toString());

        $applyFilters = static function (Builder $query) use ($statusFilter, $paymentStatusFilter, $keyword): void {
            if ($statusFilter !== '') {
                $query->where('bookings.status', $statusFilter);
            }

            if ($paymentStatusFilter !== '') {
                $query->wherePaymentStatus($paymentStatusFilter);
            }

            if ($keyword === '') {
                return;
            }

            $query->where(function (Builder $nested) use ($keyword): void {
                $nested->where('bookings.booking_id', $keyword)
                    ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', '%'.$keyword.'%'))
                    ->orWhereHas('room', fn (Builder $roomQuery) => $roomQuery->where('name', 'like', '%'.$keyword.'%'));
            });
        };

        $bookingsQuery = Booking::query()
            ->with(['user', 'room', 'payment', 'guestDetail', 'assignedStaff']);
        $applyFilters($bookingsQuery);

        $bookings = $bookingsQuery
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counterQuery = Booking::query();
        $applyFilters($counterQuery);

        $summary = [
            'total' => (clone $counterQuery)->count(),
            'pending' => (clone $counterQuery)->where('bookings.status', 'pending')->count(),
            'confirmed' => (clone $counterQuery)->where('bookings.status', 'confirmed')->count(),
            'completed' => (clone $counterQuery)->where('bookings.status', 'completed')->count(),
            'unpaid_confirmed' => (clone $counterQuery)
                ->where('bookings.status', 'confirmed')
                ->wherePaymentStatus('unpaid')
                ->count(),
            'paid_revenue' => (clone $counterQuery)
                ->join('payments', 'payments.booking_id', '=', 'bookings.booking_id')
                ->where('payments.status', 'paid')
                ->sum('payments.amount'),
        ];

        return view('admin.bookings.index', compact('bookings', 'summary'));
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'room', 'payment.verifiedByStaff', 'guestDetail', 'assignedStaff']);
        $this->ensurePaidTransactionReference($booking);
        $staffMembers = Staff::query()
            ->orderBy('name')
            ->get(['staff_id', 'name', 'email']);

        return view('admin.bookings.show', compact('booking', 'staffMembers'));
    }

    public function assignStaff(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'staff_id' => [
                'nullable',
                'integer',
                Rule::exists('staff', 'staff_id'),
            ],
        ]);

        $assignedStaffId = $validated['staff_id'] ?? null;
        $assignedStaff = $assignedStaffId
            ? Staff::query()->whereKey($assignedStaffId)->first()
            : null;

        $booking->update([
            'staff_id' => $assignedStaffId,
        ]);

        $message = $assignedStaff
            ? 'Assigned staff updated to '.$assignedStaff->name.'.'
            : 'Assigned staff cleared for this booking.';

        return redirect()
            ->route('admin.bookings.show', $booking)
            ->with('status', $message);
    }

    public function updateStatus(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:pending,confirmed,cancelled,completed'],
        ]);

        $previousStatus = $booking->status;
        $newStatus = $validated['status'];

        if ($previousStatus === $newStatus) {
            return redirect()->route('admin.bookings.show', $booking)->with('status', 'Booking status is already up to date.');
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

        if ($newStatus === 'cancelled' && $booking->actual_check_in_at) {
            return back()->withErrors(['status' => 'Checked-in booking cannot be cancelled. Check-out instead.']);
        }

        $updatePayload = [
            'status' => $newStatus,
        ];

        if ($newStatus === 'cancelled') {
            $updatePayload['actual_check_in_at'] = null;
            $updatePayload['actual_check_out_at'] = null;
        }

        if ($newStatus === 'completed') {
            $updatePayload['actual_check_out_at'] = now();
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

        return redirect()->route('admin.bookings.show', $booking)->with('status', 'Booking status updated.');
    }

    public function approveOnlinePayment(Booking $booking)
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
            'staff_id' => null,
        ]);
        $payment->ensureTransactionReference((int) $booking->id);

        $booking->refresh()->load(['user', 'room', 'payment.verifiedByStaff', 'guestDetail', 'assignedStaff']);

        $this->sendBookingMail($booking, new BookingPaidMail($booking));

        return redirect()->route('admin.bookings.show', $booking)
            ->with('status', 'Online payment verified and marked as paid.');
    }

    public function rejectOnlinePayment(Booking $booking)
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
            'staff_id' => null,
            'transaction_reference' => null,
        ]);

        return redirect()->route('admin.bookings.show', $booking)
            ->with('status', 'Online payment proof rejected. Customer can submit again.');
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
}
