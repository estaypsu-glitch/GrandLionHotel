@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    @php
        $preferredMethod = data_get($booking->reservation_meta, 'payment_preference');
        $legacyMethodMap = [
            'bank_transfer' => 'instapay',
            'gcash' => 'instapay',
            'paymaya' => 'instapay',
        ];
        $selectedMethod = old('method', $preferredMethod);
        $selectedMethod = $legacyMethodMap[$selectedMethod] ?? $selectedMethod;
        $onlineMethods = ['instapay', 'credit_debit_card'];
        $billedUnits = max(1, $booking->nights());
        $subtotalAmount = (float) $booking->total_price;
        $unitRate = $billedUnits > 0 ? round($subtotalAmount / $billedUnits, 2) : $subtotalAmount;
    @endphp

    <div class="row justify-content-center">
        <div class="col-lg-7">
            <section class="soft-card p-4 p-lg-5">
                <p class="ta-eyebrow mb-1">Secure Payment</p>
                <h1 class="h3 mb-3">Checkout for Booking #{{ $booking->id }}</h1>

                <div class="bg-light rounded-3 p-3 mb-4">
                    <p class="mb-1"><strong>Room:</strong> {{ $booking->room->name ?? 'N/A' }}</p>
                    <p class="mb-1"><strong>Stay:</strong> {{ $booking->check_in->format('M d, Y') }} - {{ $booking->check_out->format('M d, Y') }}</p>
                    <p class="mb-1"><strong>Stay Type:</strong> Nightly</p>
                    <hr class="my-2">
                    <p class="mb-1"><strong>Nightly rate:</strong> &#8369;{{ number_format($unitRate, 2) }}</p>
                    <p class="mb-1"><strong>Nights:</strong> {{ $billedUnits }}</p>
                    <p class="mb-1"><strong>Calculation:</strong> &#8369;{{ number_format($unitRate, 2) }} x {{ $billedUnits }} night{{ $billedUnits === 1 ? '' : 's' }}</p>
                    <p class="mb-0"><strong>Amount due:</strong> &#8369;{{ number_format($subtotalAmount, 2) }}</p>
                </div>

                <form method="POST" action="{{ route('payments.process', $booking) }}" class="row g-3" id="payment_checkout_form" enctype="multipart/form-data">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Payment method</label>
                        <select class="form-select" name="method" id="payment_method_select" required>
                            <option value="cash" @selected($selectedMethod === 'cash')>Cash</option>
                            <option value="instapay" @selected($selectedMethod === 'instapay')>InstaPay</option>
                            <option value="credit_debit_card" @selected($selectedMethod === 'credit_debit_card')>Credit/Debit Card</option>
                        </select>
                        <small class="text-secondary">
                            If you select Cash, booking stays unpaid until staff confirms payment at the front desk.
                            For InstaPay or Credit/Debit Card, upload payment proof for manual verification.
                        </small>
                    </div>

                    <div class="col-12 {{ in_array($selectedMethod, $onlineMethods, true) ? '' : 'd-none' }}" id="online_verification_fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Transaction reference number</label>
                                <input
                                    type="text"
                                    class="form-control @error('customer_reference') is-invalid @enderror"
                                    name="customer_reference"
                                    id="customer_reference_input"
                                    maxlength="120"
                                    value="{{ old('customer_reference') }}"
                                    placeholder="Enter transaction reference no."
                                >
                                @error('customer_reference')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Optional provider reference</label>
                                <input
                                    type="text"
                                    class="form-control @error('qr_reference') is-invalid @enderror"
                                    name="qr_reference"
                                    maxlength="80"
                                    value="{{ old('qr_reference') }}"
                                    placeholder="Optional bank/network reference"
                                >
                                @error('qr_reference')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payment screenshot / proof</label>
                                <input
                                    type="file"
                                    class="form-control @error('payment_proof') is-invalid @enderror"
                                    name="payment_proof"
                                    id="payment_proof_input"
                                    accept="image/*"
                                >
                                <small class="text-secondary">Upload a clear screenshot/photo of your successful payment.</small>
                                @error('payment_proof')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <small class="text-secondary">
                                    Submitted online payments are not auto-confirmed.
                                    Staff will review your reference and proof before marking payment as paid.
                                    By submitting, you agree to our
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms and Conditions</a>.
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ $backRoute ?? route('bookings.show', $booking) }}" class="btn btn-ta-outline">Back</a>
                        <button type="submit" class="btn btn-ta" id="payment_submit_button">
                            {{ in_array($selectedMethod, $onlineMethods, true) ? 'Submit for verification' : 'Confirm payment' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const methodSelect = document.getElementById('payment_method_select');
            const onlineFields = document.getElementById('online_verification_fields');
            const submitButton = document.getElementById('payment_submit_button');
            const onlineMethods = ['instapay', 'credit_debit_card'];

            if (!methodSelect || !onlineFields || !submitButton) {
                return;
            }

            const updateUi = () => {
                const requiresOnlineProof = onlineMethods.includes(methodSelect.value);
                onlineFields.classList.toggle('d-none', !requiresOnlineProof);
                submitButton.textContent = requiresOnlineProof ? 'Submit for verification' : 'Confirm payment';
            };

            methodSelect.addEventListener('change', updateUi);
            updateUi();
        })();
    </script>
@endpush

