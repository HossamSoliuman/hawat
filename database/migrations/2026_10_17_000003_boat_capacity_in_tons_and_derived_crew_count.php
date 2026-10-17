<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - `boats.capacity` = الحمولة بالطن، بكسرين (2.5 طن) بدل عدد صحيح.
 * - `boats.crew_count` لقوارب الملاك صار مشتقًّا: عدد أفراد الطاقم النشطين
 *   المسجّلين على القارب (Boat::refreshCrewCount). يُحسب هنا مرة لما سبق.
 *   قوارب الوزارة بلا مالك تبقى أرقامها المستوردة كما هي.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->decimal('capacity', 10, 2)->nullable()->change();
        });

        DB::table('boats')->whereNotNull('owner_id')->update([
            'crew_count' => DB::raw("(select count(*) from fishers where fishers.boat_id = boats.id and fishers.user_id is null and fishers.status = 'نشط')"),
        ]);
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->unsignedInteger('capacity')->nullable()->change();
        });
    }
};
