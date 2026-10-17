<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * سجلّ الصياد (كابتن أو طاقم) يكمل وثائقه كما يطلبها التطبيق:
 *
 * - `iqama_issue_date` / `iqama_expiry_date`: بداية الإقامة وانتهاؤها، لأي نوع
 *   هوية — العامل المسجّل بجوازه قد يحمل إقامة أيضًا.
 * - `license_date`: بداية رخصة الصيد، بجانب `license_expiry` الموجود.
 * - `avatar_path`: الصورة الشخصية لفرد الطاقم يرفعها المالك. صورة الكابتن في
 *   حسابه (`users.avatar_path`) لأنه يراها ويغيّرها من تطبيقه.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fishers', function (Blueprint $table) {
            $table->date('iqama_issue_date')->nullable()->after('id_type_id');
            $table->date('iqama_expiry_date')->nullable()->after('iqama_issue_date');
            $table->date('license_date')->nullable()->after('license_status');
            $table->string('avatar_path')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('fishers', function (Blueprint $table) {
            $table->dropColumn(['iqama_issue_date', 'iqama_expiry_date', 'license_date', 'avatar_path']);
        });
    }
};
