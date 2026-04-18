@extends('layouts.admin')

@section('title', 'Rooms')

@push('head')
    <style>
        .admin-room-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, var(--admin-surface) 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .admin-room-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .admin-room-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-rooms-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-discount-shell {
            border-radius: 14px;
            border: 1px solid #ddcfba;
            background: linear-gradient(180deg, #fffaf1 0%, #fff 100%);
            box-shadow: var(--admin-shadow);
        }
        .admin-discount-shell .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #7b6650;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
    </style>
@endpush

@section('content')
    @php
        $oldTargetScopeRaw = old('target_scope', 'all');
        $oldTargetScope = $oldTargetScopeRaw === 'type' ? 'roomtype' : $oldTargetScopeRaw;
        $oldSelectedRoomIds = collect(old('room_ids', []))->map(fn ($id) => (int) $id)->all();
        $hasBulkDiscountErrors = $errors->hasAny([
            'target_scope',
            'room_type',
            'room_ids',
            'room_ids.*',
            'discount_percent',
            'discount_start',
            'discount_end',
        ]);
    @endphp

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Room Management</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.rooms.create') }}" class="btn btn-ta">
                <i class="bi bi-plus-circle me-1"></i>Add Room
            </a>
            <button
                type="button"
                class="btn btn-ta-outline"
                data-bs-toggle="modal"
                data-bs-target="#bulkDateDiscountModal"
            >
                <i class="bi bi-calendar-range me-1"></i>Bulk Date Discount
            </button>
            <a href="{{ route('admin.rooms.date-discounts.index') }}" class="btn btn-ta-outline">
                <i class="bi bi-journal-text me-1"></i>View Date Discounts
            </a>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Total Rooms</p>
                <p class="value">{{ $stats['total'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Available</p>
                <p class="value text-success">{{ $stats['available'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Unavailable</p>
                <p class="value text-secondary">{{ $stats['unavailable'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-room-stat">
                <p class="label">Upcoming Date Discounts</p>
                <p class="value text-warning">{{ $stats['active_discounts'] }}</p>
            </div>
        </div>
    </div>

    <section class="admin-rooms-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.rooms.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search room</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Room name, room type, or view">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-select">
                        <option value="all" @selected($availability === 'all')>All</option>
                        <option value="available" @selected($availability === 'available')>Available</option>
                        <option value="unavailable" @selected($availability === 'unavailable')>Unavailable</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Room Status</label>
                    <select name="room_status" class="form-select">
                        <option value="">All</option>
                        @foreach($roomStatuses as $status)
                            <option value="{{ $status->slug }}" @selected($roomStatus === $status->slug)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.rooms.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Room Type</th>
                        <th>View</th>
                        <th>Capacity</th>
                        <th>Price</th>
                        <th>Room Status</th>
                        <th>Availability</th>
                        <th class="text-end admin-action-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rooms as $room)
                        <tr>
                            <td>#{{ $room->id }}</td>
                            <td>{{ $room->name }}</td>
                            <td>{{ $room->type }}</td>
                            <td>{{ $room->view_type ?: '-' }}</td>
                            <td>{{ $room->capacity }}</td>
                            <td>
                                <div>&#8369;{{ number_format($room->price_per_night, 2) }} / night</div>
                            </td>
                            <td>
                                <form action="{{ route('admin.rooms.update-room-status', $room) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PATCH')
                                    <select name="room_status_id" onchange="this.form.submit()" class="form-select form-select-sm py-1" style="width: auto;">
                                        @foreach($roomStatuses as $status)
                                            <option value="{{ $status->id }}" {{ $room->room_status_id == $status->id ? 'selected' : '' }}>
                                                {{ $status->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                                @if($room->status_updated_at)
                                    <small class="d-block text-muted">
                                        Updated {{ $room->status_updated_at->diffForHumans() }}
                                        @if($room->statusUpdatedByAdmin)
                                            by {{ $room->statusUpdatedByAdmin->name }}
                                        @endif
                                    </small>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $room->is_available ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $room->is_available ? 'Available' : 'Unavailable' }}</span>
                            </td>
                            <td class="text-end admin-action-col">
                                <div class="admin-action-group">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-edit-room-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editRoomModal"
                                        data-room-id="{{ $room->id }}"
                                        data-room-name="{{ $room->name }}"
                                        data-room-type="{{ $room->type }}"
                                        data-room-view-type="{{ $room->view_type ?? '' }}"
                                        data-room-description="{{ $room->description ?? '' }}"
                                        data-room-price-night="{{ number_format((float) $room->price_per_night, 2, '.', '') }}"
                                        data-room-capacity="{{ $room->capacity }}"
                                        data-room-image="{{ $room->image ?? '' }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form action="{{ route('admin.rooms.destroy', $room) }}" method="POST" onsubmit="return confirm('Delete this room? This action cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-action-delete">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4">No rooms found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $rooms->links() }}
    </div>

    <div class="modal fade" id="editRoomModal" tabindex="-1" aria-labelledby="editRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editRoomForm" method="POST" action="{{ route('admin.rooms.update', ['room' => '__ROOM__']) }}" class="row g-0">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_room_modal_id" id="edit_room_modal_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoomModalLabel">Edit Room</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" id="edit_room_name" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Room Type</label>
                                <input type="text" class="form-control" name="type" id="edit_room_type" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">View Type</label>
                                <input type="text" class="form-control" name="view_type" id="edit_room_view_type" placeholder="Nature View, Garden View, etc.">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="description" id="edit_room_description" rows="3"></textarea>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Price per night</label>
                                <input type="number" step="0.01" class="form-control" name="price_per_night" id="edit_room_price_night" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Capacity</label>
                                <input type="number" min="1" class="form-control" name="capacity" id="edit_room_capacity" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Image URL</label>
                                <input type="url" class="form-control" name="image" id="edit_room_image" placeholder="https://...">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Update room</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDateDiscountModal" tabindex="-1" aria-labelledby="bulkDateDiscountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content admin-discount-shell">
                <form method="POST" action="{{ route('admin.rooms.date-discounts.bulk') }}">
                    @csrf

                    <div class="modal-header">
                        <div>
                            <h5 class="modal-title mb-1" id="bulkDateDiscountModalLabel">Bulk Date Discount</h5>
                            <p class="text-secondary mb-0 small">Apply one discount percent to a date range for all rooms, one room type, or selected rooms.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Target Scope</label>
                                <select name="target_scope" id="discount_target_scope" class="form-select @error('target_scope') is-invalid @enderror">
                                    <option value="all" @selected($oldTargetScope === 'all')>All rooms</option>
                                    <option value="roomtype" @selected($oldTargetScope === 'roomtype')>By room type</option>
                                    <option value="selected" @selected($oldTargetScope === 'selected')>Selected rooms</option>
                                </select>
                                @error('target_scope')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4" id="discount_room_type_wrap">
                                <label class="form-label">Room Type</label>
                                <select name="room_type" id="discount_room_type" class="form-select @error('room_type') is-invalid @enderror">
                                    <option value="">Choose room type</option>
                                    @foreach($roomTypes as $roomType)
                                        <option value="{{ $roomType }}" @selected(old('room_type') === $roomType)>{{ $roomType }}</option>
                                    @endforeach
                                </select>
                                @error('room_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Discount (%)</label>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="1"
                                    max="100"
                                    name="discount_percent"
                                    value="{{ old('discount_percent', '10') }}"
                                    class="form-control @error('discount_percent') is-invalid @enderror"
                                    required
                                >
                                @error('discount_percent')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Start Date</label>
                                <input
                                    type="date"
                                    name="discount_start"
                                    value="{{ old('discount_start', now()->toDateString()) }}"
                                    class="form-control @error('discount_start') is-invalid @enderror"
                                    required
                                >
                                @error('discount_start')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">End Date</label>
                                <input
                                    type="date"
                                    name="discount_end"
                                    value="{{ old('discount_end', now()->addDays(6)->toDateString()) }}"
                                    class="form-control @error('discount_end') is-invalid @enderror"
                                    required
                                >
                                @error('discount_end')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12" id="discount_room_select_wrap">
                                <label class="form-label">Selected Rooms</label>
                                <input
                                    type="text"
                                    id="discount_room_search"
                                    class="form-control mb-2"
                                    placeholder="Search room by ID, name, or room type"
                                >
                                <select
                                    name="room_ids[]"
                                    id="discount_room_ids"
                                    class="form-select @error('room_ids') is-invalid @enderror @error('room_ids.*') is-invalid @enderror"
                                    multiple
                                    size="8"
                                >
                                    @foreach($discountRoomOptions as $optionRoom)
                                        <option value="{{ $optionRoom->id }}" @selected(in_array((int) $optionRoom->id, $oldSelectedRoomIds, true))>
                                            #{{ $optionRoom->id }} - {{ $optionRoom->name }} (Room Type: {{ $optionRoom->type }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-secondary d-block mt-1">Hold Ctrl (Windows) or Command (Mac) to select multiple rooms.</small>
                                @error('room_ids')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                @error('room_ids.*')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-ta">Apply Discount</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const discountScopeSelect = document.getElementById('discount_target_scope');
            const discountRoomTypeWrap = document.getElementById('discount_room_type_wrap');
            const discountRoomTypeField = document.getElementById('discount_room_type');
            const discountRoomSelectWrap = document.getElementById('discount_room_select_wrap');
            const discountRoomSelectField = document.getElementById('discount_room_ids');
            const discountRoomSearchField = document.getElementById('discount_room_search');

            const normalizeDiscountSearchValue = function (value) {
                return (value || '').toString().trim().toLowerCase();
            };

            const updateDiscountRoomSearchUi = function () {
                if (!discountRoomSelectField) {
                    return;
                }

                const query = normalizeDiscountSearchValue(discountRoomSearchField?.value);
                const options = Array.from(discountRoomSelectField.options);

                options.forEach(function (optionEl) {
                    const optionText = normalizeDiscountSearchValue(optionEl.textContent);
                    optionEl.hidden = query !== '' && !optionText.includes(query);
                });
            };

            const updateDiscountScopeUi = function () {
                if (!discountScopeSelect) {
                    return;
                }

                const scope = discountScopeSelect.value;
                const showRoomType = scope === 'roomtype';
                const showSelected = scope === 'selected';

                if (discountRoomTypeWrap) {
                    discountRoomTypeWrap.classList.toggle('d-none', !showRoomType);
                }
                if (discountRoomTypeField) {
                    discountRoomTypeField.disabled = !showRoomType;
                }

                if (discountRoomSelectWrap) {
                    discountRoomSelectWrap.classList.toggle('d-none', !showSelected);
                }
                if (discountRoomSelectField) {
                    discountRoomSelectField.disabled = !showSelected;
                }
                if (discountRoomSearchField) {
                    discountRoomSearchField.disabled = !showSelected;
                    if (!showSelected) {
                        discountRoomSearchField.value = '';
                    }
                }

                updateDiscountRoomSearchUi();
            };

            discountScopeSelect?.addEventListener('change', updateDiscountScopeUi);
            discountRoomSearchField?.addEventListener('input', updateDiscountRoomSearchUi);
            updateDiscountScopeUi();

            const bulkDateDiscountModalEl = document.getElementById('bulkDateDiscountModal');
            const shouldOpenBulkFromQuery = new URLSearchParams(window.location.search).get('open_bulk_discount') === '1';
            if (bulkDateDiscountModalEl && typeof bootstrap !== 'undefined' && (@json($hasBulkDiscountErrors) || shouldOpenBulkFromQuery)) {
                const bulkModal = bootstrap.Modal.getOrCreateInstance(bulkDateDiscountModalEl);
                bulkModal.show();
            }

            const editRoomModalEl = document.getElementById('editRoomModal');
            const formEl = document.getElementById('editRoomForm');
            if (!editRoomModalEl || !formEl || typeof bootstrap === 'undefined') {
                return;
            }

            const updateUrlTemplate = @json(route('admin.rooms.update', ['room' => '__ROOM__']));
            const oldFormValues = {
                name: @json(old('name')),
                type: @json(old('type')),
                viewType: @json(old('view_type')),
                description: @json(old('description')),
                priceNight: @json(old('price_per_night')),
                capacity: @json(old('capacity')),
                image: @json(old('image')),
            };

            const fieldRoomId = document.getElementById('edit_room_modal_id');
            const fieldName = document.getElementById('edit_room_name');
            const fieldType = document.getElementById('edit_room_type');
            const fieldViewType = document.getElementById('edit_room_view_type');
            const fieldDescription = document.getElementById('edit_room_description');
            const fieldPriceNight = document.getElementById('edit_room_price_night');
            const fieldCapacity = document.getElementById('edit_room_capacity');
            const fieldImage = document.getElementById('edit_room_image');

            editRoomModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const roomId = trigger.getAttribute('data-room-id') || '';
                const name = trigger.getAttribute('data-room-name') || '';
                const type = trigger.getAttribute('data-room-type') || '';
                const viewType = trigger.getAttribute('data-room-view-type') || '';
                const description = trigger.getAttribute('data-room-description') || '';
                const priceNight = trigger.getAttribute('data-room-price-night') || '0';
                const capacity = trigger.getAttribute('data-room-capacity') || '1';
                const image = trigger.getAttribute('data-room-image') || '';

                formEl.action = updateUrlTemplate.replace('__ROOM__', roomId);
                fieldRoomId.value = roomId;
                fieldName.value = name;
                fieldType.value = type;
                fieldViewType.value = viewType;
                fieldDescription.value = description;
                fieldPriceNight.value = priceNight;
                fieldCapacity.value = capacity;
                fieldImage.value = image;
            });

            const oldModalRoomId = @json(old('_room_modal_id'));
            if (oldModalRoomId) {
                const oldButton = document.querySelector(`.js-edit-room-btn[data-room-id="${oldModalRoomId}"]`);
                if (oldButton) {
                    bootstrap.Modal.getOrCreateInstance(editRoomModalEl).show(oldButton);

                    if (oldFormValues.name !== null) fieldName.value = oldFormValues.name;
                    if (oldFormValues.type !== null) fieldType.value = oldFormValues.type;
                    if (oldFormValues.viewType !== null) fieldViewType.value = oldFormValues.viewType;
                    if (oldFormValues.description !== null) fieldDescription.value = oldFormValues.description;
                    if (oldFormValues.priceNight !== null) fieldPriceNight.value = oldFormValues.priceNight;
                    if (oldFormValues.capacity !== null) fieldCapacity.value = oldFormValues.capacity;
                    if (oldFormValues.image !== null) fieldImage.value = oldFormValues.image;
                }
            }
        });
    </script>
@endpush
