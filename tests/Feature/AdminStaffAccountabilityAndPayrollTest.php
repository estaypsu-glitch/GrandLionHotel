<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminStaffAccountabilityAndPayrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_confirmation_auto_sets_assigned_staff_for_accountability(): void
    {
        Mail::fake();

        $staff = User::factory()->staff()->create();

        $booking = Booking::factory()->create([
            'user_id' => null,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'staff_id' => null,
            'reservation_meta' => [
                'customer_name' => 'Accountability Guest',
            ],
        ]);

        $response = $this->actingAs($staff)->patch(route('staff.bookings.confirm', $booking));

        $response->assertRedirect(route('staff.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame($staff->id, $booking->staff_id);
    }

    public function test_admin_can_assign_staff_owner_to_booking(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();

        $booking = Booking::factory()->create([
            'staff_id' => null,
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.bookings.assign-staff', $booking), [
            'staff_id' => $staff->id,
        ]);

        $response->assertRedirect(route('admin.bookings.show', $booking));

        $booking->refresh();
        $this->assertSame($staff->id, $booking->staff_id);
    }

    public function test_admin_can_view_only_assigned_customers_for_specific_staff(): void
    {
        $admin = User::factory()->admin()->create();
        $staffA = User::factory()->staff()->create();
        $staffB = User::factory()->staff()->create();

        Booking::factory()->create([
            'user_id' => null,
            'staff_id' => $staffA->id,
            'reservation_meta' => [
                'customer_name' => 'Assigned Guest One',
                'customer_email' => 'assigned.one@example.com',
            ],
        ]);

        Booking::factory()->create([
            'user_id' => null,
            'staff_id' => $staffA->id,
            'reservation_meta' => [
                'customer_name' => 'Assigned Guest Two',
                'customer_email' => 'assigned.two@example.com',
            ],
        ]);

        Booking::factory()->create([
            'user_id' => null,
            'staff_id' => $staffB->id,
            'reservation_meta' => [
                'customer_name' => 'Other Staff Guest',
                'customer_email' => 'other.staff@example.com',
            ],
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staff.show', $staffA));

        $response->assertOk();
        $response->assertSee('Assigned Guest One');
        $response->assertSee('Assigned Guest Two');
        $response->assertDontSee('Other Staff Guest');
    }

    public function test_admin_can_update_staff_payroll_cycle_and_rate(): void
    {
        $admin = User::factory()->admin()->create();

        $staff = User::factory()->staff()->create([
            'salary_type' => User::SALARY_TYPE_DAILY,
            'salary_rate' => 750,
        ]);

        $response = $this->actingAs($admin)->put(route('admin.staff.update', $staff), [
            'name' => 'Updated Staff',
            'email' => $staff->email,
            'phone' => '+639171112222',
            'salary_type' => User::SALARY_TYPE_WEEKLY,
            'salary_rate' => 8500,
        ]);

        $response->assertRedirect(route('admin.staff.index'));

        $staff->refresh();
        $this->assertSame('Updated Staff', $staff->name);
        $this->assertSame(User::SALARY_TYPE_WEEKLY, $staff->salary_type);
        $this->assertSame('8500.00', number_format((float) $staff->salary_rate, 2, '.', ''));
    }
}
