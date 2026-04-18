@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    @php
        $preferredMethod = data_get($booking->reservation_meta, 'payment_preference');
        $selectedMethod = old('method', $preferredMethod);
        $bookingId = (string) $booking->id;
        $amountValue = number_format((float) $booking->total_price, 2, '.', '');
        $billedUnits = max(1, $booking->nights());
        $subtotalAmount = (float) $booking->total_price;
        $unitRate = $billedUnits > 0 ? round($subtotalAmount / $billedUnits, 2) : $subtotalAmount;
        $merchantName = (string) config('services.qr_wallets.merchant_name', config('app.name', 'The Grand Lion Hotel'));
        $resolveQrImageUrl = static function (mixed $configuredUrl): string {
            $value = trim((string) $configuredUrl);
            if ($value === '') {
                return '';
            }

            if (preg_match('/^https?:\/\//i', $value) === 1 || str_starts_with($value, '/')) {
                return $value;
            }

            $normalized = str_replace('\\', '/', $value);
            $publicPath = str_replace('\\', '/', public_path());
            $publicPrefix = rtrim($publicPath, '/').'/';

            if (str_starts_with(strtolower($normalized), strtolower($publicPrefix))) {
                $relative = substr($normalized, strlen($publicPrefix));
                $relative = ltrim((string) $relative, '/');

                return '/'.$relative;
            }

            return '/'.ltrim($normalized, '/');
        };
        $qrWallets = [
            'gcash' => [
                'label' => (string) data_get(config('services.qr_wallets'), 'gcash.label', 'GCash'),
                'holder_name' => (string) data_get(config('services.qr_wallets'), 'gcash.holder_name', $merchantName),
                'number' => (string) data_get(config('services.qr_wallets'), 'gcash.number', '0917-123-4567'),
                'qr_image_url' => $resolveQrImageUrl(data_get(config('services.qr_wallets'), 'gcash.qr_image_url', '')),
                'qr_payload' => (string) data_get(config('services.qr_wallets'), 'gcash.qr_payload', ''),
            ],
            'paymaya' => [
                'label' => (string) data_get(config('services.qr_wallets'), 'paymaya.label', 'PayMaya'),
                'holder_name' => (string) data_get(config('services.qr_wallets'), 'paymaya.holder_name', $merchantName),
                'number' => (string) data_get(config('services.qr_wallets'), 'paymaya.number', '0918-123-4567'),
                'qr_image_url' => $resolveQrImageUrl(data_get(config('services.qr_wallets'), 'paymaya.qr_image_url', '')),
                'qr_payload' => (string) data_get(config('services.qr_wallets'), 'paymaya.qr_payload', ''),
            ],
        ];
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
                    <input type="hidden" name="qr_reference" id="qr_reference_input" value="{{ old('qr_reference') }}">
                    <div class="col-12">
                        <label class="form-label">Payment method</label>
                        <select class="form-select" name="method" id="payment_method_select" required>
                            <option value="cash" @selected($selectedMethod === 'cash')>Cash</option>
                            <option value="bank_transfer" @selected($selectedMethod === 'bank_transfer')>Bank Transfer</option>
                            <option value="gcash" @selected($selectedMethod === 'gcash')>GCash (QR)</option>
                            <option value="paymaya" @selected($selectedMethod === 'paymaya')>PayMaya (QR)</option>
                        </select>
                        <small class="text-secondary">
                            Choosing GCash or PayMaya will open a secure QR payment popup.
                            After payment, submit your reference number and screenshot for staff verification.
                            If you select Cash, booking stays unpaid until staff confirms payment at the front desk.
                        </small>
                    </div>
                    <div class="col-12 {{ in_array($selectedMethod, ['gcash', 'paymaya'], true) ? '' : 'd-none' }}" id="online_verification_fields">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Wallet reference number</label>
                                <input
                                    type="text"
                                    class="form-control"
                                    name="customer_reference"
                                    id="customer_reference_input"
                                    maxlength="120"
                                    value="{{ old('customer_reference') }}"
                                    placeholder="Enter GCash / PayMaya reference no."
                                >
                                @error('customer_reference')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Payment screenshot</label>
                                <input
                                    type="file"
                                    class="form-control"
                                    name="payment_proof"
                                    id="payment_proof_input"
                                    accept="image/*"
                                >
                                <small class="text-secondary">Upload a clear screenshot/photo of your successful transfer.</small>
                                @error('payment_proof')
                                    <small class="text-danger d-block">{{ $message }}</small>
                                @enderror
                            </div>
                            <div class="col-12">
                                <small class="text-secondary">
                                    Submitted online payments are not auto-confirmed.
                                    Our staff will review your uploaded proof and reference before marking payment as paid.
                                    By submitting, you agree to our
                                    <a href="{{ route('terms') }}" target="_blank" rel="noopener">Terms and Conditions</a>.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ $backRoute ?? route('bookings.show', $booking) }}" class="btn btn-ta-outline">Back</a>
                        <button type="submit" class="btn btn-ta" id="payment_submit_button">
                            {{ in_array($selectedMethod, ['gcash', 'paymaya'], true) ? 'Submit for verification' : 'Confirm payment' }}
                        </button>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <div class="modal fade" id="qrPaymentModal" tabindex="-1" aria-labelledby="qrPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content soft-card border-0">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <p class="ta-eyebrow mb-1">Online Wallet QR</p>
                        <h5 class="modal-title mb-0" id="qrPaymentModalLabel">Scan to Pay</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <p class="text-secondary mb-3">
                        Open <strong data-wallet-label>-</strong>, scan this QR, then continue to submit your payment proof.
                    </p>

                    <div class="text-center mb-3">
                        <img
                            src=""
                            alt="QR code for online payment"
                            class="img-fluid rounded-3 border p-2 bg-white"
                            style="max-width: 290px;"
                            data-qr-image
                        >
                    </div>
                    <div class="alert alert-warning small d-none" role="alert" data-qr-support></div>

                    <div class="bg-light rounded-3 p-3 small mb-3">
                        <div><strong>Merchant:</strong> {{ $merchantName }}</div>
                        <div><strong>Wallet:</strong> <span data-wallet-label>-</span></div>
                        <div><strong>Account Name:</strong> <span data-wallet-holder>-</span></div>
                        <div><strong>Wallet Number:</strong> <span data-wallet-number>-</span></div>
                        <div><strong>Amount:</strong> &#8369;{{ number_format($booking->total_price, 2) }}</div>
                        <div><strong>Reference:</strong> <span data-qr-reference>-</span></div>
                    </div>

                    <p class="small text-secondary mb-3 d-none" data-mobile-wallet-hint></p>

                    <div class="d-flex flex-wrap justify-content-end gap-2">
                        <button type="button" class="btn btn-ta-outline d-none" id="open_wallet_app_button">
                            Open Wallet App
                        </button>
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-ta" id="qr_continue_button">I have paid, continue</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const checkoutForm = document.getElementById('payment_checkout_form');
            const methodSelect = document.getElementById('payment_method_select');
            const qrReferenceInput = document.getElementById('qr_reference_input');
            const onlineVerificationFields = document.getElementById('online_verification_fields');
            const customerReferenceInput = document.getElementById('customer_reference_input');
            const paymentProofInput = document.getElementById('payment_proof_input');
            const paymentSubmitButton = document.getElementById('payment_submit_button');
            const qrModalElement = document.getElementById('qrPaymentModal');
            const continueButton = document.getElementById('qr_continue_button');

            if (
                !checkoutForm ||
                !methodSelect ||
                !qrReferenceInput ||
                !onlineVerificationFields ||
                !customerReferenceInput ||
                !paymentProofInput ||
                !qrModalElement ||
                typeof bootstrap === 'undefined'
            ) {
                return;
            }

            const qrWallets = @json($qrWallets);
            const bookingId = @json($bookingId);
            const amount = @json($amountValue);
            const merchantName = @json($merchantName);
            const qrImage = qrModalElement.querySelector('[data-qr-image]');
            const qrSupportNotice = qrModalElement.querySelector('[data-qr-support]');
            const mobileWalletHint = qrModalElement.querySelector('[data-mobile-wallet-hint]');
            const walletLabels = qrModalElement.querySelectorAll('[data-wallet-label]');
            const walletHolder = qrModalElement.querySelector('[data-wallet-holder]');
            const walletNumber = qrModalElement.querySelector('[data-wallet-number]');
            const qrReferenceText = qrModalElement.querySelector('[data-qr-reference]');
            const openWalletAppButton = document.getElementById('open_wallet_app_button');
            const qrModal = new bootstrap.Modal(qrModalElement);
            const userAgent = (navigator.userAgent || '').toLowerCase();
            const isAndroid = /android/i.test(userAgent);
            const isIOS = /iphone|ipad|ipod/i.test(userAgent);
            const walletAppLinks = {
                gcash: 'gcash://',
                paymaya: 'maya://',
            };
            const walletStoreLinks = {
                gcash: {
                    android: 'https://play.google.com/store/search?q=GCash&c=apps',
                    ios: 'https://apps.apple.com/ph/search?term=GCash',
                    web: 'https://www.gcash.com/',
                },
                paymaya: {
                    android: 'https://play.google.com/store/search?q=Maya&c=apps',
                    ios: 'https://apps.apple.com/ph/search?term=Maya',
                    web: 'https://www.maya.ph/',
                },
            };
            const isMobileDevice = /android|iphone|ipad|ipod|mobile/i.test((navigator.userAgent || '').toLowerCase())
                || (window.matchMedia && window.matchMedia('(pointer: coarse)').matches);
            let allowSubmitAfterQr = false;

            const updateOnlineVerificationUI = () => {
                const selectedMethod = methodSelect.value;
                const needsQrPopup = Object.prototype.hasOwnProperty.call(qrWallets, selectedMethod);

                onlineVerificationFields.classList.toggle('d-none', !needsQrPopup);

                if (paymentSubmitButton) {
                    paymentSubmitButton.textContent = needsQrPopup
                        ? 'Submit for verification'
                        : 'Confirm payment';
                }

                if (!needsQrPopup) {
                    qrReferenceInput.value = '';
                }
            };

            const buildReference = (method) => {
                const suffix = Date.now().toString().slice(-6);
                return `GLH-${bookingId}-${method.toUpperCase()}-${suffix}`;
            };

            const normalizePayload = (value) => String(value || '').replace(/\s+/g, '').trim();

            const parseTlv = (payload) => {
                const tlvFields = [];
                let cursor = 0;

                while (cursor < payload.length) {
                    if (cursor + 4 > payload.length) {
                        return null;
                    }

                    const id = payload.slice(cursor, cursor + 2);
                    const lengthText = payload.slice(cursor + 2, cursor + 4);

                    if (!/^\d{2}$/.test(id) || !/^\d{2}$/.test(lengthText)) {
                        return null;
                    }

                    const valueLength = Number(lengthText);
                    const valueStart = cursor + 4;
                    const valueEnd = valueStart + valueLength;

                    if (valueEnd > payload.length) {
                        return null;
                    }

                    tlvFields.push({
                        id,
                        value: payload.slice(valueStart, valueEnd),
                    });

                    cursor = valueEnd;
                }

                return tlvFields;
            };

            const encodeTlv = (tlvFields) => {
                return tlvFields.map((field) => {
                    const value = String(field.value || '');
                    return `${field.id}${String(value.length).padStart(2, '0')}${value}`;
                }).join('');
            };

            const upsertTlvField = (tlvFields, id, value) => {
                const existingField = tlvFields.find((field) => field.id === id);
                if (existingField) {
                    existingField.value = value;
                    return;
                }

                const targetId = Number(id);
                const insertIndex = tlvFields.findIndex((field) => Number(field.id) > targetId);

                if (insertIndex === -1) {
                    tlvFields.push({ id, value });
                    return;
                }

                tlvFields.splice(insertIndex, 0, { id, value });
            };

            const crc16Ccitt = (value) => {
                let crc = 0xFFFF;

                for (let i = 0; i < value.length; i += 1) {
                    crc ^= value.charCodeAt(i) << 8;

                    for (let bit = 0; bit < 8; bit += 1) {
                        if ((crc & 0x8000) !== 0) {
                            crc = ((crc << 1) ^ 0x1021) & 0xFFFF;
                        } else {
                            crc = (crc << 1) & 0xFFFF;
                        }
                    }
                }

                return crc.toString(16).toUpperCase().padStart(4, '0');
            };

            const buildAmountAwarePayload = (basePayload, qrReference) => {
                const cleanedPayload = normalizePayload(basePayload);
                if (cleanedPayload === '') {
                    return null;
                }

                const topLevelFields = parseTlv(cleanedPayload);
                if (!topLevelFields) {
                    return null;
                }

                const fieldsWithoutCrc = topLevelFields
                    .filter((field) => field.id !== '63')
                    .map((field) => ({ ...field }));

                upsertTlvField(fieldsWithoutCrc, '01', '12');
                upsertTlvField(fieldsWithoutCrc, '53', '608');
                upsertTlvField(fieldsWithoutCrc, '54', amount);

                const referenceValue = qrReference.slice(0, 25);
                const additionalDataField = fieldsWithoutCrc.find((field) => field.id === '62');

                if (additionalDataField) {
                    const additionalFields = parseTlv(additionalDataField.value);
                    if (!additionalFields) {
                        return null;
                    }

                    upsertTlvField(additionalFields, '05', referenceValue);
                    additionalDataField.value = encodeTlv(additionalFields);
                } else {
                    const additionalFields = [{ id: '05', value: referenceValue }];
                    upsertTlvField(fieldsWithoutCrc, '62', encodeTlv(additionalFields));
                }

                const payloadWithoutCrc = `${encodeTlv(fieldsWithoutCrc)}6304`;
                const crc = crc16Ccitt(payloadWithoutCrc);

                return `${payloadWithoutCrc}${crc}`;
            };

            const buildQrPayload = (method, label, holderName, number, reference) => {
                return [
                    `merchant:${merchantName}`,
                    `booking:${bookingId}`,
                    `method:${method}`,
                    `wallet:${label}`,
                    `holder:${holderName}`,
                    `wallet_number:${number}`,
                    `amount:${amount}`,
                    `reference:${reference}`,
                ].join('|');
            };

            const resolveQrSource = (method, walletConfig, qrReference) => {
                const officialPayload = normalizePayload(walletConfig.qr_payload || '');
                if (officialPayload !== '') {
                    const amountAwarePayload = buildAmountAwarePayload(officialPayload, qrReference);

                    if (amountAwarePayload !== null) {
                        return {
                            url: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(amountAwarePayload)}`,
                            supported: true,
                            note: `${walletConfig.label} QR amount is auto-filled to PHP ${amount}.`,
                        };
                    }

                    return {
                        url: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(officialPayload)}`,
                        supported: false,
                        note: `${walletConfig.label} QR payload is invalid for amount injection. Keep ${method.toUpperCase()}_QR_PAYLOAD as one clean EMV/QRPh string.`,
                    };
                }

                const officialImageUrl = (walletConfig.qr_image_url || '').trim();
                if (officialImageUrl !== '') {
                    return {
                        url: officialImageUrl,
                        supported: false,
                        note: `Static ${walletConfig.label} QR loaded. Add ${method.toUpperCase()}_QR_PAYLOAD to auto-fill the bill amount.`,
                    };
                }

                const holderName = walletConfig.holder_name || merchantName;
                const fallbackPayload = buildQrPayload(method, walletConfig.label, holderName, walletConfig.number, qrReference);

                return {
                    url: `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(fallbackPayload)}`,
                    supported: false,
                    note: `Demo QR only. Set ${method.toUpperCase()}_QR_IMAGE_URL or ${method.toUpperCase()}_QR_PAYLOAD in .env for wallet-supported scanning.`,
                };
            };

            const updateWalletAppAction = (method, walletConfig) => {
                const appLink = walletAppLinks[method] || '';
                const canOpenWalletApp = isMobileDevice && appLink !== '';

                if (openWalletAppButton) {
                    if (canOpenWalletApp) {
                        openWalletAppButton.classList.remove('d-none');
                        openWalletAppButton.textContent = `Open ${walletConfig.label} App`;
                        openWalletAppButton.setAttribute('data-wallet-link', appLink);
                        openWalletAppButton.setAttribute('data-wallet-method', method);
                    } else {
                        openWalletAppButton.classList.add('d-none');
                        openWalletAppButton.textContent = 'Open Wallet App';
                        openWalletAppButton.removeAttribute('data-wallet-link');
                        openWalletAppButton.removeAttribute('data-wallet-method');
                    }
                }

                if (mobileWalletHint) {
                    if (canOpenWalletApp) {
                        mobileWalletHint.classList.remove('d-none');
                        mobileWalletHint.textContent = `Using this phone? Tap "Open ${walletConfig.label} App". If not installed, we'll open its download page.`;
                    } else {
                        mobileWalletHint.classList.add('d-none');
                        mobileWalletHint.textContent = '';
                    }
                }
            };

            const resolveStoreFallback = (method) => {
                const storeLinks = walletStoreLinks[method] || {};
                if (isAndroid && storeLinks.android) {
                    return storeLinks.android;
                }
                if (isIOS && storeLinks.ios) {
                    return storeLinks.ios;
                }

                return storeLinks.web || storeLinks.android || storeLinks.ios || '';
            };

            const openWalletAppWithFallback = (method) => {
                const appLink = walletAppLinks[method] || '';
                if (appLink === '') {
                    return;
                }

                const fallbackUrl = resolveStoreFallback(method);
                let didSwitchApp = false;
                const onVisibilityChange = () => {
                    if (document.visibilityState === 'hidden') {
                        didSwitchApp = true;
                    }
                };
                document.addEventListener('visibilitychange', onVisibilityChange);

                window.location.href = appLink;

                window.setTimeout(() => {
                    document.removeEventListener('visibilitychange', onVisibilityChange);

                    if (!didSwitchApp && document.visibilityState === 'visible' && fallbackUrl !== '') {
                        window.location.href = fallbackUrl;
                    }
                }, 1400);
            };

            const showQrModal = (method) => {
                const walletConfig = qrWallets[method];
                if (!walletConfig) {
                    return;
                }

                const qrReference = buildReference(method);
                const holderName = walletConfig.holder_name || merchantName;
                const qrSource = resolveQrSource(method, walletConfig, qrReference);

                qrReferenceInput.value = qrReference;

                walletLabels.forEach((node) => {
                    node.textContent = walletConfig.label;
                });

                if (walletNumber) {
                    walletNumber.textContent = walletConfig.number;
                }

                if (walletHolder) {
                    walletHolder.textContent = holderName;
                }

                if (qrReferenceText) {
                    qrReferenceText.textContent = qrReference;
                }

                if (qrImage) {
                    qrImage.src = qrSource.url;
                    qrImage.alt = `${walletConfig.label} QR code for booking ${bookingId}`;
                }

                if (qrSupportNotice) {
                    qrSupportNotice.textContent = qrSource.note;
                    qrSupportNotice.classList.remove('d-none', 'alert-warning', 'alert-success');
                    qrSupportNotice.classList.add(qrSource.supported ? 'alert-success' : 'alert-warning');
                }

                updateWalletAppAction(method, walletConfig);

                qrModal.show();
            };

            checkoutForm.addEventListener('submit', (event) => {
                const selectedMethod = methodSelect.value;
                const needsQrPopup = Object.prototype.hasOwnProperty.call(qrWallets, selectedMethod);

                if (!needsQrPopup) {
                    qrReferenceInput.value = '';
                    return;
                }

                if (allowSubmitAfterQr) {
                    allowSubmitAfterQr = false;
                    return;
                }

                event.preventDefault();
                showQrModal(selectedMethod);
            });

            methodSelect.addEventListener('change', updateOnlineVerificationUI);
            updateOnlineVerificationUI();

            continueButton?.addEventListener('click', () => {
                const requiresOnlineProof = Object.prototype.hasOwnProperty.call(qrWallets, methodSelect.value);
                if (requiresOnlineProof) {
                    const customerReference = customerReferenceInput.value.trim();
                    const hasProofUpload = (paymentProofInput.files || []).length > 0;

                    if (customerReference === '' || !hasProofUpload) {
                        onlineVerificationFields.classList.remove('d-none');
                        qrModal.hide();
                        window.setTimeout(() => {
                            if (customerReference === '') {
                                customerReferenceInput.focus();
                            } else {
                                paymentProofInput.focus();
                            }
                        }, 250);
                        window.alert('Please enter your wallet reference number and upload your payment screenshot before submitting.');
                        return;
                    }
                }

                allowSubmitAfterQr = true;
                qrModal.hide();
                checkoutForm.requestSubmit();
            });

            openWalletAppButton?.addEventListener('click', () => {
                const walletMethod = openWalletAppButton.getAttribute('data-wallet-method');
                if (!walletMethod) {
                    return;
                }

                openWalletAppWithFallback(walletMethod);
            });
        })();
    </script>
@endpush
