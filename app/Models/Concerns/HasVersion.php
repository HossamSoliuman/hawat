<?php

namespace App\Models\Concerns;

use App\Exceptions\StaleRecordException;
use Illuminate\Http\Request;

/**
 * رقم نسخة يزيد مع كل تعديل فعلي على السجل — قفل متفائل للتعديلات المتزامنة.
 *
 * العميل يقرأ `version` (أو ترويسة ETag) ثم يرسلها مع التعديل في If-Match أو
 * حقل `version`؛ إن تغيّر السجل منذ قرأه يُرفض التعديل بـ409 ولا تُكتب قيمه.
 * العميل الذي لا يرسل نسخة يُقبل تعديله كما كان (النموذج كاملًا).
 */
trait HasVersion
{
    /**
     * السجل الجديد يبدأ بالنسخة 1 في الذاكرة كما في القاعدة.
     */
    public function initializeHasVersion(): void
    {
        $this->attributes['version'] ??= 1;
    }

    public static function bootHasVersion(): void
    {
        static::updating(function ($model) {
            if ($model->isDirty() && ! $model->isDirty('version')) {
                $model->version = (int) $model->getOriginal('version') + 1;
            }
        });
    }

    /**
     * النسخة التي يقول العميل إنه عدّل عليها: If-Match أولًا ثم حقل version.
     * ‎"3" و W/"3" و 3 كلها النسخة 3.
     */
    public static function expectedVersion(Request $request): ?int
    {
        $raw = $request->header('If-Match') ?? $request->input('version');

        if ($raw === null || $raw === '' || $raw === '*') {
            return null;
        }

        $value = trim(preg_replace('/^W\//', '', trim((string) $raw)), '"');

        return ctype_digit($value) ? (int) $value : -1;
    }

    /**
     * يرفض التعديل إن أرسل العميل نسخة غير الحالية. يُستدعى على سجلّ مقفول
     * (lockForUpdate) داخل المعاملة نفسها التي تكتب التعديل.
     */
    public function assertVersion(Request $request): void
    {
        $expected = static::expectedVersion($request);

        if ($expected !== null && $expected !== (int) $this->version) {
            throw new StaleRecordException((int) $this->version);
        }
    }

    public function etag(): string
    {
        return '"'.(int) $this->version.'"';
    }
}
