<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * تعديل على نسخة قديمة من السجل: جهاز آخر حفظ بعد أن قرأه هذا العميل.
 * التطبيق يأخذ 409 بالنسخة الحالية ليعيد القراءة، والويب يعود إلى النموذج
 * برسالة تطلب إعادة فتحه.
 */
class StaleRecordException extends RuntimeException
{
    public const MESSAGE = 'تغيّر هذا السجل منذ فتحته — أعد تحميله ثم عدّل عليه.';

    public function __construct(public readonly int $currentVersion)
    {
        parent::__construct(self::MESSAGE);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => self::MESSAGE,
                'current_version' => $this->currentVersion,
            ], 409, ['ETag' => '"'.$this->currentVersion.'"']);
        }

        return back()->withInput()->withErrors(['version' => self::MESSAGE]);
    }
}
