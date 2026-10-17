<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * عمالة الدكة تُسجَّل أفرادًا: لكل عامل اسمه وجواله وجنسيته وهويته ونوعه
 * وحالته وصورته، ويُعطَّل بدل أن يُحذف.
 *
 * سطور الأعداد السابقة (النوع × الجنسية × العدد) لا تضيع: ينتقل جدولها إلى
 * `dalal_worker_counts`، وتبقى أعداد الدلال الظاهرة منها حتى يسجّل أول عامل
 * فردًا، ثم تُحسب الأعداد من السجلات (DalalProfile::workforce).
 *
 * قيود المفاتيح الأجنبية تُسقط قبل إعادة التسمية وتُعاد بعدها، لأن MySQL
 * يُبقي أسماءها القديمة (dalal_workers_*_foreign) فيصطدم بها الجدول الجديد.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dalal_workers', function (Blueprint $table) {
            $table->dropForeign(['dalal_id']);
            $table->dropForeign(['dalal_worker_type_id']);
        });

        Schema::rename('dalal_workers', 'dalal_worker_counts');

        Schema::table('dalal_worker_counts', function (Blueprint $table) {
            $table->foreign('dalal_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('dalal_worker_type_id')->references('id')->on('dalal_worker_types')->cascadeOnDelete();
        });

        Schema::create('dalal_workers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dalal_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('dalal_worker_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('nationality')->nullable();
            $table->foreignId('id_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('id_number', 50)->nullable();
            $table->string('status')->default('نشط');
            $table->string('avatar_path')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            // رقم الهوية لا يتكرر عند الدلال نفسه؛ والعامل قد يُسجَّل عند دلال آخر.
            $table->unique(['dalal_id', 'id_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dalal_workers');

        Schema::table('dalal_worker_counts', function (Blueprint $table) {
            $table->dropForeign(['dalal_id']);
            $table->dropForeign(['dalal_worker_type_id']);
        });

        Schema::rename('dalal_worker_counts', 'dalal_workers');

        Schema::table('dalal_workers', function (Blueprint $table) {
            $table->foreign('dalal_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('dalal_worker_type_id')->references('id')->on('dalal_worker_types')->cascadeOnDelete();
        });
    }
};
