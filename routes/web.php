<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoomController as AdminRoomController;
use App\Http\Controllers\Admin\StaffController as AdminStaffController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\Staff\BookingController as StaffBookingController;
use App\Http\Controllers\Staff\DashboardController as StaffDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
Route::get('/rooms/search', [RoomController::class, 'search'])->name('rooms.search');
Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/gallery', [PageController::class, 'gallery'])->name('gallery');
Route::get('/terms-and-conditions', [PageController::class, 'terms'])->name('terms');
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::middleware('guest:admin,staff,customer')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.perform');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.perform');
    Route::get('/register/verify', [AuthController::class, 'showRegisterVerification'])->name('register.verify');
    Route::post('/register/verify', [AuthController::class, 'verifyRegisterCode'])->name('register.verify.perform');
    Route::post('/register/verify/resend', [AuthController::class, 'resendRegisterCode'])->name('register.verify.resend');

    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetCode'])->name('password.email');
    Route::get('/reset-password/{email}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password/verify', [AuthController::class, 'verifyResetCode'])->name('password.verify');
    Route::get('/reset-password/{email}/new', [AuthController::class, 'showNewPasswordForm'])->name('password.reset.new');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    Route::get('/auth/google/redirect/login', [AuthController::class, 'redirectToGoogleForLogin'])->name('auth.google.redirect.login');
    Route::get('/auth/google/redirect/register', [AuthController::class, 'redirectToGoogleForRegister'])->name('auth.google.redirect.register');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->middleware('auth:admin,staff,customer')->name('logout');

Route::middleware('auth:admin,staff,customer')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/security', [ProfileController::class, 'security'])->name('profile.security');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

Route::middleware('auth:customer')->group(function () {
    Route::get('/rooms/{room}/book', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings/my', [BookingController::class, 'myBookings'])->name('bookings.my');
    Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/receipt', [BookingController::class, 'receipt'])->name('bookings.receipt');
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('/bookings/{booking}/request-reschedule', [BookingController::class, 'requestReschedule'])->name('bookings.request-reschedule');
    Route::get('/bookings/{booking}/success', [BookingController::class, 'success'])->name('bookings.success');

    Route::get('/payments/{booking}/checkout', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::post('/payments/{booking}/process', [PaymentController::class, 'process'])->name('payments.process');
});

Route::prefix('admin')->name('admin.')->middleware(['auth:admin', 'admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/sales-report', [DashboardController::class, 'salesReport'])->name('sales-report');

    Route::resource('rooms', AdminRoomController::class)->except(['show']);
    Route::patch('/rooms/{room}/room-status', [AdminRoomController::class, 'updateRoomStatus'])->name('rooms.update-room-status');
    Route::post('/rooms/date-discounts/bulk', [AdminRoomController::class, 'applyBulkDateDiscount'])->name('rooms.date-discounts.bulk');
    Route::get('/rooms/date-discounts', [AdminRoomController::class, 'dateDiscountsIndex'])->name('rooms.date-discounts.index');
    Route::patch('/rooms/date-discounts/range', [AdminRoomController::class, 'updateDateDiscountRange'])->name('rooms.date-discounts.range.update');

    Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])->name('bookings.show');
    Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.update-status');
    Route::patch('/bookings/{booking}/assign-staff', [AdminBookingController::class, 'assignStaff'])->name('bookings.assign-staff');
    Route::patch('/bookings/{booking}/approve-online-payment', [AdminBookingController::class, 'approveOnlinePayment'])->name('bookings.approve-online-payment');
    Route::patch('/bookings/{booking}/reject-online-payment', [AdminBookingController::class, 'rejectOnlinePayment'])->name('bookings.reject-online-payment');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    Route::get('/staff', [AdminStaffController::class, 'index'])->name('staff.index');
    Route::get('/staff/create', [AdminStaffController::class, 'create'])->name('staff.create');
    Route::post('/staff', [AdminStaffController::class, 'store'])->name('staff.store');
    Route::get('/staff/{staff}', [AdminStaffController::class, 'show'])->name('staff.show');
    Route::get('/staff/{staff}/edit', [AdminStaffController::class, 'edit'])->name('staff.edit');
    Route::put('/staff/{staff}', [AdminStaffController::class, 'update'])->name('staff.update');
    Route::delete('/staff/{staff}', [AdminStaffController::class, 'destroy'])->name('staff.destroy');
});

Route::prefix('staff')->name('staff.')->middleware(['auth:staff', 'staff'])->group(function () {
    Route::get('/dashboard', [StaffDashboardController::class, 'index'])->name('dashboard');
    Route::get('/arrivals', [StaffBookingController::class, 'arrivals'])->name('arrivals');
    Route::get('/bookings/create', [StaffBookingController::class, 'create'])->name('bookings.create');
    Route::post('/bookings', [StaffBookingController::class, 'store'])->name('bookings.store');
    Route::get('/bookings', [StaffBookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [StaffBookingController::class, 'show'])->name('bookings.show');
    Route::get('/bookings/{booking}/receipt', [StaffBookingController::class, 'receipt'])->name('bookings.receipt');
    Route::patch('/bookings/{booking}/confirm', [StaffBookingController::class, 'confirm'])->name('bookings.confirm');
    Route::patch('/bookings/{booking}/cancel', [StaffBookingController::class, 'cancel'])->name('bookings.cancel');
    Route::patch('/bookings/{booking}/check-in', [StaffBookingController::class, 'checkIn'])->name('bookings.check-in');
    Route::patch('/bookings/{booking}/check-out', [StaffBookingController::class, 'checkOut'])->name('bookings.check-out');
    Route::patch('/bookings/{booking}/record-payment', [StaffBookingController::class, 'recordPayment'])->name('bookings.record-payment');
    Route::patch('/bookings/{booking}/approve-online-payment', [StaffBookingController::class, 'approveOnlinePayment'])->name('bookings.approve-online-payment');
    Route::patch('/bookings/{booking}/reject-online-payment', [StaffBookingController::class, 'rejectOnlinePayment'])->name('bookings.reject-online-payment');
    Route::patch('/bookings/{booking}/transfer-room', [StaffBookingController::class, 'transferRoom'])->name('bookings.transfer-room');
    Route::patch('/bookings/{booking}/reschedule', [StaffBookingController::class, 'reschedule'])->name('bookings.reschedule');
    Route::patch('/bookings/{booking}/apply-reschedule-request', [StaffBookingController::class, 'applyRescheduleRequest'])->name('bookings.apply-reschedule-request');
    Route::patch('/bookings/{booking}/decline-reschedule-request', [StaffBookingController::class, 'declineRescheduleRequest'])->name('bookings.decline-reschedule-request');
    Route::patch('/bookings/{booking}/staff-notes', [StaffBookingController::class, 'updateStaffNotes'])->name('bookings.staff-notes');
    Route::patch('/bookings/{booking}/status', [StaffBookingController::class, 'updateStatus'])->name('bookings.update-status');
});
