@extends('layouts.admin')

@section('title', 'Date Discounts')

@push('head')
    <style>
        .discount-stat-card {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, #fffaf1 0%, #fff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .discount-stat-card .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #7b6650;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .discount-stat-card .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .date-discount-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 0.45rem;
        }
        .date-discount-room-list {
            display: grid;
            gap: 0.45rem;
            max-height: 260px;
            overflow-y: auto;
        }
        .date-discount-room-item {
            border: 1px solid var(--admin-line);
            border-radius: 10px;
            background: #f8fbff;
            padding: 0.58rem 0.72rem;
            font-size: 0.9rem;
        }
    </style>
@endpush

@section('content')
    @php
        $hasEditErrors = $errors->hasAny(['start_date', 'end_date', 'room_ids', 'room_ids.*', 'discount_percent']);
        $oldEditStartDate = old('start_date', '');
        $oldEditEndDate = old('end_date', '');
        $oldEditRoomIds = old('room_ids', []);
        $oldEditPercent = old('discount_percent', '');
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Date Discounts</h1>
            <p class="text-secondary mb-0 small">Review and update scheduled room discounts.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">
                <i class="bi bi-arrow-left me-1"></i>Back to Rooms
            </a>
        </div>
    </div>

    <section class="table-shell p-3 p-lg-4 mb-3">
        <form method="GET" action="{{ route('admin.rooms.date-discounts.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search</label>
                    <input
                        type="text"
                        name="q"
                        class="form-control"
                        value="{{ $search }}"
                        placeholder="Room ID, room name, or room type"
                    >
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.rooms.date-discounts.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="row g-3 mb-3">
        <div class="col-sm-6">
            <div class="discount-stat-card">
                <p class="label">Discounted Dates</p>
                <p class="value">{{ number_format($summary['date_count']) }}</p>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="discount-stat-card">
                <p class="label">Affected Rooms</p>
                <p class="value">{{ number_format($summary['room_count']) }}</p>
            </div>
        </div>
    </div>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Date Range</th>
                        <th>Discount</th>
                        <th>Room Types</th>
                        <th>Regular Price / Night</th>
                        <th>Discounted Price / Night</th>
                        <th>Rooms</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discountOverviewRanges as $range)
                        @php
                            $startLabel = \Carbon\Carbon::parse($range->start_date)->format('M d, Y');
                            $endLabel = \Carbon\Carbon::parse($range->end_date)->format('M d, Y');
                            $dateLabel = $startLabel === $endLabel ? $startLabel : $startLabel.' - '.$endLabel;
                            $discountLabel = $range->discount_values->map(static fn ($value): string => number_format((float) $value, 2).'%')->join(', ');
                            $regularPriceLabel = $range->regular_price_min === $range->regular_price_max
                                ? 'PHP '.number_format((float) $range->regular_price_min, 2)
                                : 'PHP '.number_format((float) $range->regular_price_min, 2).' - PHP '.number_format((float) $range->regular_price_max, 2);
                            $discountedPriceLabel = $range->discounted_price_min === $range->discounted_price_max
                                ? 'PHP '.number_format((float) $range->discounted_price_min, 2)
                                : 'PHP '.number_format((float) $range->discounted_price_min, 2).' - PHP '.number_format((float) $range->discounted_price_max, 2);
                            $roomPreview = $range->room_labels->take(3)->join(', ');
                            $remainingRooms = max($range->room_labels->count() - 3, 0);
                            $roomIdsCsv = $range->room_ids->map(static fn ($id): int => (int) $id)->join(',');
                            $roomCount = $range->room_ids->count();
                            $hasMixedDiscounts = $range->discount_values->count() > 1;
                            $defaultDiscount = (string) ($range->discount_values->first() ?? '');
                            $roomLabelsJson = json_encode($range->room_labels->values()->all(), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_HEX_QUOT);
                        @endphp
                        <tr>
                            <td>{{ $dateLabel }}</td>
                            <td>{{ $discountLabel !== '' ? $discountLabel : '-' }}</td>
                            <td>{{ $range->room_types->isNotEmpty() ? $range->room_types->join(', ') : '-' }}</td>
                            <td>{{ $regularPriceLabel }}</td>
                            <td class="text-success fw-semibold">{{ $discountedPriceLabel }}</td>
                            <td>
                                <div class="small">{{ $roomPreview !== '' ? $roomPreview : '-' }}</div>
                                @if($remainingRooms > 0)
                                    <small class="text-secondary">+{{ $remainingRooms }} more room(s)</small>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="date-discount-actions">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-view-date-discount-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewDateDiscountModal"
                                        data-date-label="{{ $dateLabel }}"
                                        data-start-date="{{ $range->start_date }}"
                                        data-end-date="{{ $range->end_date }}"
                                        data-room-count="{{ $roomCount }}"
                                        data-room-labels="{{ $roomLabelsJson }}"
                                        data-room-types="{{ $range->room_types->join(', ') }}"
                                        data-discount-label="{{ $discountLabel }}"
                                        data-regular-price-label="{{ $regularPriceLabel }}"
                                        data-discounted-price-label="{{ $discountedPriceLabel }}"
                                    >
                                        <i class="bi bi-eye me-1"></i>View
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-edit-date-discount-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDateDiscountModal"
                                        data-start-date="{{ $range->start_date }}"
                                        data-end-date="{{ $range->end_date }}"
                                        data-room-count="{{ $roomCount }}"
                                        data-room-ids="{{ $roomIdsCsv }}"
                                        data-current-discount="{{ $discountLabel }}"
                                        data-default-discount="{{ $defaultDiscount }}"
                                        data-mixed-discount="{{ $hasMixedDiscounts ? '1' : '0' }}"
                                    >
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </button>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-action-delete js-delete-date-discount-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteDateDiscountModal"
                                        data-date-label="{{ $dateLabel }}"
                                        data-start-date="{{ $range->start_date }}"
                                        data-end-date="{{ $range->end_date }}"
                                        data-room-count="{{ $roomCount }}"
                                        data-room-ids="{{ $roomIdsCsv }}"
                                        data-current-discount="{{ $discountLabel }}"
                                    >
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-secondary">No date discounts found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal fade" id="viewDateDiscountModal" tabindex="-1" aria-labelledby="viewDateDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewDateDiscountModalLabel">View Date Discount</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label class="form-label">Date Range</label>
                            <input type="text" id="view_discount_date_label" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Discount</label>
                            <input type="text" id="view_discount_label" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Room Types</label>
                            <input type="text" id="view_discount_room_types" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Affected Rooms</label>
                            <input type="text" id="view_discount_room_count" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Regular Price / Night</label>
                            <input type="text" id="view_discount_regular_price" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Discounted Price / Night</label>
                            <input type="text" id="view_discount_discounted_price" class="form-control" value="-" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Rooms Included</label>
                            <div id="view_discount_room_list" class="date-discount-room-list"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editDateDiscountModal" tabindex="-1" aria-labelledby="editDateDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editDateDiscountForm" method="POST" action="{{ route('admin.rooms.date-discounts.range.update') }}">
                    @csrf
                    @method('PATCH')

                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="original_start_date" id="edit_discount_original_start_date" value="{{ old('original_start_date') }}">
                    <input type="hidden" name="original_end_date" id="edit_discount_original_end_date" value="{{ old('original_end_date') }}">
                    <div id="edit_discount_room_ids_container"></div>

                    <div class="modal-header">
                        <h5 class="modal-title" id="editDateDiscountModalLabel">Edit Date Discount</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Start Date</label>
                                <input
                                    type="date"
                                    id="edit_discount_start_date"
                                    name="start_date"
                                    class="form-control @error('start_date') is-invalid @enderror"
                                    value="{{ old('start_date') }}"
                                >
                                @error('start_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">End Date</label>
                                <input
                                    type="date"
                                    id="edit_discount_end_date"
                                    name="end_date"
                                    class="form-control @error('end_date') is-invalid @enderror"
                                    value="{{ old('end_date') }}"
                                >
                                @error('end_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Affected Rooms</label>
                                <input type="text" id="edit_discount_room_count" class="form-control" value="-" readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Current Discount</label>
                                <input type="text" id="edit_discount_current_label" class="form-control" value="-" readonly>
                                <small id="edit_discount_mixed_help" class="text-secondary d-none">This entry has mixed discounts. Saving will unify them.</small>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">New Discount (%)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="100"
                                    id="edit_discount_percent"
                                    name="discount_percent"
                                    class="form-control @error('discount_percent') is-invalid @enderror"
                                    value="{{ old('discount_percent') }}"
                                    required
                                >
                                @error('discount_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            @if($errors->has('room_ids') || $errors->has('room_ids.*'))
                                <div class="col-12">
                                    @error('room_ids')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                    @error('room_ids.*')
                                        <div class="text-danger small">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteDateDiscountModal" tabindex="-1" aria-labelledby="deleteDateDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="deleteDateDiscountForm" method="POST" action="{{ route('admin.rooms.date-discounts.range.destroy') }}">
                    @csrf
                    @method('DELETE')

                    <input type="hidden" name="from" value="{{ $from }}">
                    <input type="hidden" name="to" value="{{ $to }}">
                    <input type="hidden" name="q" value="{{ $search }}">
                    <input type="hidden" name="original_start_date" id="delete_discount_original_start_date">
                    <input type="hidden" name="original_end_date" id="delete_discount_original_end_date">
                    <div id="delete_discount_room_ids_container"></div>

                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteDateDiscountModalLabel">Delete Date Discount</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p class="mb-3">This will permanently remove the selected date discount range.</p>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Date Range</label>
                                <input type="text" id="delete_discount_date_label" class="form-control" value="-" readonly>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Discount</label>
                                <input type="text" id="delete_discount_current_label" class="form-control" value="-" readonly>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Affected Rooms</label>
                                <input type="text" id="delete_discount_room_count" class="form-control" value="-" readonly>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-action-delete">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewModalEl = document.getElementById('viewDateDiscountModal');
            const editModalEl = document.getElementById('editDateDiscountModal');
            const deleteModalEl = document.getElementById('deleteDateDiscountModal');
            const editFormEl = document.getElementById('editDateDiscountForm');
            const deleteFormEl = document.getElementById('deleteDateDiscountForm');
            if (!viewModalEl || !editModalEl || !deleteModalEl || !editFormEl || !deleteFormEl || typeof bootstrap === 'undefined') {
                return;
            }

            const viewDateLabel = document.getElementById('view_discount_date_label');
            const viewDiscountLabel = document.getElementById('view_discount_label');
            const viewRoomTypes = document.getElementById('view_discount_room_types');
            const viewRoomCount = document.getElementById('view_discount_room_count');
            const viewRegularPrice = document.getElementById('view_discount_regular_price');
            const viewDiscountedPrice = document.getElementById('view_discount_discounted_price');
            const viewRoomList = document.getElementById('view_discount_room_list');
            const fieldStartDate = document.getElementById('edit_discount_start_date');
            const fieldEndDate = document.getElementById('edit_discount_end_date');
            const fieldOriginalStartDate = document.getElementById('edit_discount_original_start_date');
            const fieldOriginalEndDate = document.getElementById('edit_discount_original_end_date');
            const fieldRoomCount = document.getElementById('edit_discount_room_count');
            const fieldCurrentLabel = document.getElementById('edit_discount_current_label');
            const fieldNewPercent = document.getElementById('edit_discount_percent');
            const fieldMixedHelp = document.getElementById('edit_discount_mixed_help');
            const roomIdsContainer = document.getElementById('edit_discount_room_ids_container');
            const deleteFieldOriginalStartDate = document.getElementById('delete_discount_original_start_date');
            const deleteFieldOriginalEndDate = document.getElementById('delete_discount_original_end_date');
            const deleteFieldDateLabel = document.getElementById('delete_discount_date_label');
            const deleteFieldCurrentLabel = document.getElementById('delete_discount_current_label');
            const deleteFieldRoomCount = document.getElementById('delete_discount_room_count');
            const deleteRoomIdsContainer = document.getElementById('delete_discount_room_ids_container');

            const setRoomIds = function (container, roomIds) {
                if (!container) {
                    return;
                }

                container.innerHTML = '';
                roomIds.forEach(function (roomId) {
                    const inputEl = document.createElement('input');
                    inputEl.type = 'hidden';
                    inputEl.name = 'room_ids[]';
                    inputEl.value = String(roomId).trim();
                    container.appendChild(inputEl);
                });
            };

            const parseRoomIds = function (rawValue) {
                return String(rawValue || '')
                    .split(',')
                    .map(function (value) { return value.trim(); })
                    .filter(function (value) { return value !== ''; });
            };

            const parseJsonArray = function (rawValue) {
                try {
                    const parsed = JSON.parse(rawValue || '[]');

                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };

            viewModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const roomLabels = parseJsonArray(trigger.getAttribute('data-room-labels'));

                viewDateLabel.value = trigger.getAttribute('data-date-label') || '-';
                viewDiscountLabel.value = trigger.getAttribute('data-discount-label') || '-';
                viewRoomTypes.value = trigger.getAttribute('data-room-types') || '-';
                viewRoomCount.value = (trigger.getAttribute('data-room-count') || '0') + ' room(s)';
                viewRegularPrice.value = trigger.getAttribute('data-regular-price-label') || '-';
                viewDiscountedPrice.value = trigger.getAttribute('data-discounted-price-label') || '-';

                if (viewRoomList) {
                    viewRoomList.innerHTML = '';

                    if (roomLabels.length === 0) {
                        const emptyEl = document.createElement('div');
                        emptyEl.className = 'date-discount-room-item text-secondary';
                        emptyEl.textContent = 'No rooms listed for this discount.';
                        viewRoomList.appendChild(emptyEl);
                    } else {
                        roomLabels.forEach(function (label) {
                            const itemEl = document.createElement('div');
                            itemEl.className = 'date-discount-room-item';
                            itemEl.textContent = String(label);
                            viewRoomList.appendChild(itemEl);
                        });
                    }
                }
            });

            editModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const startDate = trigger.getAttribute('data-start-date') || '';
                const endDate = trigger.getAttribute('data-end-date') || '';
                const roomCount = trigger.getAttribute('data-room-count') || '0';
                const roomIds = parseRoomIds(trigger.getAttribute('data-room-ids'));
                const currentDiscountLabel = trigger.getAttribute('data-current-discount') || '-';
                const defaultDiscount = trigger.getAttribute('data-default-discount') || '';
                const mixedDiscountFlag = trigger.getAttribute('data-mixed-discount') === '1';

                fieldStartDate.value = startDate;
                fieldEndDate.value = endDate;
                if (fieldOriginalStartDate) {
                    fieldOriginalStartDate.value = startDate;
                }
                if (fieldOriginalEndDate) {
                    fieldOriginalEndDate.value = endDate;
                }
                fieldRoomCount.value = roomCount + ' room(s)';
                fieldCurrentLabel.value = currentDiscountLabel;
                if (fieldMixedHelp) {
                    fieldMixedHelp.classList.toggle('d-none', !mixedDiscountFlag);
                }
                fieldNewPercent.value = defaultDiscount;
                setRoomIds(roomIdsContainer, roomIds);
            });

            deleteModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const startDate = trigger.getAttribute('data-start-date') || '';
                const endDate = trigger.getAttribute('data-end-date') || '';
                const roomCount = trigger.getAttribute('data-room-count') || '0';
                const roomIds = parseRoomIds(trigger.getAttribute('data-room-ids'));

                deleteFieldDateLabel.value = trigger.getAttribute('data-date-label') || '-';
                deleteFieldCurrentLabel.value = trigger.getAttribute('data-current-discount') || '-';
                deleteFieldRoomCount.value = roomCount + ' room(s)';
                if (deleteFieldOriginalStartDate) {
                    deleteFieldOriginalStartDate.value = startDate;
                }
                if (deleteFieldOriginalEndDate) {
                    deleteFieldOriginalEndDate.value = endDate;
                }
                setRoomIds(deleteRoomIdsContainer, roomIds);
            });

            const hasEditErrors = @json($hasEditErrors);
            if (hasEditErrors) {
                const oldStartDate = @json($oldEditStartDate);
                const oldEndDate = @json($oldEditEndDate);
                const oldRoomIds = @json($oldEditRoomIds);
                const oldPercent = @json($oldEditPercent);
                const oldOriginalStartDate = @json(old('original_start_date'));
                const oldOriginalEndDate = @json(old('original_end_date'));

                fieldStartDate.value = oldStartDate || '';
                fieldEndDate.value = oldEndDate || '';
                if (fieldOriginalStartDate) {
                    fieldOriginalStartDate.value = oldOriginalStartDate || oldStartDate || '';
                }
                if (fieldOriginalEndDate) {
                    fieldOriginalEndDate.value = oldOriginalEndDate || oldEndDate || '';
                }
                fieldRoomCount.value = Array.isArray(oldRoomIds) ? oldRoomIds.length + ' room(s)' : '0 room(s)';
                fieldCurrentLabel.value = 'Previously submitted value';
                if (fieldMixedHelp) {
                    fieldMixedHelp.classList.add('d-none');
                }
                fieldNewPercent.value = oldPercent || '';
                setRoomIds(roomIdsContainer, Array.isArray(oldRoomIds) ? oldRoomIds : []);

                bootstrap.Modal.getOrCreateInstance(editModalEl).show();
            }
        });
    </script>
@endpush
