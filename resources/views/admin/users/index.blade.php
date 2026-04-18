@extends('layouts.admin')

@section('title', 'Customer Accounts')

@push('head')
    <style>
        .admin-user-stat {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            box-shadow: var(--admin-shadow);
            background: linear-gradient(180deg, var(--admin-surface) 0%, #f8fbff 100%);
            padding: 0.78rem 0.88rem;
            height: 100%;
        }
        .admin-user-stat .label {
            font-size: 0.68rem;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #617084;
            font-weight: 700;
            margin-bottom: 0.28rem;
        }
        .admin-user-stat .value {
            font-size: 1.34rem;
            line-height: 1;
            font-weight: 800;
            margin: 0;
        }
        .admin-user-shell {
            border-radius: 14px;
            border: 1px solid var(--admin-line);
            background: #fff;
            box-shadow: var(--admin-shadow);
        }
        .admin-user-location {
            color: #6b7280;
            font-size: 0.84rem;
        }
        .admin-user-joined-col {
            min-width: 190px;
        }
        .admin-user-actions-col {
            min-width: 220px;
        }
        .admin-user-joined-at {
            white-space: nowrap;
        }
        .admin-user-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.45rem;
            flex-wrap: nowrap;
        }
        .admin-user-actions form {
            margin: 0;
        }
        .admin-user-actions .btn {
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
        .admin-user-actions .btn-ta-outline {
            border-width: 1px;
        }
        .admin-user-actions .btn-delete {
            border: 1px solid rgba(var(--theme-secondary-rgb), 0.48);
            color: var(--theme-secondary);
            background: rgba(var(--theme-secondary-rgb), 0.08);
        }
        .admin-user-actions .btn-delete:hover,
        .admin-user-actions .btn-delete:focus {
            border-color: var(--theme-secondary);
            background: var(--theme-secondary);
            color: #fff;
        }
        @media (max-width: 991.98px) {
            .admin-user-actions-col,
            .admin-user-joined-col {
                min-width: 280px;
            }
            .admin-user-joined-at {
                white-space: normal;
            }
        }
    </style>
@endpush

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">Customer Accounts</h1>
        </div>
        <a href="{{ route('admin.users.index', ['profile' => 'incomplete']) }}" class="btn btn-ta-outline">
            <i class="bi bi-person-exclamation me-1"></i>View Incomplete Profiles
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="admin-user-stat">
                <p class="label">Total Customers</p>
                <p class="value">{{ $stats['total_customers'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-user-stat">
                <p class="label">With Bookings</p>
                <p class="value text-primary">{{ $stats['with_bookings'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-user-stat">
                <p class="label">Incomplete Profiles</p>
                <p class="value text-warning">{{ $stats['incomplete_profiles'] }}</p>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="admin-user-stat">
                <p class="label">Joined Last 30 Days</p>
                <p class="value text-success">{{ $stats['recent_30_days'] }}</p>
            </div>
        </div>
    </div>

    <section class="admin-user-shell p-3 p-lg-4 mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}">
            <div class="row g-2 align-items-end">
                <div class="col-lg-4">
                    <label class="form-label">Search customer</label>
                    <input type="text" class="form-control" name="q" value="{{ request('q') }}" placeholder="Name, email, phone, city, province">
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Profile status</label>
                    <select class="form-select" name="profile">
                        <option value="all" @selected($profile === 'all')>All</option>
                        <option value="complete" @selected($profile === 'complete')>Complete</option>
                        <option value="incomplete" @selected($profile === 'incomplete')>Incomplete</option>
                    </select>
                </div>
                <div class="col-sm-6 col-lg-3">
                    <label class="form-label">Booking history</label>
                    <select class="form-select" name="bookings">
                        <option value="all" @selected($bookings === 'all')>All</option>
                        <option value="with" @selected($bookings === 'with')>With bookings</option>
                        <option value="without" @selected($bookings === 'without')>Without bookings</option>
                    </select>
                </div>
                <div class="col-lg-2 d-flex gap-2">
                    <button type="submit" class="btn btn-ta w-100">Apply</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-ta-outline">Reset</a>
                </div>
            </div>
        </form>
    </section>

    <div class="table-shell p-2 p-lg-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Location</th>
                        <th>Profile</th>
                        <th>Bookings</th>
                        <th class="admin-user-joined-col">Joined</th>
                        <th class="text-end admin-user-actions-col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        @php($customerName = \App\Support\PersonName::split($customer->name))
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $customer->name }}</div>
                                <small class="text-secondary">#{{ $customer->id }}</small>
                            </td>
                            <td>
                                <div>{{ $customer->email }}</div>
                                <small class="text-secondary">{{ $customer->phone ?: '-' }}</small>
                            </td>
                            <td>
                                <span class="admin-user-location">
                                    {{ collect([$customer->city, $customer->province])->filter()->implode(', ') ?: '-' }}
                                </span>
                            </td>
                            <td>
                                @if($customer->hasCompleteProfile())
                                    <span class="badge text-bg-success">Complete</span>
                                @else
                                    <span class="badge text-bg-warning">Incomplete</span>
                                @endif
                            </td>
                            <td>{{ $customer->bookings_count }}</td>
                            <td class="admin-user-joined-at">{{ $customer->created_at?->format('M d, Y h:i A') }}</td>
                            <td class="text-end admin-user-actions-col">
                                <div class="admin-user-actions">
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-ta-outline js-edit-user-btn"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-user-id="{{ $customer->id }}"
                                        data-user-first-name="{{ $customerName['first_name'] }}"
                                        data-user-last-name="{{ $customerName['last_name'] }}"
                                        data-user-email="{{ $customer->email }}"
                                        data-user-phone="{{ $customer->phone ?? '' }}"
                                        data-user-address="{{ $customer->address_line ?? '' }}"
                                        data-user-city="{{ $customer->city ?? '' }}"
                                        data-user-province="{{ $customer->province ?? '' }}"
                                        data-user-country="{{ $customer->country ?? '' }}"
                                    >
                                        <i class="bi bi-pencil-square"></i>
                                        <span>Edit</span>
                                    </button>
                                    <form method="POST" action="{{ route('admin.users.destroy', $customer) }}" onsubmit="return confirm('Delete this customer account? This cannot be undone.');">
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
                            <td colspan="7" class="text-center py-4">No customer accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $customers->links() }}
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editUserForm" method="POST" action="{{ route('admin.users.update', ['user' => '__USER__']) }}">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_user_modal_id" id="edit_user_modal_id">

                    <div class="modal-header">
                        <h5 class="modal-title" id="editUserModalLabel">Edit Customer Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First name</label>
                                <input type="text" name="first_name" id="edit_user_first_name" class="form-control @error('first_name') is-invalid @enderror" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last name</label>
                                <input type="text" name="last_name" id="edit_user_last_name" class="form-control @error('last_name') is-invalid @enderror" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" id="edit_user_email" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone (optional)</label>
                                <input type="text" name="phone" id="edit_user_phone" class="form-control @error('phone') is-invalid @enderror" placeholder="+63...">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Address line (optional)</label>
                                <input type="text" name="address_line" id="edit_user_address_line" class="form-control @error('address_line') is-invalid @enderror" placeholder="Street, barangay, house number">
                                @error('address_line')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">City (optional)</label>
                                <input type="text" name="city" id="edit_user_city" class="form-control @error('city') is-invalid @enderror">
                                @error('city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Province (optional)</label>
                                <input type="text" name="province" id="edit_user_province" class="form-control @error('province') is-invalid @enderror">
                                @error('province')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Country (optional)</label>
                                <input type="text" name="country" id="edit_user_country" class="form-control @error('country') is-invalid @enderror" placeholder="Philippines">
                                @error('country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New password (optional)</label>
                                <input type="password" name="password" id="edit_user_password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm new password</label>
                                <input type="password" name="password_confirmation" id="edit_user_password_confirmation" class="form-control">
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
            const modalEl = document.getElementById('editUserModal');
            const formEl = document.getElementById('editUserForm');
            if (!modalEl || !formEl || typeof bootstrap === 'undefined') {
                return;
            }

            const updateUrlTemplate = @json(route('admin.users.update', ['user' => '__USER__']));
            const oldFormValues = {
                firstName: @json(old('first_name')),
                lastName: @json(old('last_name')),
                email: @json(old('email')),
                phone: @json(old('phone')),
                addressLine: @json(old('address_line')),
                city: @json(old('city')),
                province: @json(old('province')),
                country: @json(old('country')),
            };

            const fieldModalId = document.getElementById('edit_user_modal_id');
            const fieldFirstName = document.getElementById('edit_user_first_name');
            const fieldLastName = document.getElementById('edit_user_last_name');
            const fieldEmail = document.getElementById('edit_user_email');
            const fieldPhone = document.getElementById('edit_user_phone');
            const fieldAddressLine = document.getElementById('edit_user_address_line');
            const fieldCity = document.getElementById('edit_user_city');
            const fieldProvince = document.getElementById('edit_user_province');
            const fieldCountry = document.getElementById('edit_user_country');
            const fieldPassword = document.getElementById('edit_user_password');
            const fieldPasswordConfirmation = document.getElementById('edit_user_password_confirmation');

            modalEl.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!trigger) {
                    return;
                }

                const userId = trigger.getAttribute('data-user-id') || '';
                formEl.action = updateUrlTemplate.replace('__USER__', userId);
                fieldModalId.value = userId;
                fieldFirstName.value = trigger.getAttribute('data-user-first-name') || '';
                fieldLastName.value = trigger.getAttribute('data-user-last-name') || '';
                fieldEmail.value = trigger.getAttribute('data-user-email') || '';
                fieldPhone.value = trigger.getAttribute('data-user-phone') || '';
                fieldAddressLine.value = trigger.getAttribute('data-user-address') || '';
                fieldCity.value = trigger.getAttribute('data-user-city') || '';
                fieldProvince.value = trigger.getAttribute('data-user-province') || '';
                fieldCountry.value = trigger.getAttribute('data-user-country') || '';
                fieldPassword.value = '';
                fieldPasswordConfirmation.value = '';
            });

            const oldModalUserId = @json(old('_user_modal_id'));
            if (oldModalUserId) {
                const oldButton = document.querySelector(`.js-edit-user-btn[data-user-id="${oldModalUserId}"]`);
                if (oldButton) {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                    if (oldFormValues.firstName !== null) fieldFirstName.value = oldFormValues.firstName;
                    if (oldFormValues.lastName !== null) fieldLastName.value = oldFormValues.lastName;
                    if (oldFormValues.email !== null) fieldEmail.value = oldFormValues.email;
                    if (oldFormValues.phone !== null) fieldPhone.value = oldFormValues.phone;
                    if (oldFormValues.addressLine !== null) fieldAddressLine.value = oldFormValues.addressLine;
                    if (oldFormValues.city !== null) fieldCity.value = oldFormValues.city;
                    if (oldFormValues.province !== null) fieldProvince.value = oldFormValues.province;
                    if (oldFormValues.country !== null) fieldCountry.value = oldFormValues.country;
                    formEl.action = updateUrlTemplate.replace('__USER__', oldModalUserId);
                    fieldModalId.value = oldModalUserId;
                }
            }
        });
    </script>
@endpush
