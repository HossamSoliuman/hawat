<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Account\ChangePasswordRequest;
use App\Http\Requests\Account\ProfileRequest;
use App\Services\Account\ProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * الملف الشخصي كما في التطبيق: الاسم والدور والجوال والميناء وحالة الحساب،
 * وتعديل البيانات والصورة وكلمة المرور. شاشة واحدة لكل أدوار اللوحة.
 */
class ProfileController extends Controller
{
    public function __construct(private readonly ProfileService $profile) {}

    public function show(Request $request): View
    {
        return view('panel.profile.show', [
            'user' => $request->user()->load(['appRole', 'owner', 'statisticsOfficer.port.governorate', 'dalalProfile.port.governorate']),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $this->profile->update($request->user(), $request->validated());

        return redirect()->route('panel.profile')->with('status', 'تم حفظ الملف الشخصي.');
    }

    public function avatar(AvatarRequest $request): RedirectResponse
    {
        $this->profile->storeAvatar($request->user(), $request->file('avatar'));

        return redirect()->route('panel.profile')->with('status', 'تم تحديث الصورة.');
    }

    public function removeAvatar(Request $request): RedirectResponse
    {
        $this->profile->removeAvatar($request->user());

        return redirect()->route('panel.profile')->with('status', 'أُزيلت الصورة.');
    }

    public function password(ChangePasswordRequest $request): RedirectResponse
    {
        $this->profile->changePassword($request->user(), $request->validated('password'));

        return redirect()->route('panel.profile')->with('status', 'تم تغيير كلمة المرور.');
    }
}
