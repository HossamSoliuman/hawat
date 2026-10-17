<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * الكابتن لا يدخل حوات بنفسه: صار سجلّ صياد عند المالك كالطاقم
 * (`fishers.is_captain`)، والمالك يدير الرحلة كلها عنه. لذلك:
 *
 * - سجلّ صياد كل كابتن يُعلَّم `is_captain`، ويشير `boats.captain_id`
 *   و`trips.captain_id` إلى سجلّه في fishers بدل حسابه في users.
 * - حسابات الكباتن تُحذف ومعها رموزها وجلساتها وإشعاراتها، ثم دور captain
 *   نفسه. كابتن بلا سجلّ صياد لم يكن له إلا حسابه، واسمه يبقى في الأعمدة
 *   النصية (boats.captain و trips.captain_name).
 * - دعوة الحساب (users.invited_at / activated_at ورموز account_invitation)
 *   كانت للكابتن وحده فتُزال، وكذلك fishers.user_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fishers', function (Blueprint $table) {
            $table->boolean('is_captain')->default(false)->after('owner_id');
            $table->index(['owner_id', 'is_captain']);
        });

        DB::table('fishers')->whereNotNull('user_id')->update(['is_captain' => true]);

        // حساب الكابتن → سجلّ صياده. ما لا سجلّ له يصير بلا كابتن (يبقى اسمه النصي).
        $fisherOfUser = DB::table('fishers')->whereNotNull('user_id')->pluck('id', 'user_id');

        foreach (['boats', 'trips'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropForeign(['captain_id']));

            foreach (DB::table($name)->whereNotNull('captain_id')->get(['id', 'captain_id']) as $row) {
                DB::table($name)->where('id', $row->id)->update(['captain_id' => $fisherOfUser[$row->captain_id] ?? null]);
            }

            Schema::table($name, fn (Blueprint $table) => $table->foreign('captain_id')->references('id')->on('fishers')->nullOnDelete());
        }

        Schema::table('fishers', fn (Blueprint $table) => $table->dropConstrainedForeignId('user_id'));

        $role = DB::table('roles')->where('key', 'captain')->value('id');

        if ($role !== null) {
            $captains = DB::table('users')->where('role_id', $role)->pluck('id');

            if ($captains->isNotEmpty()) {
                DB::table('personal_access_tokens')->where('tokenable_type', 'App\\Models\\User')->whereIn('tokenable_id', $captains)->delete();
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')->whereIn('user_id', $captains)->delete();
                }
                DB::table('users')->whereIn('id', $captains)->delete();
            }

            DB::table('roles')->where('id', $role)->delete();
        }

        DB::table('otp_codes')->where('purpose', 'account_invitation')->delete();

        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['invited_at', 'activated_at']));
    }

    /**
     * يعيد البنية لا البيانات: حسابات الكباتن المحذوفة لا تعود، فتبقى القوارب
     * والرحلات بلا كابتن مرتبط (أسماؤهم النصية باقية).
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invited_at')->nullable()->after('last_login_at');
            $table->timestamp('activated_at')->nullable()->after('invited_at');
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('owner_id')->constrained()->nullOnDelete();
        });

        foreach (['boats', 'trips'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropForeign(['captain_id']));
            DB::table($name)->update(['captain_id' => null]);
            Schema::table($name, fn (Blueprint $table) => $table->foreign('captain_id')->references('id')->on('users')->nullOnDelete());
        }

        Schema::table('fishers', function (Blueprint $table) {
            $table->dropIndex(['owner_id', 'is_captain']);
            $table->dropColumn('is_captain');
        });

        DB::table('roles')->insertOrIgnore([
            'key' => 'captain', 'name' => 'الكابتن', 'name_en' => 'Captain', 'has_portal' => true, 'active' => true,
            'description' => 'يبدأ الرحلة المسندة إليه ويسجّل مخرجاتها من التطبيق أو من بوابته.',
            'display_order' => 3, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
};
