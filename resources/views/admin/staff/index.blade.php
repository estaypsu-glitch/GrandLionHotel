@extends('layouts.admin')

@section('title', 'Staff Accounts')

@push('head')
    <style>
        .admin-staff-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, var(--admin-surface) 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .admin-staff-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .admin-staff-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-staff-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .staff-actions-col {
            min-width: 330px;
        }
        .staff-created-col {
            min-width: 170px;
        }
        .staff-created-at {
            white-space: nowrap;
        }
        .staff-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            flex-wrap: nowrap;
            gap: 0.45rem;
        }
        .staff-actions form {
            margin: 0;
        }
        .staff-actions .btn {
            min-height: 35px;
            min-width: 84px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.34rem;
            padding: 0.4rem 0.78rem;
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
        }
        .staff-actions .btn-ta {
            box-shadow: 0 5px 11px rgba(var(--theme-primary-rgb), 0.2);
        }
        .staff-actions .btn-ta:hover {
            box-shadow: 0 8px 15px rgba(var(--theme-secondary-rgb), 0.2);
        }
        .staff-actions .btn-delete {
            border: 1px solid rgba(var(--theme-secondary-rgb), 0.48);
            color: var(--theme-secondary);
            background: rgba(var(--theme-secondary-rgb), 0.08);
        }
        .staff-actions .btn-delete:hover,
        .staff-actions .btn-delete:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        @media (max-width: 991.98px) {
            .staff-actions-col,
            .staff-created-col {
                min-width: 330px;
            }
            .staff-created-at {
                white-space: normal;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Staff Accounts</h1>
            <p class="text-secondary mb-0">Daily earnings shown for {{ $selectedDateLabel }}</p>
        </div>
        <button type="button" class="btn btn-ta" data-bs-toggle="modal" data-bs-target="#createStaffModal">
            <i class="bi bi-person-plus me-1"></i>Add Staff
        </button>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-2">
            <div class="admin-staff-stat">
                <p class="label">Total Staff</p>
                <p class="value">{{ $stats['total_staff'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-staff-stat">
                <p class="label">Joined Last 30 Days</p>
                <p class="value text-primary">{{ $stats['recent_30_days'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-staff-stat">
                <p class="label">Staff With Sales</p>
                <p class="value text-warning">{{ $stats['staff_with_sales_for_date'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-staff-stat">
                <p class="label">Paid Bookings (Day)</p>
                <p class="value text-info">{{ $stats['paid_bookings_for_date'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-2">
            <div class="admin-staff-stat">
                <p class="label">Sales (Day)</p>
                <p class="value">&#8369;{{ number_format((float) $stats['revenue_for_date'], 2) }}</p>
            </div>
        </div>
    </div>

    <section class="admin-staff-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.staff.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-5">
                    <label class="form-label">Search staff</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Name, email, or phone">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Earnings date</label>
                    <input type="date" class="form-control" name="earnings_date" value="{{ $selectedDateString }}">
                </div>
                <div class="col-lg-3 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.staff.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Daily Earnings</th>
                        <th>Assigned Bookings</th>
                        <th>Paid Bookings (Day)</th>
                        <th class="staff-created-col">Created</th>
                        <th class="text-end staff-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($staffMembers as $staff)
                        @php($staffName = \App\Support\PersonName::split($staff->name))
                        <tr>
                            <td>{{ $staff->name }}</td>
                            <td>{{ $staff->email }}</td>
                            <td>{{ $staff->phone ?: '-' }}</td>
                            <td>
                                <div class="fw-semibold">&#8369;{{ number_format((float) ($staff->daily_revenue ?? 0), 2) }}</div>
                                <small class="text-secondary">{{ (int) ($staff->daily_paid_bookings ?? 0) }} paid booking(s) on {{ $selectedDateLabel }}</small>
                            </td>
                            <td>{{ $staff->assigned_bookings_count }}</td>
                            <td>{{ (int) ($staff->daily_paid_bookings ?? 0) }}</td>
                            <td class="staff-created-at">{{ $staff->created_at?->format('M d, Y h:i A') }}</td>
                            <td class="text-end">
                                <div class="staff-actions">
                                    <a href="{{ route('admin.staff.show', $staff) }}" class="btn btn-sm btn-ta">
                                        <i class="bi bi-people"></i>
                                        <span>Customers</span>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-edit-staff-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editStaffModal"
                                        data-staff-id="{{ $staff->id }}"
                                        data-staff-first-name="{{ $staffName['first_name'] }}"
                                        data-staff-last-name="{{ $staffName['last_name'] }}"
                                        data-staff-email="{{ $staff->email }}"
                                        data-staff-phone="{{ $staff->phone ?? '' }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.staff.destroy', $staff) }}" onsubmit="return confirm('Delete this staff account?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-delete">
                                            <i class="bi bi-trash"></i>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">No staff accounts yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $staffMembers->links() }}
    </div>

    <div class="modal fade" id="createStaffModal" tabindex="-1" aria-labelledby="createStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.staff.store') }}">
                    @csrf
                    <input type="hidden" name="_staff_modal_mode" value="create">

                    <div class="modal-header">
                        <h5 class="modal-title" id="createStaffModalLabel">Add Staff Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First name</label>
                                <input type="text" name="first_name" id="create_staff_first_name" class="form-control @error('first_name') is-invalid @enderror" value="{{ old('_staff_modal_mode') === 'create' ? old('first_name') : '' }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last name</label>
                                <input type="text" name="last_name" id="create_staff_last_name" class="form-control @error('last_name') is-invalid @enderror" value="{{ old('_staff_modal_mode') === 'create' ? old('last_name') : '' }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="create_staff_email" class="form-control @error('email') is-invalid @enderror" value="{{ old('_staff_modal_mode') === 'create' ? old('email') : '' }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone (optional)</label>
                                <input type="text" name="phone" id="create_staff_phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('_staff_modal_mode') === 'create' ? old('phone') : '' }}" placeholder="+63...">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Temporary password</label>
                                <input type="password" name="password" id="create_staff_password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm password</label>
                                <input type="password" name="password_confirmation" id="create_staff_password_confirmation" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Create account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editStaffForm" method="POST" action="{{ route('admin.staff.update', ['staff' => '__STAFF__']) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_staff_modal_mode" value="edit">
                    <input type="hidden" name="_staff_modal_id" id="edit_staff_modal_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First name</label>
                                <input type="text" name="first_name" id="edit_staff_first_name" class="form-control @error('first_name') is-invalid @enderror" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last name</label>
                                <input type="text" name="last_name" id="edit_staff_last_name" class="form-control @error('last_name') is-invalid @enderror" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_staff_email" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone (optional)</label>
                                <input type="text" name="phone" id="edit_staff_phone" class="form-control @error('phone') is-invalid @enderror" placeholder="+63...">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New password (optional)</label>
                                <input type="password" name="password" id="edit_staff_password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm new password</label>
                                <input type="password" name="password_confirmation" id="edit_staff_password_confirmation" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-ta-outline" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-ta">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap === 'undefined') {
                return;
            }

            const createModalEl = document.getElementById('createStaffModal');
            const editModalEl = document.getElementById('editStaffModal');
            const editFormEl = document.getElementById('editStaffForm');
            if (!createModalEl || !editModalEl || !editFormEl) {
                return;
            }

            const updateUrlTemplate = @json(route('admin.staff.update', ['staff' => '__STAFF__']));
            const oldMode = @json(old('_staff_modal_mode'));
            const oldStaffId = @json(old('_staff_modal_id'));
            const oldFormValues = {
                firstName: @json(old('first_name')),
                lastName: @json(old('last_name')),
                email: @json(old('email')),
                phone: @json(old('phone')),
            };

            const editFieldId = document.getElementById('edit_staff_modal_id');
            const editFieldFirstName = document.getElementById('edit_staff_first_name');
            const editFieldLastName = document.getElementById('edit_staff_last_name');
            const editFieldEmail = document.getElementById('edit_staff_email');
            const editFieldPhone = document.getElementById('edit_staff_phone');
            const editFieldPassword = document.getElementById('edit_staff_password');
            const editFieldPasswordConfirmation = document.getElementById('edit_staff_password_confirmation');

            editModalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const staffId = trigger.getAttribute('data-staff-id') || '';
                editFormEl.action = updateUrlTemplate.replace('__STAFF__', staffId);
                editFieldId.value = staffId;
                editFieldFirstName.value = trigger.getAttribute('data-staff-first-name') || '';
                editFieldLastName.value = trigger.getAttribute('data-staff-last-name') || '';
                editFieldEmail.value = trigger.getAttribute('data-staff-email') || '';
                editFieldPhone.value = trigger.getAttribute('data-staff-phone') || '';
                editFieldPassword.value = '';
                editFieldPasswordConfirmation.value = '';
            });

            if (oldMode === 'create') {
                bootstrap.Modal.getOrCreateInstance(createModalEl).show();
            }

            if (oldMode === 'edit' && oldStaffId) {
                const oldButton = document.querySelector(`.js-edit-staff-btn[data-staff-id="${oldStaffId}"]`);
                if (oldButton) {
                    bootstrap.Modal.getOrCreateInstance(editModalEl).show();
                    editFormEl.action = updateUrlTemplate.replace('__STAFF__', oldStaffId);
                    editFieldId.value = oldStaffId;
                    if (oldFormValues.firstName !== null) editFieldFirstName.value = oldFormValues.firstName;
                    if (oldFormValues.lastName !== null) editFieldLastName.value = oldFormValues.lastName;
                    if (oldFormValues.email !== null) editFieldEmail.value = oldFormValues.email;
                    if (oldFormValues.phone !== null) editFieldPhone.value = oldFormValues.phone;
                }
            }
        });
    </script>
@endpush
