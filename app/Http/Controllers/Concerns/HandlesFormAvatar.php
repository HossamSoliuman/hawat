<?php

namespace App\Http\Controllers\Concerns;

use App\Http\Requests\Account\AvatarRequest;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

/**
 * نماذج اللوحة التي ترفع الصورة الشخصية مع بقية الحقول: تُفحص الصورة قبل
 * الحفظ (فلا يُحفظ سجلّ بصورة مرفوضة)، ثم تُرفع أو تُحذف بمربع "حذف الصورة".
 */
trait HandlesFormAvatar
{
    protected function validateFormAvatar(Request $request): void
    {
        $request->validate(['avatar' => ['nullable', ...AvatarRequest::RULES]], AvatarRequest::MESSAGES);
    }

    /**
     * @param  callable(UploadedFile): mixed  $store
     * @param  callable(): mixed  $remove
     */
    protected function syncFormAvatar(Request $request, callable $store, callable $remove): void
    {
        if ($request->hasFile('avatar')) {
            $store($request->file('avatar'));
        } elseif ($request->boolean('remove_avatar')) {
            $remove();
        }
    }
}
