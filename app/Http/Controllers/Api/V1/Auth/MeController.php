<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\ProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Services\Account\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * حساب المستخدم الداخل: عرضه، وتعديل بياناته وصورته، وتغيير كلمة مروره،
 * ورمز إشعاراته. الطلبات والخدمة مشتركة مع صفحة الملف الشخصي في اللوحة.
 */
class MeController extends Controller
{
    private const WITH = ['appRole', 'owner', 'statisticsOfficer.port.governorate', 'dalalProfile.port.governorate'];

    public function __construct(private readonly ProfileService $profile) {}

    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load(self::WITH));
    }

    public function update(ProfileRequest $request): UserResource
    {
        return new UserResource($this->profile->update($request->user(), $request->validated())->load(self::WITH));
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        // تغيير كلمة المرور يُخرج بقية الأجهزة؛ الجهاز الحالي يبقى داخلًا.
        $this->profile->changePassword($request->user(), $request->validated('password'), $request->user()->currentAccessToken()->id);

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }

    public function updateAvatar(AvatarRequest $request): UserResource
    {
        return new UserResource($this->profile->storeAvatar($request->user(), $request->file('avatar'))->load(self::WITH));
    }

    public function removeAvatar(Request $request): UserResource
    {
        return new UserResource($this->profile->removeAvatar($request->user())->load(self::WITH));
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:500']]);

        $request->user()->update(['fcm_token' => $data['fcm_token']]);

        return response()->json(['message' => 'تم حفظ رمز الإشعارات.']);
    }
}
