<?php

namespace Tests\Unit;

use App\Models\Room;
use App\Models\RoomDateDiscount;
use App\Models\RoomStatus;
use App\Services\PricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PricingServiceExtraBeddingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['pricing.extra_bedding_fee_per_night' => 500]);

        DB::table('room_status')->insert([
            'name' => 'Clean',
            'slug' => 'clean',
            'description' => 'Ready for booking',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_quote_stay_adds_extra_bedding_fee_per_night(): void
    {
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        $quote = app(PricingService::class)->quoteStay(
            $room,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            3
        );

        $this->assertSame(1, $quote['extra_bedding_count']);
        $this->assertSame(1000.0, $quote['extra_bedding_total']);
        $this->assertSame(5000.0, $quote['total']);
        $this->assertSame(2500.0, $quote['average_nightly_rate']);
    }

    public function test_quote_stay_combines_date_discounts_and_extra_bedding_surcharge(): void
    {
        $room = $this->createRoom([
            'price_per_night' => 2000,
        ]);

        RoomDateDiscount::query()->create([
            'room_id' => $room->id,
            'discount_date' => now()->addDay()->toDateString(),
            'discount_percent' => 25,
        ]);

        $quote = app(PricingService::class)->quoteStay(
            $room,
            now()->addDay()->toDateString(),
            now()->addDays(3)->toDateString(),
            3
        );

        $this->assertSame(3500.0, $quote['room_total']);
        $this->assertSame(500.0, $quote['discount_amount']);
        $this->assertSame(1000.0, $quote['extra_bedding_total']);
        $this->assertSame(4500.0, $quote['total']);
    }

    private function createRoom(array $overrides = []): Room
    {
        $cleanStatusId = (int) RoomStatus::query()->where('slug', 'clean')->value('room_status_id');

        return Room::factory()->create(array_merge([
            'capacity' => 2,
            'room_status_id' => $cleanStatusId,
        ], $overrides));
    }
}
