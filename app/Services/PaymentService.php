<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function charge(Booking $booking, string $method = 'cash', array $meta = []): Payment
    {
        return DB::transaction(function () use ($booking, $method, $meta): Payment {
            $lockedBooking = Booking::query()
                ->whereKey($booking->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $payment = Payment::query()
                ->where('booking_id', $lockedBooking->getKey())
                ->lockForUpdate()
                ->first();

            if ($payment && $payment->status === 'paid') {
                if (blank($payment->transaction_reference)) {
                    $payment->update([
                        'transaction_reference' => $this->generateUniqueTransactionReference(
                            (int) $lockedBooking->getKey(),
                            (int) $payment->getKey()
                        ),
                    ]);
                }

                return $payment->fresh();
            }

            $bookingAmount = $payment ? (float) $payment->amount : (float) $lockedBooking->total_price;
            if ($bookingAmount <= 0) {
                $bookingAmount = (float) $lockedBooking->total_price;
            }
            $requestedAmount = $meta['amount'] ?? null;
            $chargeAmount = $bookingAmount;

            if (is_numeric($requestedAmount)) {
                $chargeAmount = max(0.0, min($bookingAmount, round((float) $requestedAmount, 2)));
                unset($meta['amount']);
            }

            $source = trim((string) ($meta['source'] ?? ''));
            if ($source === '') {
                $source = in_array($method, ['gcash', 'paymaya'], true) ? 'online_qr' : 'manual';
            }

            $transactionReference = trim((string) ($payment?->transaction_reference ?? ''));
            if ($transactionReference === '') {
                $transactionReference = $this->generateUniqueTransactionReference(
                    (int) $lockedBooking->getKey(),
                    $payment?->getKey() ? (int) $payment->getKey() : null
                );
            }

            $paymentPayload = array_filter([
                'amount' => $chargeAmount,
                'method' => $method,
                'status' => 'paid',
                'paid_at' => now(),
                'source' => $source,
                'qr_reference' => data_get($meta, 'qr_reference'),
                'original_amount' => is_numeric($meta['original_amount'] ?? null) ? round((float) $meta['original_amount'], 2) : null,
                'discount_rate' => is_numeric($meta['discount_rate'] ?? null) ? round((float) $meta['discount_rate'], 4) : null,
                'discount_amount' => is_numeric($meta['discount_amount'] ?? null) ? round((float) $meta['discount_amount'], 2) : null,
                'transaction_reference' => $transactionReference,
            ], static fn ($value): bool => !is_null($value) && $value !== '');

            if ($payment) {
                $payment->update($paymentPayload);
            } else {
                $payment = $lockedBooking->payment()->create($paymentPayload);
            }

            $bookingStatus = $lockedBooking->status;
            if ($bookingStatus === 'pending') {
                $bookingStatus = 'confirmed';
            }

            $lockedBooking->update([
                'status' => $bookingStatus,
            ]);

            return $payment->fresh();
        }, 3);
    }

    private function generateUniqueTransactionReference(int $bookingId, ?int $exceptPaymentId = null): string
    {
        do {
            $candidate = Payment::generateTransactionReference($bookingId);

            $query = Payment::query()->where('transaction_reference', $candidate);
            if (!is_null($exceptPaymentId)) {
                $query->where('payment_id', '!=', $exceptPaymentId);
            }
        } while ($query->exists());

        return $candidate;
    }
}
