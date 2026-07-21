<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\RefundRequest;

class RefundRequestService
{
    public function createPendingForCancellation(Booking $booking, Payment $payment, array $attributes = []): RefundRequest
    {
        $reason = trim((string) ($attributes['reason'] ?? 'Booking cancelled and marked for refund processing.'));
        $notes = $this->buildRefundNotes($payment, (string) ($attributes['notes'] ?? ''));

        return RefundRequest::query()->firstOrCreate(
            [
                'payment_id' => $payment->getKey(),
                'status' => RefundRequest::STATUS_PENDING,
            ],
            [
                'reason' => $reason !== '' ? $reason : null,
                'notes' => $notes !== '' ? $notes : null,
                'requested_at' => $attributes['requested_at'] ?? now(),
            ]
        );
    }

    private function buildRefundNotes(Payment $payment, string $notes = ''): ?string
    {
        $segments = [];

        $trimmedNotes = trim($notes);
        if ($trimmedNotes !== '') {
            $segments[] = $trimmedNotes;
        }

        $segments[] = 'Refund must be returned using the original payment method: '
            .Payment::methodLabel((string) $payment->method)
            .'.';

        $segments = array_values(array_unique(array_filter($segments, static fn (string $segment): bool => trim($segment) !== '')));

        return empty($segments) ? null : implode(' ', $segments);
    }
}
