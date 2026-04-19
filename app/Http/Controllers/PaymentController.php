<?php

namespace App\Http\Controllers;

use App\Mail\BookingPaidMail;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function checkout(Booking $booking)
    {
        $this->authorizeOwner($booking);
        $booking->loadMissing(['room', 'guestDetail']);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.success', $booking)
                ->with('status', 'This booking is already paid.');
        }

        if ($booking->payment_status === 'pending_verification') {
            return redirect()->route('bookings.show', $booking)
                ->with('status', 'Your online payment proof is under review. Please wait for staff verification.');
        }

        if ($booking->status === 'pending') {
            return redirect()->route('bookings.show', $booking)->withErrors([
                'booking' => 'Your booking is still pending. Please wait for staff confirmation before payment.',
            ]);
        }

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return redirect()->route('bookings.my')->withErrors([
                'method' => 'This booking cannot be paid in its current status.',
            ]);
        }

        return view('payments.checkout', compact('booking'));
    }

    public function process(Request $request, Booking $booking)
    {
        $this->authorizeOwner($booking);

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.success', $booking)
                ->with('status', 'This booking is already paid.');
        }

        if ($booking->payment_status === 'pending_verification') {
            return back()->withErrors([
                'method' => 'Your payment proof is already submitted and is waiting for staff verification.',
            ]);
        }

        if ($booking->status === 'pending') {
            return back()->withErrors([
                'method' => 'Payment is disabled until staff confirms your booking.',
            ]);
        }

        if (in_array($booking->status, ['cancelled', 'completed'], true)) {
            return back()->withErrors([
                'method' => 'This booking cannot be paid in its current status.',
            ]);
        }

        $validated = $request->validate([
            'method' => ['required', Rule::in(Payment::allowedMethods())],
            'qr_reference' => ['nullable', 'string', 'max:80'],
            'customer_reference' => ['nullable', 'string', 'max:120', Rule::requiredIf(fn (): bool => Payment::isOnlineMethod((string) $request->input('method')))],
            'payment_proof' => ['nullable', 'image', 'max:5120', Rule::requiredIf(fn (): bool => Payment::isOnlineMethod((string) $request->input('method')))],
        ]);

        if ($validated['method'] === Payment::METHOD_CASH) {
            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_price,
                    'method' => Payment::METHOD_CASH,
                    'status' => 'unpaid',
                    'source' => 'cash_pending',
                    'qr_reference' => null,
                    'paid_at' => null,
                ]
            );

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Cash payment selected. Please pay at the front desk. Staff will confirm and mark this booking as paid once payment is received.');
        }

        if (Payment::isOnlineMethod($validated['method'])) {
            $existingPayment = $booking->payment()->first();
            $oldProofPath = trim((string) ($existingPayment?->payment_proof_path ?? ''));
            $proofPath = $request->file('payment_proof')->store('payment-proofs', 'public');

            if ($oldProofPath !== '' && $oldProofPath !== $proofPath) {
                Storage::disk('public')->delete($oldProofPath);
            }

            $booking->payment()->updateOrCreate(
                ['booking_id' => $booking->id],
                [
                    'amount' => $booking->total_price,
                    'method' => $validated['method'],
                    'status' => 'pending_verification',
                    'source' => 'online_submitted',
                    'qr_reference' => $validated['qr_reference'] ?? null,
                    'customer_reference' => $validated['customer_reference'] ?? null,
                    'payment_proof_path' => $proofPath,
                    'paid_at' => null,
                    'verified_at' => null,
                    'staff_id' => null,
                    'transaction_reference' => null,
                    'original_amount' => null,
                    'discount_rate' => null,
                    'discount_amount' => null,
                ]
            );

            return redirect()
                ->route('bookings.show', $booking)
                ->with('status', 'Payment proof submitted. Staff will review your online payment before marking it as paid.');
        }

        $this->paymentService->charge($booking, $validated['method'], [
            'qr_reference' => $validated['qr_reference'] ?? null,
        ]);
        $booking->refresh()->loadMissing(['user', 'room', 'payment']);

        try {
            Mail::to($booking->user->email)->queue(new BookingPaidMail($booking));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('bookings.success', $booking);
    }

    private function authorizeOwner(Booking $booking): void
    {
        if (auth()->id() !== $booking->customer_id) {
            abort(403);
        }
    }
}
