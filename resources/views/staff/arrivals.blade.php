@extends('layouts.staff')

@section('title', "Today's Arrivals")

@push('head')
    <style>
        .arrivals-stat {
            border-radius: 14px;
            border: 1px solid #d7deec;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .arrivals-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .arrivals-stat .value {
            font-size: clamp(1.3rem, 0.9vw + 0.9rem, 1.7rem);
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .arrivals-table-shell {
            border-radius: 14px;
            border: 1px solid #d9e1ef;
            background: #fff;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }
        .arrivals-table thead th {
            font-size: 0.7rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #5a6a80;
            white-space: nowrap;
            border-top: 0;
            padding-top: 0.78rem;
            padding-bottom: 0.62rem;
        }
        .arrivals-table td {
            border-color: #edf2f7;
            vertical-align: top;
            font-size: 0.9rem;
        }
        .arrivals-table tbody tr:hover {
            background: #f8fbff;
        }
        .arrivals-actions-col {
            min-width: 430px;
        }
        .arrivals-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 0.35rem;
        }
        .arrivals-actions form {
            margin: 0;
        }
        .arrivals-actions .btn {
            min-height: 34px;
            min-width: 86px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.28rem;
            padding: 0.38rem 0.64rem;
            font-size: 0.78rem;
            line-height: 1;
            white-space: nowrap;
        }
        .arrivals-actions .btn-staff {
            box-shadow: 0 5px 10px rgba(var(--theme-primary-rgb), 0.18);
        }
        .arrivals-actions .btn-staff:hover {
            box-shadow: 0 8px 14px rgba(var(--theme-secondary-rgb), 0.2);
        }
        .gcash-modal-shell {
            background: #d9dde3;
        }
        .gcash-top {
            background: linear-gradient(180deg, #0b58d1 0%, #0a4fbf 100%);
            min-height: 170px;
            padding: 1.4rem 1.5rem 2.2rem;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .gcash-brand {
            color: #fff;
            font-size: 2rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.02em;
        }
        .gcash-pay-card {
            border-radius: 14px;
            background: #fff;
            border: 1px solid #d8dde8;
            box-shadow: 0 10px 35px rgba(17, 24, 39, 0.16);
            margin-top: -70px;
            padding: 1.2rem 1rem 1rem;
            text-align: center;
        }
        .gcash-open-btn {
            border-radius: 999px;
            border: 0;
            background: linear-gradient(90deg, #0d69ec 0%, #0b58d1 100%);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            text-decoration: none;
            padding: 0.62rem 1.15rem;
        }
        .gcash-open-btn:hover {
            color: #fff;
            filter: brightness(0.96);
        }
        .gcash-qr-frame {
            width: 236px;
            margin: 0.8rem auto 0;
            background: #fff;
            border: 1px solid #e4e9f2;
            border-radius: 12px;
            padding: 0.45rem;
        }
        .gcash-qr-frame img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
@endpush

@section('content')
    @php
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
        $resolveDiscountProofUrl = static function (mixed $storedPath): string {
            $path = trim((string) $storedPath);
            if ($path === '') {
                return '';
            }

            return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        };
        $gcashWallet = [
            'label' => (string) data_get(config('services.qr_wallets'), 'instapay.label', 'InstaPay'),
            'holder_name' => (string) data_get(config('services.qr_wallets'), 'instapay.holder_name', $merchantName),
            'number' => (string) data_get(config('services.qr_wallets'), 'instapay.number', 'N/A'),
            'qr_image_url' => $resolveQrImageUrl(data_get(config('services.qr_wallets'), 'instapay.qr_image_url', '')),
            'qr_payload' => (string) data_get(config('services.qr_wallets'), 'instapay.qr_payload', ''),
            'app_link' => (string) data_get(config('services.qr_wallets'), 'instapay.app_link', 'https://www.bsp.gov.ph/PaymentAndSettlement/Instapay'),
        ];
    @endphp

    <section class="mb-4">
        <h1 class="h4 mb-1">Arrivals Board</h1>
        <p class="text-secondary mb-0">{{ now()->format('M d, Y') }}</p>
    </section>

    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="arrivals-stat">
                <p class="label">Total Arrivals</p>
                <p class="value">{{ $stats['total_arrivals'] }}</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="arrivals-stat">
                <p class="label">Checked In</p>
                <p class="value text-success">{{ $stats['checked_in'] }}</p>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="arrivals-stat">
                <p class="label">Pending Check-in</p>
                <p class="value text-warning">{{ $stats['pending'] }}</p>
            </div>
        </div>
    </div>

    <section class="arrivals-table-shell p-3 p-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h5 mb-0">Arrivals Queue</h2>
        </div>
        
        @if($arrivals->isEmpty())
            <div class="text-center py-5">
                <p class="text-secondary mb-0">No arrivals scheduled for today.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table arrivals-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Room</th>
                            <th>Check-in Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th class="text-end arrivals-actions-col staff-action-col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($arrivals as $booking)
                            @php
                                $paymentBadgeClass = match ($booking->payment_status) {
                                    'paid' => 'text-bg-success',
                                    'pending_verification' => 'text-bg-info',
                                    'refund_pending' => 'text-bg-info',
                                    default => 'text-bg-warning',
                                };
                            @endphp
                            <tr>
                                <td>#{{ $booking->id }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span>{{ $booking->guestName() }}</span>
                                        <small class="text-secondary">{{ $booking->guestEmail() !== '-' ? $booking->guestEmail() : '' }}</small>
                                    </div>
                                </td>
                                <td>{{ $booking->room->name ?? '-' }}</td>
                                <td>{{ $booking->check_in->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge text-bg-{{ $booking->status === 'confirmed' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($booking->status) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge {{ $paymentBadgeClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $booking->payment_status)) }}
                                    </span>
                                </td>
                                <td class="text-end arrivals-actions-col staff-action-col">
                                    <div class="arrivals-actions staff-action-group">
                                        @if($booking->status === 'confirmed' && $booking->payment_status === 'paid')
                                            <form method="POST" action="{{ route('staff.bookings.check-in', $booking) }}" data-confirm="Check in this guest now?">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="actual_check_in_at" value="{{ now()->format('Y-m-d\TH:i') }}">
                                                <button type="submit" class="btn btn-sm btn-staff">
                                                    <i class="bi bi-door-open"></i>
                                                    <span>Check In</span>
                                                </button>
                                            </form>
                                        @elseif($booking->status === 'confirmed' && $booking->payment_status === 'unpaid')
                                            <form method="POST" action="{{ route('staff.bookings.record-payment', $booking) }}" data-confirm="Record cash payment for this booking?">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="method" value="cash">
                                                <button type="submit" class="btn btn-sm btn-staff-outline">
                                                    <i class="bi bi-cash-stack"></i>
                                                    <span>Cash</span>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('staff.bookings.record-payment', $booking) }}" data-confirm="Record card payment for this booking?">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="method" value="credit_debit_card">
                                                <button type="submit" class="btn btn-sm btn-staff-outline">
                                                    <i class="bi bi-credit-card-2-front"></i>
                                                    <span>Card</span>
                                                </button>
                                            </form>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-staff js-open-gcash-qr"
                                                data-booking-id="{{ $booking->id }}"
                                                data-amount="{{ number_format((float) $booking->total_price, 2, '.', '') }}"
                                                data-record-url="{{ route('staff.bookings.record-payment', $booking) }}"
                                                data-guest-name="{{ $booking->guestName() }}"
                                                data-room-name="{{ $booking->room->name ?? '-' }}"
                                                data-default-discount="{{ data_get($booking->reservation_meta, 'discount_type', 'none') }}"
                                                data-default-discount-id="{{ data_get($booking->reservation_meta, 'discount_id', '') }}"
                                                data-default-discount-proof-url="{{ $resolveDiscountProofUrl(data_get($booking->reservation_meta, 'discount_id_photo_path', '')) }}"
                                            >
                                                <i class="bi bi-qr-code"></i>
                                                <span>InstaPay QR</span>
                                            </button>
                                        @elseif($booking->status === 'confirmed' && $booking->payment_status === 'pending_verification')
                                            <span class="small text-secondary d-inline-block">Awaiting payment verification</span>
                                        @elseif($booking->status === 'pending')
                                            <form method="POST" action="{{ route('staff.bookings.confirm', $booking) }}" data-confirm="Confirm this booking?">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-staff-outline">
                                                    <i class="bi bi-check2-circle"></i>
                                                    <span>Confirm</span>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('staff.bookings.show', ['booking' => $booking, 'return_to' => request()->getRequestUri()]) }}" class="btn btn-sm btn-staff-outline">
                                            <i class="bi bi-eye"></i>
                                            <span>View</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            @if($arrivals->hasPages())
                <div class="mt-4">
                    {{ $arrivals->links() }}
                </div>
            @endif
        @endif
    </section>

    <div class="modal fade" id="gcashQrModal" tabindex="-1" aria-labelledby="gcashQrModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 gcash-modal-shell">
                <div class="gcash-top">
                    <p class="gcash-brand mb-0">{{ $gcashWallet['label'] }}</p>
                </div>
                <div class="modal-body px-3 pb-3 pt-0">
                    <div class="gcash-pay-card">
                        <h5 class="fw-bold mb-2" id="gcashQrModalLabel">Securely complete the payment with InstaPay</h5>
                        <a
                            href="{{ $gcashWallet['app_link'] }}"
                            target="_blank"
                            rel="noopener"
                            class="gcash-open-btn"
                            id="gcash_open_app_link"
                        >
                            Open InstaPay details
                        </a>
                        <p class="fw-semibold mt-3 mb-2">Scan this QR using your banking app with InstaPay support.</p>

                        <div class="gcash-qr-frame">
                            <img src="" alt="InstaPay QR code" id="gcash_qr_image">
                        </div>

                        <div class="alert alert-warning small d-none mt-3 mb-0" id="gcash_qr_notice" role="alert"></div>
                        <div class="small text-secondary mt-3">
                            Booking <strong data-gcash-booking>-</strong> | Guest: <strong data-gcash-guest>-</strong>
                        </div>
                        <div class="small text-secondary mt-1">
                            Room: <strong data-gcash-room>-</strong>
                        </div>
                        <div class="small text-secondary mt-1">
                            Base Amount: <strong data-gcash-base-amount>-</strong>
                        </div>
                        <div class="small text-secondary mt-1">
                            Discount: <strong data-gcash-discount>-</strong>
                        </div>
                        <div class="small text-secondary mt-1">
                            Payable Amount: <strong data-gcash-amount>-</strong>
                        </div>
                        <div class="small text-secondary mt-1 d-none" id="gcash_discount_proof_wrap">
                            Discount ID Photo:
                            <a href="#" target="_blank" rel="noopener" id="gcash_discount_proof_link">View uploaded photo</a>
                        </div>
                        <div class="small text-secondary mt-1">
                            Reference: <strong data-gcash-reference>-</strong>
                        </div>
                    </div>

                    <form method="POST" action="" id="gcash_record_payment_form" class="mt-3" data-confirm="Mark this booking as paid via InstaPay?">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="method" value="instapay">
                        <input type="hidden" name="qr_reference" value="" id="gcash_qr_reference_input">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label small mb-1">Discount Type</label>
                                <select class="form-select" name="discount_type" id="gcash_discount_type">
                                    <option value="none">None</option>
                                    <option value="pwd">PWD (20%)</option>
                                    <option value="senior">Senior (20%)</option>
                                </select>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label small mb-1">Discount ID Number (Required if no uploaded photo)</label>
                                <input type="text" class="form-control" name="discount_id" id="gcash_discount_id" maxlength="80" placeholder="PWD/Senior ID">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end gap-2 mt-3">
                            <button type="button" class="btn btn-staff-outline" data-bs-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-staff">Mark as Paid</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (() => {
            const openButtons = document.querySelectorAll('.js-open-gcash-qr');
            const modalElement = document.getElementById('gcashQrModal');
            const qrImage = document.getElementById('gcash_qr_image');
            const qrNotice = document.getElementById('gcash_qr_notice');
            const openAppLink = document.getElementById('gcash_open_app_link');
            const recordForm = document.getElementById('gcash_record_payment_form');
            const qrReferenceInput = document.getElementById('gcash_qr_reference_input');
            const discountTypeSelect = document.getElementById('gcash_discount_type');
            const discountIdInput = document.getElementById('gcash_discount_id');
            const discountProofWrap = document.getElementById('gcash_discount_proof_wrap');
            const discountProofLink = document.getElementById('gcash_discount_proof_link');
            const bookingText = modalElement?.querySelector('[data-gcash-booking]');
            const guestText = modalElement?.querySelector('[data-gcash-guest]');
            const roomText = modalElement?.querySelector('[data-gcash-room]');
            const baseAmountText = modalElement?.querySelector('[data-gcash-base-amount]');
            const discountText = modalElement?.querySelector('[data-gcash-discount]');
            const amountText = modalElement?.querySelector('[data-gcash-amount]');
            const referenceText = modalElement?.querySelector('[data-gcash-reference]');

            if (
                openButtons.length === 0 ||
                !modalElement ||
                !recordForm ||
                !qrImage ||
                !qrReferenceInput ||
                !discountTypeSelect ||
                typeof bootstrap === 'undefined'
            ) {
                return;
            }

            const merchantName = @json($merchantName);
            const walletConfig = @json($gcashWallet);
            const qrModal = new bootstrap.Modal(modalElement);
            const discountRates = {
                none: 0,
                pwd: 0.2,
                senior: 0.2,
            };
            let activeBooking = null;

            const normalizePayload = (value) => String(value || '').replace(/\s+/g, '').trim();
            const roundMoney = (value) => Math.round(Number(value) * 100) / 100;
            const formatMoney = (value) => Number(value).toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

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

            const buildAmountAwarePayload = (basePayload, amount, qrReference) => {
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

            const buildFallbackPayload = (bookingId, amount, reference) => {
                return [
                    `merchant:${merchantName}`,
                    `booking:${bookingId}`,
                    `wallet:${walletConfig.label || 'InstaPay'}`,
                    `holder:${walletConfig.holder_name || merchantName}`,
                    `wallet_number:${walletConfig.number || ''}`,
                    `amount:${amount}`,
                    `reference:${reference}`,
                ].join('|');
            };

            const buildReference = (bookingId) => {
                const suffix = Date.now().toString().slice(-6);
                return `GLH-${bookingId}-INSTAPAY-${suffix}`;
            };

            const resolveQrSource = (amount, qrReference, bookingId) => {
                const officialPayload = normalizePayload(walletConfig.qr_payload || '');
                if (officialPayload !== '') {
                    const amountAwarePayload = buildAmountAwarePayload(officialPayload, amount, qrReference);

                    if (amountAwarePayload !== null) {
                        return {
                            url: `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(amountAwarePayload)}`,
                            note: `InstaPay QR generated with auto-filled amount PHP ${amount}.`,
                            supported: true,
                        };
                    }

                    return {
                        url: `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(officialPayload)}`,
                        note: 'InstaPay payload is invalid for amount injection. Use a valid single-line QRPh payload.',
                        supported: false,
                    };
                }

                const officialImage = String(walletConfig.qr_image_url || '').trim();
                if (officialImage !== '') {
                    return {
                        url: officialImage,
                        note: 'Showing static InstaPay QR image. Add INSTAPAY_QR_PAYLOAD for auto-filled bill amount.',
                        supported: false,
                    };
                }

                const fallbackPayload = buildFallbackPayload(bookingId, amount, qrReference);

                return {
                    url: `https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=${encodeURIComponent(fallbackPayload)}`,
                    note: 'Demo QR generated. Add INSTAPAY_QR_PAYLOAD in .env for real wallet-compatible dynamic amount.',
                    supported: false,
                };
            };

            const setNotice = (note, supported) => {
                if (!qrNotice) {
                    return;
                }

                qrNotice.textContent = note;
                qrNotice.classList.remove('d-none', 'alert-success', 'alert-warning');
                qrNotice.classList.add(supported ? 'alert-success' : 'alert-warning');
            };

            const getComputedAmounts = (baseAmount, discountType) => {
                const rate = Object.prototype.hasOwnProperty.call(discountRates, discountType)
                    ? discountRates[discountType]
                    : 0;
                const discountAmount = roundMoney(baseAmount * rate);
                const payableAmount = roundMoney(Math.max(0, baseAmount - discountAmount));

                return {
                    rate,
                    discountAmount,
                    payableAmount,
                };
            };

            const renderActiveBooking = () => {
                if (!activeBooking) {
                    return;
                }

                const discountType = discountTypeSelect.value || 'none';
                const computed = getComputedAmounts(activeBooking.baseAmount, discountType);
                const payableAmountText = computed.payableAmount.toFixed(2);
                const qrSource = resolveQrSource(payableAmountText, activeBooking.reference, activeBooking.bookingId);

                if (openAppLink) {
                    openAppLink.href = String(walletConfig.app_link || 'https://www.bsp.gov.ph/PaymentAndSettlement/Instapay');
                }

                if (bookingText) {
                    bookingText.textContent = `#${activeBooking.bookingId}`;
                }

                if (guestText) {
                    guestText.textContent = activeBooking.guestName;
                }

                if (roomText) {
                    roomText.textContent = activeBooking.roomName;
                }

                if (baseAmountText) {
                    baseAmountText.textContent = `PHP ${formatMoney(activeBooking.baseAmount)}`;
                }

                if (discountText) {
                    discountText.textContent = computed.rate > 0
                        ? `${discountType.toUpperCase()} - PHP ${formatMoney(computed.discountAmount)} (${Math.round(computed.rate * 100)}%)`
                        : 'None';
                }

                if (discountProofWrap && discountProofLink) {
                    if (computed.rate > 0 && activeBooking.discountProofUrl) {
                        discountProofLink.href = activeBooking.discountProofUrl;
                        discountProofWrap.classList.remove('d-none');
                    } else {
                        discountProofLink.href = '#';
                        discountProofWrap.classList.add('d-none');
                    }
                }

                if (amountText) {
                    amountText.textContent = `PHP ${formatMoney(computed.payableAmount)}`;
                }

                if (referenceText) {
                    referenceText.textContent = activeBooking.reference;
                }

                if (discountIdInput) {
                    const requiresDiscountId = computed.rate > 0;
                    const hasUploadedProof = Boolean(activeBooking.discountProofUrl);
                    discountIdInput.disabled = !requiresDiscountId;
                    discountIdInput.required = requiresDiscountId && !hasUploadedProof;
                    if (!requiresDiscountId) {
                        discountIdInput.value = '';
                    }
                }

                qrImage.src = qrSource.url;
                qrImage.alt = `InstaPay QR for booking ${activeBooking.bookingId}`;
                setNotice(qrSource.note, qrSource.supported);
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    const bookingId = button.getAttribute('data-booking-id') || '';
                    const guestName = button.getAttribute('data-guest-name') || '-';
                    const roomName = button.getAttribute('data-room-name') || '-';
                    const amountRaw = button.getAttribute('data-amount') || '0.00';
                    const baseAmount = roundMoney(Number.parseFloat(amountRaw) || 0);
                    const recordUrl = button.getAttribute('data-record-url') || '';
                    const reference = buildReference(bookingId);
                    const defaultDiscount = button.getAttribute('data-default-discount') || 'none';
                    const defaultDiscountId = button.getAttribute('data-default-discount-id') || '';
                    const defaultDiscountProofUrl = button.getAttribute('data-default-discount-proof-url') || '';

                    activeBooking = {
                        bookingId,
                        guestName,
                        roomName,
                        baseAmount,
                        recordUrl,
                        reference,
                        defaultDiscount,
                        defaultDiscountId,
                        discountProofUrl: defaultDiscountProofUrl,
                    };

                    recordForm.action = recordUrl;
                    qrReferenceInput.value = reference;
                    discountTypeSelect.value = Object.prototype.hasOwnProperty.call(discountRates, defaultDiscount)
                        ? defaultDiscount
                        : 'none';
                    if (discountIdInput) {
                        discountIdInput.value = defaultDiscountId;
                    }

                    renderActiveBooking();
                    qrModal.show();
                });
            });

            discountTypeSelect.addEventListener('change', () => {
                renderActiveBooking();
            });
        })();
    </script>
@endpush
