<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ترويسة Idempotency-Key على طلبات POST في التطبيق: ضاع ردّ الطلب الأول
 * فأعاده التطبيق بالمفتاح نفسه → يعود الردّ الأول نفسه (بترويسة
 * Idempotency-Replayed) ولا يُنشأ سجلّ ثانٍ.
 *
 * - المفتاح لكل حساب، ويُحفظ ردّه يومًا (IdempotencyKey::TTL_HOURS).
 * - المفتاح نفسه لطلب مختلف (مسار أو محتوى) → 422.
 * - الطلب الأول ما زال يُنفَّذ → 409، يعيد التطبيق المحاولة بعد لحظات.
 * - الردّ غير الناجح (422 مثلًا) لا يُحفظ: يصحّح التطبيق ويعيد بالمفتاح نفسه.
 * - بلا ترويسة يمرّ الطلب كما كان.
 */
class Idempotent
{
    public const HEADER = 'Idempotency-Key';

    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header(self::HEADER);

        if (! $request->isMethod('POST') || $key === null || $request->user() === null) {
            return $next($request);
        }

        if (! preg_match('/^[\x21-\x7E]{1,255}$/', $key)) {
            return $this->error(422, 'ترويسة Idempotency-Key يجب أن تكون من 1 إلى 255 حرفًا إنجليزيًا أو رقمًا بلا مسافات.');
        }

        $attributes = [
            'user_id' => $request->user()->id,
            'key' => $key,
            'method' => $request->method(),
            'path' => $request->path(),
            'fingerprint' => $this->fingerprint($request),
        ];

        try {
            $record = IdempotencyKey::create($attributes);
        } catch (UniqueConstraintViolationException) {
            $record = IdempotencyKey::where('user_id', $attributes['user_id'])->where('key', $key)->first();

            if ($record === null) {
                return $this->handle($request, $next);
            }

            if ($record->path !== $attributes['path'] || $record->method !== $attributes['method'] || $record->fingerprint !== $attributes['fingerprint']) {
                return $this->error(422, 'مفتاح Idempotency-Key هذا استُعمل لطلب مختلف — أنشئ مفتاحًا جديدًا لكل عملية.');
            }

            if ($record->isCompleted()) {
                // JsonResponse لا نص خام، فيخرج الردّ المعاد بترميز الردّ الأول نفسه (ForceJsonResponse).
                return JsonResponse::fromJsonString($record->response_body, $record->status_code, ['Idempotency-Replayed' => 'true']);
            }

            if (! $record->isStale()) {
                return $this->error(409, 'الطلب نفسه ما زال قيد التنفيذ — أعد المحاولة بعد لحظات.');
            }

            // الطلب الأول تعطّل في منتصفه: هذا الطلب يكمل مكانه.
            $record->forceFill(['created_at' => now()])->save();
        }

        $response = $next($request);

        if ($response->isSuccessful()) {
            $record->update(['status_code' => $response->getStatusCode(), 'response_body' => $response->getContent()]);
        } else {
            $record->delete();
        }

        return $response;
    }

    /**
     * بصمة المحتوى: الحقول كما أُرسلت، ومحتوى كل ملف مرفوع.
     */
    private function fingerprint(Request $request): string
    {
        $files = collect($request->allFiles())->flatten()->map(fn ($file) => hash_file('sha256', $file->getRealPath()))->all();

        return hash('sha256', json_encode([$request->input(), $files]));
    }

    private function error(int $status, string $message): Response
    {
        return response()->json(['message' => $message, 'errors' => [self::HEADER => [$message]]], $status);
    }
}
