<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmedMail;
use App\Mail\BookingPaidMail;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Room;
use App\Models\RoomStatus;
use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStaffButtonFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-16 10:30:00');
        $this->seedCleaningStatuses();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_pages_and_actions_work(): void
    {
        Mail::fake();

        $admin = Admin::factory()->create();
        $staff = Staff::factory()->create(['admin_id' => $admin->id]);
        $customer = Customer::factory()->create();
        $orphanCustomer = Customer::factory()->create();
        $room = $this->createRoom();
        $pendingBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'pending',
            paymentStatus: 'unpaid'
        );
        $onlineBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'gcash',
            paymentExtras: [
                'qr_reference' => 'QR-ADMIN-001',
                'customer_reference' => 'GCASH-ADMIN-001',
            ]
        );

        $this->actingAs($admin, 'admin');

        $this->get(route('admin.dashboard'))->assertOk();
        $roomsIndexResponse = $this->get(route('admin.rooms.index'));
        $roomsIndexResponse->assertOk();
        $roomsIndexResponse->assertSee('Room Status');
        $roomsIndexResponse->assertDontSee('Cleaning Status');
        $roomsIndexResponse->assertDontSee('Needs Attention');
        $roomsIndexResponse->assertDontSee('Needs Cleaning');
        $this->get(route('admin.staff.index'))->assertOk();
        $this->get(route('admin.users.index'))->assertOk();
        $this->get(route('admin.bookings.show', $pendingBooking))->assertOk();

        $this->post(route('admin.staff.store'), [
            'name' => 'Desk Staff',
            'email' => 'desk.staff@example.com',
            'phone' => '09170001111',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.staff.index'));

        $createdStaff = Staff::query()->where('email', 'desk.staff@example.com')->firstOrFail();
        $this->assertSame($admin->id, $createdStaff->admin_id);

        $this->put(route('admin.staff.update', $createdStaff), [
            'name' => 'Desk Staff Updated',
            'email' => 'desk.staff.updated@example.com',
            'phone' => '09170002222',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.staff.index'));

        $createdStaff->refresh();
        $this->assertSame('Desk Staff Updated', $createdStaff->name);

        $this->patch(route('admin.bookings.assign-staff', $pendingBooking), [
            'staff_id' => $staff->id,
        ])->assertRedirect(route('admin.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame($staff->id, $pendingBooking->staff_id);

        $this->patch(route('admin.bookings.update-status', $pendingBooking), [
            'status' => 'confirmed',
        ])->assertRedirect(route('admin.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame('confirmed', $pendingBooking->status);
        $this->assertSame($staff->id, $pendingBooking->staff_id);
        Mail::assertQueued(BookingConfirmedMail::class);

        $dirtyStatusId = (int) RoomStatus::query()->where('slug', 'dirty')->value('room_status_id');
        $this->patch(route('admin.rooms.update-room-status', $room), [
            'room_status_id' => $dirtyStatusId,
        ])->assertRedirect();

        $room->refresh();
        $this->assertSame($dirtyStatusId, $room->room_status_id);
        $this->assertSame($admin->id, $room->admin_id);
        $this->assertNotNull($room->status_updated_at);

        $this->patch(route('admin.bookings.approve-online-payment', $onlineBooking))
            ->assertRedirect(route('admin.bookings.show', $onlineBooking));

        $onlineBooking->refresh();
        $onlineBooking->load('payment');
        $this->assertSame('paid', $onlineBooking->payment->status);
        $this->assertNotNull($onlineBooking->payment->transaction_reference);
        Mail::assertQueued(BookingPaidMail::class);

        $this->put(route('admin.users.update', $orphanCustomer), [
            'name' => 'Updated Customer',
            'email' => 'updated.customer@example.com',
            'phone' => '09179999999',
            'address_line' => 'Updated Street',
            'city' => 'Calamba',
            'province' => 'Laguna',
            'country' => 'Philippines',
            'password' => '',
            'password_confirmation' => '',
        ])->assertRedirect(route('admin.users.index'));

        $orphanCustomer->refresh();
        $this->assertSame('Updated Customer', $orphanCustomer->name);

        $this->delete(route('admin.users.destroy', $orphanCustomer))
            ->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('customers', ['customer_id' => $orphanCustomer->id]);

        $this->delete(route('admin.staff.destroy', $createdStaff))
            ->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseMissing('staff', ['staff_id' => $createdStaff->id]);
    }

    public function test_staff_pages_and_operational_actions_work(): void
    {
        Mail::fake();

        $staff = Staff::factory()->create();
        $customer = Customer::factory()->create();
        $room = $this->createRoom();
        $walkInRoom = $this->createRoom(['name' => 'Walk-in Room 1']);

        $pendingBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'pending',
            paymentStatus: 'unpaid'
        );
        $activeBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'unpaid'
        );
        $onlineBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'gcash',
            paymentExtras: [
                'qr_reference' => 'QR-STAFF-001',
                'customer_reference' => 'GCASH-STAFF-001',
            ]
        );
        $rejectedBooking = $this->createBooking(
            customer: $customer,
            room: $room,
            status: 'confirmed',
            paymentStatus: 'pending_verification',
            paymentMethod: 'paymaya',
            paymentExtras: [
                'qr_reference' => 'QR-STAFF-REJECT',
                'customer_reference' => 'PAYMAYA-STAFF-001',
            ]
        );

        $this->actingAs($staff, 'staff');

        $dashboardResponse = $this->get(route('staff.dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertDontSee(route('staff.bookings.create'), false);
        $dashboardResponse->assertDontSee('/staff/rooms', false);
        $this->get(route('staff.arrivals'))->assertOk();
        $bookingsIndexResponse = $this->get(route('staff.bookings.index'));
        $bookingsIndexResponse->assertOk();
        $bookingsIndexResponse->assertSee(route('staff.bookings.create'), false);
        $this->get(route('staff.bookings.show', [
            'booking' => $activeBooking,
            'return_to' => '/staff/bookings?queue=arrivals_today',
        ]))
            ->assertOk()
            ->assertSee('href="/staff/bookings?queue=arrivals_today"', false);
        $this->get(route('staff.bookings.create'))->assertOk();
        $this->get('/staff/rooms')->assertNotFound();

        $this->patch(route('staff.bookings.confirm', $pendingBooking))
            ->assertRedirect(route('staff.bookings.show', $pendingBooking));

        $pendingBooking->refresh();
        $this->assertSame('confirmed', $pendingBooking->status);
        $this->assertSame($staff->id, $pendingBooking->staff_id);
        Mail::assertQueued(BookingConfirmedMail::class);

        $this->patch(route('staff.bookings.staff-notes', $activeBooking), [
            'staff_notes' => 'Front desk verified the guest documents.',
        ])->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $this->assertSame('Front desk verified the guest documents.', $activeBooking->staff_notes);

        $this->patch(route('staff.bookings.record-payment', $activeBooking), [
            'method' => 'cash',
            'discount_type' => 'none',
        ])->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $activeBooking->load('payment');
        $this->assertSame('paid', $activeBooking->payment->status);
        $this->assertSame('cash', $activeBooking->payment->method);
        $this->assertSame($staff->id, $activeBooking->staff_id);
        Mail::assertQueued(BookingPaidMail::class);

        $this->patch(route('staff.bookings.check-in', $activeBooking), [
            'return_to' => '/staff/bookings?queue=arrivals_today',
        ])->assertRedirect('/staff/bookings?queue=arrivals_today');

        $activeBooking->refresh();
        $this->assertNotNull($activeBooking->actual_check_in_at);

        Carbon::setTestNow(Carbon::now()->addHour());

        $this->patch(route('staff.bookings.check-out', $activeBooking), [])
            ->assertRedirect(route('staff.bookings.show', $activeBooking));

        $activeBooking->refresh();
        $this->assertSame('completed', $activeBooking->status);
        $this->assertNotNull($activeBooking->actual_check_out_at);

        $this->patch(route('staff.bookings.approve-online-payment', $onlineBooking))
            ->assertRedirect(route('staff.bookings.show', $onlineBooking));

        $onlineBooking->refresh();
        $onlineBooking->load('payment');
        $this->assertSame('paid', $onlineBooking->payment->status);
        $this->assertSame($staff->id, $onlineBooking->payment->staff_id);

        $this->patch(route('staff.bookings.reject-online-payment', $rejectedBooking))
            ->assertRedirect(route('staff.bookings.show', $rejectedBooking));

        $rejectedBooking->refresh();
        $rejectedBooking->load('payment');
        $this->assertSame('unpaid', $rejectedBooking->payment->status);
        $this->assertSame($staff->id, $rejectedBooking->payment->staff_id);
        $this->assertNull($rejectedBooking->payment->transaction_reference);

        $this->post(route('staff.bookings.store'), [
            'customer_name' => 'Walk In Guest',
            'customer_email' => 'walkin@example.com',
            'customer_phone' => '09178888888',
            'room_id' => $walkInRoom->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->copy()->addDay()->toDateString(),
            'guests' => 2,
            'payment_preference' => 'cash',
            'notes' => 'Walk-in from front desk.',
        ])->assertRedirect();

        $walkInBooking = Booking::query()
            ->where('room_id', $walkInRoom->id)
            ->whereNull('customer_id')
            ->latest('booking_id')
            ->first();

        $this->assertNotNull($walkInBooking);
        $this->assertSame($staff->id, $walkInBooking->staff_id);
    }

    private function seedCleaningStatuses(): void
    {
        DB::table('room_status')->insert([
            [
                'name' => 'Clean',
                'slug' => 'clean',
                'description' => 'Ready for booking',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dirty',
                'slug' => 'dirty',
                'description' => 'Currently dirty',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Make Up Room',
                'slug' => 'being_cleaned',
                'description' => 'Currently being cleaned',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Inspected',
                'slug' => 'inspected',
                'description' => 'Checked and ready',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function createRoom(array $attributes = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'room_status_id' => $cleanStatusId,
        ], $attributes));
    }

    private function createBooking(
        Customer $customer,
        Room $room,
        string $status,
        string $paymentStatus,
        string $paymentMethod = 'pending',
        array $paymentExtras = []
    ): Booking {
        $booking = Booking::query()->create([
            'customer_id' => $customer->id,
            'room_id' => $room->id,
            'check_in' => Carbon::now()->toDateString(),
            'check_out' => Carbon::now()->copy()->addDay()->toDateString(),
            'status' => $status,
            'notes' => 'Automated test booking',
        ]);

        $booking->guestDetail()->create([
            'first_name' => 'Test',
            'last_name' => 'Guest',
            'email' => $customer->email,
            'phone' => $customer->phone,
            'adults' => 2,
            'kids' => 0,
            'payment_preference' => $paymentMethod === 'pending' ? 'cash' : $paymentMethod,
        ]);

        $paymentPayload = array_merge([
            'amount' => $room->price_per_night,
            'method' => $paymentMethod,
            'status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? now() : null,
        ], $paymentExtras);

        $booking->payment()->create($paymentPayload);

        return $booking->fresh(['payment', 'guestDetail', 'room', 'user']);
    }
}
