<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Owner\CaptainRequest;
use App\Http\Resources\Api\FisherResource;
use App\Models\Fisher;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * كباتن المالك: سجلات صيادين بلا حساب دخول (is_captain) بشكل فرد الطاقم
 * نفسه. المالك يسندهم إلى القوارب والرحلات ويدير الرحلة عنهم.
 */
class CaptainController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['boat', 'port', 'idType', 'fisherRole'];

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $captains = Fisher::forOwner($request->user())->captains()->with(self::WITH)
            ->when($request->filled('boat_id'), fn ($q) => $q->where('boat_id', $request->query('boat_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')
                ->orWhere('national_id', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return FisherResource::collection($captains);
    }

    public function store(CaptainRequest $request): JsonResponse
    {
        return $this->respond($this->staff->createCaptain($request->user(), $request->validated()), 201);
    }

    public function show(Request $request, int $captain): JsonResponse
    {
        return $this->respond($this->ownedCaptain($request->user(), $captain));
    }

    /**
     * If-Match (أو حقل version) بنسخة قديمة → 409 ولا يُكتب شيء.
     */
    public function update(CaptainRequest $request, int $captain): JsonResponse
    {
        $model = DB::transaction(function () use ($request, $captain) {
            $model = $this->lockedCaptain($request->user(), $captain);
            $model->assertVersion($request);

            return $this->staff->updateCaptain($model, $request->validated());
        });

        return $this->respond($model);
    }

    /**
     * يُرفع عن قاربه ورحلاته، واسمه يبقى على رحلاته السابقة.
     */
    public function destroy(Request $request, int $captain): JsonResponse
    {
        $this->staff->deleteCaptain($this->ownedCaptain($request->user(), $captain));

        return response()->json(['message' => 'تم حذف الكابتن.']);
    }

    public function updateAvatar(AvatarRequest $request, int $captain): JsonResponse
    {
        return $this->respond($this->staff->storeAvatar($this->ownedCaptain($request->user(), $captain), $request->file('avatar')));
    }

    public function removeAvatar(Request $request, int $captain): JsonResponse
    {
        return $this->respond($this->staff->removeAvatar($this->ownedCaptain($request->user(), $captain)));
    }

    private function respond(Fisher $captain, int $status = 200): JsonResponse
    {
        return (new FisherResource($captain->load(self::WITH)))->response()->setStatusCode($status)->header('ETag', $captain->etag());
    }
}
