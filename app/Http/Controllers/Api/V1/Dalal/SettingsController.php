<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\LogoRequest;
use App\Http\Requests\Dalal\SettingsRequest;
use App\Http\Requests\Dalal\WorkersRequest;
use App\Http\Resources\Api\DalalProfileResource;
use App\Models\DalalProfile;
use App\Models\User;
use App\Services\Dalal\DalalProfileService;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private readonly DalalProfileService $profiles) {}

    public function show(Request $request): DalalProfileResource
    {
        return $this->resource($request->user());
    }

    public function update(SettingsRequest $request): DalalProfileResource
    {
        $this->profiles->update($request->user(), $request->validated());

        return $this->resource($request->user());
    }

    public function logo(LogoRequest $request): DalalProfileResource
    {
        $this->profiles->storeLogo($request->user(), $request->file('logo'));

        return $this->resource($request->user());
    }

    public function removeLogo(Request $request): DalalProfileResource
    {
        $this->profiles->removeLogo($request->user());

        return $this->resource($request->user());
    }

    public function workers(WorkersRequest $request): DalalProfileResource
    {
        $this->profiles->syncWorkerCounts($request->user(), $request->validated('workers'));

        return $this->resource($request->user());
    }

    private function resource(User $dalal): DalalProfileResource
    {
        $profile = DalalProfile::forUser($dalal)->load(['region', 'governorate', 'port', 'user']);
        // الملف يُنشأ عند أول قراءة، والردّ يبقى 200 لا 201 — ليس إنشاءً طلبه التطبيق.
        $profile->wasRecentlyCreated = false;

        return new DalalProfileResource($profile);
    }
}
