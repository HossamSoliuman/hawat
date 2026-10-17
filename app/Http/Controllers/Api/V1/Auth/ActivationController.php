<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\Account\InvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * تفعيل حساب مدعوّ (كابتن أنشأه مالكه بلا كلمة مرور): الجوال ورمز الدعوة
 * الذي وصله، وكلمة مرور يختارها بنفسه. الردّ كردّ الدخول — رمز Sanctum
 * والمستخدم — فيدخل التطبيق مباشرة.
 */
class ActivationController extends Controller
{
    public function __construct(private readonly InvitationService $invitations) {}

    public function store(Request $request): JsonResponse
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
        ]);

        $user = $this->invitations->activate($data['phone'], $data['code'], $data['password']);

        $user->forceFill([
            'last_login_at' => now(),
            'fcm_token' => $data['fcm_token'] ?? $user->fcm_token,
        ])->save();

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('appRole', 'owner')),
            ],
            'message' => 'تم تفعيل حسابك وتسجيل الدخول.',
        ]);
    }
}
