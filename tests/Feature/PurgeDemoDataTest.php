<?php

namespace Tests\Feature;

use App\Console\Commands\PurgeDemoData;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * hawat:purge-demo-data يمسح البيانات التجريبية كلها ويُبقي المرجعية: يُشغَّل
 * هنا على البذر الكامل (بما فيه المالك والدلال وشركة التشغيل التجريبيون).
 */
class PurgeDemoDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_it_wipes_every_demo_record_and_keeps_the_reference_data(): void
    {
        Storage::disk('public')->put('avatars/demo.jpg', 'x');
        Storage::disk('public')->put('expenses/1/receipt.pdf', 'x');
        $this->assertGreaterThan(0, DB::table('trips')->count());
        $this->assertGreaterThan(0, DB::table('sales')->count());

        $kept = collect(['regions', 'governorates', 'ports', 'fishing_sites', 'species', 'gear_types', 'roles', 'boat_types', 'notification_types'])
            ->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()]);
        $superAdmin = Role::key(Role::SUPER_ADMIN)->id;

        $this->artisan('hawat:purge-demo-data', ['--force' => true])->assertSuccessful();

        foreach (PurgeDemoData::PURGE as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} لم يُفرَّغ");
        }

        foreach ($kept as $table => $count) {
            $this->assertSame($count, DB::table($table)->count(), "{$table} تغيّر");
        }

        // حسابات الوزارة والمدير العام فقط، ولا صلاحية بلا حساب.
        $this->assertSame(0, DB::table('users')->whereNotNull('role_id')->where('role_id', '!=', $superAdmin)->count());
        $this->assertTrue(DB::table('users')->where('email', 'admin@hawat.sa')->exists());
        $this->assertTrue(DB::table('users')->where('email', 'qatif@hawat.sa')->exists());
        $this->assertSame(0, DB::table('user_permissions')->whereNotIn('user_email', DB::table('users')->select('email'))->count());

        $this->assertSame(0.0, (float) DB::table('regions')->sum('total_catch_tons'));
        $this->assertSame(0, (int) DB::table('ports')->sum('daily_trips'));
        $this->assertSame(0, (int) DB::table('species')->sum('trips_count'));
        $this->assertGreaterThan(0, (float) DB::table('regions')->sum('coast_length_km'));

        Storage::disk('public')->assertMissing('avatars/demo.jpg');
        Storage::disk('public')->assertMissing('expenses/1/receipt.pdf');

        $backups = Storage::disk('local')->files('backups');
        $this->assertCount(1, $backups);
        $dump = json_decode(gzdecode(Storage::disk('local')->get($backups[0])), true);
        $this->assertNotEmpty($dump['tables']['trips']);
        $this->assertNotEmpty($dump['users']);
    }

    public function test_a_dry_run_only_lists_what_would_go(): void
    {
        $trips = DB::table('trips')->count();

        $this->artisan('hawat:purge-demo-data', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame($trips, DB::table('trips')->count());
        $this->assertEmpty(Storage::disk('local')->files('backups'));
    }

    public function test_it_refuses_to_run_while_a_table_is_unclassified(): void
    {
        Schema::create('mystery_records', function (Blueprint $table) {
            $table->id();
        });
        $trips = DB::table('trips')->count();

        $this->artisan('hawat:purge-demo-data', ['--force' => true])
            ->expectsOutputToContain('mystery_records')
            ->assertFailed();

        $this->assertSame($trips, DB::table('trips')->count());
    }
}
