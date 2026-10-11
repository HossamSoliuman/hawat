<?php

namespace Tests\Feature;

use App\Models\Boat;
use App\Models\Governorate;
use App\Models\Market;
use App\Models\Port;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * حذف منطقة من مركز المعلومات: يتسلسل إلى محافظاتها وموانئها وأسواقها، ويُرفض
 * ما دام في موانئها قارب أو صياد أو رحلة أو سجل لشركة تشغيل.
 */
class RegionDeletionTest extends TestCase
{
    use RefreshDatabase;

    private Region $region;

    private Port $port;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->actingAs(User::factory()->superAdmin()->create());

        $this->region = Region::factory()->create(['name' => 'منطقة الاختبار']);
        $governorate = Governorate::factory()->for($this->region)->create();
        $this->port = Port::factory()->for($governorate)->create();
    }

    public function test_an_unused_region_goes_with_its_governorates_ports_and_markets(): void
    {
        $other = Region::factory()->create();
        Market::create(['name' => 'سوق الاختبار', 'region' => 'منطقة الاختبار', 'governorate' => 'محافظة']);
        Market::create(['name' => 'سوق باقٍ', 'region' => $other->name, 'governorate' => 'محافظة']);

        $this->delete(route('regions.destroy', $this->region))
            ->assertRedirect(route('regions'))
            ->assertSessionHas('status');

        $this->assertModelMissing($this->region);
        $this->assertModelMissing($this->port);
        $this->assertSame(0, Governorate::where('region_id', $this->region->id)->count());
        $this->assertFalse(Market::where('name', 'سوق الاختبار')->exists());
        $this->assertTrue(Market::where('name', 'سوق باقٍ')->exists());
        $this->assertModelExists($other);
    }

    public function test_a_region_whose_ports_hold_records_is_kept_and_the_reason_shown(): void
    {
        $boat = Boat::factory()->for($this->port)->create();

        $this->delete(route('regions.destroy', $this->region))
            ->assertRedirect(route('regions'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, '1 قوارب'));

        $this->assertModelExists($this->region);
        $this->assertModelExists($this->port);
        $this->assertModelExists($boat);

        $this->get(route('regions'))->assertOk()->assertSee('لا يمكن حذف منطقة');
    }
}
