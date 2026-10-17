<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * مفاتيح Idempotency-Key لطلبات POST في التطبيق: الطلب الأول يُنفَّذ ويُحفظ
 * ردّه، وتكراره بالمفتاح نفسه (بعد ضياع الردّ) يعيد الردّ المحفوظ ولا يُنشئ
 * سجلًا ثانيًا. المفتاح لكل حساب، ويُحذف بعد يوم (App\Models\IdempotencyKey).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('method', 10);
            $table->string('path');
            $table->char('fingerprint', 64);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
    }
};
