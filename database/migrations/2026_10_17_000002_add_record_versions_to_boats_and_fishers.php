<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * رقم نسخة للسجلات التي يعدّلها أكثر من جهاز (القارب، والصياد كابتنًا أو
 * طاقمًا): يزيد مع كل تعديل (App\Models\Concerns\HasVersion)، والتعديل الذي
 * يرسل نسخة قديمة في If-Match يُرفض بـ409 بدل أن يكتب فوق تعديل غيره.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
        });
    }

    public function down(): void
    {
        Schema::table('boats', function (Blueprint $table) {
            $table->dropColumn('version');
        });

        Schema::table('fishers', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
