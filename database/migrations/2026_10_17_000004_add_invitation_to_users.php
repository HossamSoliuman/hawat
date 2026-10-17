<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * دعوة الكابتن دون كلمة مرور: المالك ينشئ الحساب فيُرسل إلى جوال الكابتن رمز
 * تفعيل، والكابتن يختار كلمة مروره بنفسه (POST /auth/activate).
 *
 * - `invited_at`: آخر إرسال للدعوة — منه تُحسب صلاحيتها (User::INVITATION_HOURS).
 * - `activated_at`: لحظة تفعيل الحساب. حساب أُنشئ بكلمة مرور مفعّل من لحظته،
 *   وحسابات التطبيق السابقة تُعدّ مفعّلة منذ إنشائها.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('invited_at')->nullable()->after('last_login_at');
            $table->timestamp('activated_at')->nullable()->after('invited_at');
        });

        DB::table('users')->whereNotNull('role_id')->update(['activated_at' => DB::raw('created_at')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['invited_at', 'activated_at']);
        });
    }
};
