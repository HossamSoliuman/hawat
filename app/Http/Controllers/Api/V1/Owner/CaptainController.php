<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Owner\CaptainRequest;
use App\Http\Resources\Api\CaptainResource;
use App\Models\Role;
use App\Models\User;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CaptainController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['fisher.boat', 'fisher.port', 'fisher.idType', 'fisher.fisherRole'];

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $captains = $request->user()->staff()
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::CAPTAIN))
            ->with(self::WITH)
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return CaptainResource::collection($captains);
    }

    /**
     * بلا password يُدعى الكابتن برمز تفعيل إلى جواله (حالته invited).
     */
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
            $model = $this->ownedCaptain($request->user(), $captain);
            $this->lockedCaptainFisher($model)?->assertVersion($request);

            return $this->staff->updateCaptain($model, $request->validated());
        });

        return $this->respond($model);
    }

    public function invitation(Request $request, int $captain): JsonResponse
    {
        return $this->respond($this->staff->resendInvitation($this->ownedCaptain($request->user(), $captain)));
    }

    public function updateAvatar(AvatarRequest $request, int $captain): JsonResponse
    {
        return $this->respond($this->staff->storeCaptainAvatar($this->ownedCaptain($request->user(), $captain), $request->file('avatar')));
    }

    public function removeAvatar(Request $request, int $captain): JsonResponse
    {
        return $this->respond($this->staff->removeCaptainAvatar($this->ownedCaptain($request->user(), $captain)));
    }

    private function respond(User $captain, int $status = 200): JsonResponse
    {
        $captain->load(self::WITH);
        $response = (new CaptainResource($captain))->response()->setStatusCode($status);

        return $captain->fisher ? $response->header('ETag', $captain->fisher->etag()) : $response;
    }
}
