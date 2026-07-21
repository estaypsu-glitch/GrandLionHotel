<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Room;
use App\Models\RoomDateDiscount;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoomDateDiscountsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-04-19 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_date_discounts_page_defaults_to_first_upcoming_discount_window_when_next_90_days_is_empty(): void
    {
        $admin = Admin::factory()->create();
        $roomA = Room::factory()->create([
            'name' => 'Room 301',
            'type' => 'Suite',
            'price_per_night' => 5000,
        ]);
        $roomB = Room::factory()->create([
            'name' => 'Room 302',
            'type' => 'Suite',
            'price_per_night' => 5500,
        ]);

        $startDate = Carbon::parse('2026-12-19');
        $dates = collect(range(0, 6))->map(
            static fn (int $offset): string => $startDate->copy()->addDays($offset)->toDateString()
        );

        foreach ([$roomA, $roomB] as $room) {
            foreach ($dates as $discountDate) {
                RoomDateDiscount::query()->create([
                    'room_id' => $room->id,
                    'discount_date' => $discountDate,
                    'discount_percent' => 10,
                    'admin_id' => $admin->id,
                ]);
            }
        }

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.rooms.date-discounts.index'));

        $response->assertOk();
        $response->assertViewHas('from', '2026-12-19');
        $response->assertViewHas('to', '2027-03-19');
        $response->assertViewHas('summary', fn (array $summary): bool => $summary['entry_count'] === 14
            && $summary['date_count'] === 7
            && $summary['room_count'] === 2);
        $response->assertSee('Dec 19, 2026 - Dec 25, 2026');
    }

    public function test_admin_can_edit_discount_range_dates_and_percent(): void
    {
        $admin = Admin::factory()->create();
        $roomA = Room::factory()->create(['name' => 'Room 401']);
        $roomB = Room::factory()->create(['name' => 'Room 402']);

        $originalDates = collect(range(0, 6))->map(
            static fn (int $offset): string => Carbon::parse('2026-12-19')->addDays($offset)->toDateString()
        );

        foreach ([$roomA, $roomB] as $room) {
            foreach ($originalDates as $discountDate) {
                RoomDateDiscount::query()->create([
                    'room_id' => $room->id,
                    'discount_date' => $discountDate,
                    'discount_percent' => 10,
                    'admin_id' => $admin->id,
                ]);
            }
        }

        $response = $this->actingAs($admin, 'admin')->patch(
            route('admin.rooms.date-discounts.range.update'),
            [
                'original_start_date' => '2026-12-19',
                'original_end_date' => '2026-12-25',
                'start_date' => '2026-12-20',
                'end_date' => '2026-12-27',
                'room_ids' => [$roomA->id, $roomB->id],
                'discount_percent' => 15,
                'from' => '2026-12-19',
                'to' => '2027-03-19',
            ]
        );

        $response->assertRedirect(route('admin.rooms.date-discounts.index', [
            'from' => '2026-12-19',
            'to' => '2027-03-19',
        ]));

        $this->assertDatabaseMissing('room_date_discounts', [
            'room_id' => $roomA->id,
            'discount_date' => '2026-12-19',
        ]);
        $this->assertDatabaseHas('room_date_discounts', [
            'room_id' => $roomA->id,
            'discount_date' => '2026-12-27',
            'discount_percent' => 15,
        ]);
        $this->assertDatabaseHas('room_date_discounts', [
            'room_id' => $roomB->id,
            'discount_date' => '2026-12-27',
            'discount_percent' => 15,
        ]);
    }

    public function test_admin_can_delete_discount_range(): void
    {
        $admin = Admin::factory()->create();
        $roomA = Room::factory()->create(['name' => 'Room 501']);
        $roomB = Room::factory()->create(['name' => 'Room 502']);

        foreach ([$roomA, $roomB] as $room) {
            foreach (range(0, 2) as $offset) {
                RoomDateDiscount::query()->create([
                    'room_id' => $room->id,
                    'discount_date' => Carbon::parse('2026-12-19')->addDays($offset)->toDateString(),
                    'discount_percent' => 12,
                    'admin_id' => $admin->id,
                ]);
            }
        }

        $response = $this->actingAs($admin, 'admin')->delete(
            route('admin.rooms.date-discounts.range.destroy'),
            [
                'original_start_date' => '2026-12-19',
                'original_end_date' => '2026-12-21',
                'room_ids' => [$roomA->id, $roomB->id],
                'from' => '2026-12-19',
                'to' => '2027-03-19',
            ]
        );

        $response->assertRedirect(route('admin.rooms.date-discounts.index', [
            'from' => '2026-12-19',
            'to' => '2027-03-19',
        ]));

        $this->assertDatabaseMissing('room_date_discounts', [
            'room_id' => $roomA->id,
            'discount_date' => '2026-12-19',
        ]);
        $this->assertDatabaseMissing('room_date_discounts', [
            'room_id' => $roomB->id,
            'discount_date' => '2026-12-21',
        ]);
    }

    public function test_bulk_date_discount_keeps_existing_discounted_dates_unchanged(): void
    {
        $admin = Admin::factory()->create();
        $room = Room::factory()->create(['name' => 'Room 601', 'type' => 'Suite']);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date' => '2026-12-20',
            'discount_percent' => 10,
            'admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->post(
            route('admin.rooms.date-discounts.bulk'),
            [
                'target_scope' => 'selected',
                'room_ids' => [$room->id],
                'discount_percent' => 15,
                'discount_start' => '2026-12-20',
                'discount_end' => '2026-12-21',
            ]
        );

        $response->assertRedirect(route('admin.rooms.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('room_date_discounts', [
            'room_id' => $room->id,
            'discount_date' => '2026-12-20',
            'discount_percent' => 10,
        ]);
        $this->assertDatabaseHas('room_date_discounts', [
            'room_id' => $room->id,
            'discount_date' => '2026-12-21',
            'discount_percent' => 15,
        ]);
    }
}
