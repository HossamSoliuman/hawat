<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Owner\CrewRequest;
use App\Http\Resources\Api\FisherResource;
use App\Models\Fisher;
use App\Services\Owner\StaffService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CrewController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['boat', 'port', 'idType', 'fisherRole'];

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $crew = Fisher::forOwner($request->user())->crew()->with(self::WITH)
            ->when($request->filled('boat_id'), fn ($q) => $q->where('boat_id', $request->query('boat_id')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return FisherResource::collection($crew);
    }

    public function store(CrewRequest $request): JsonResponse
    {
        return $this->respond($this->staff->createCrew($request->user(), $request->validated()), 201);
    }

    public function show(Request $request, int $crew): JsonResponse
    {
        return $this->respond($this->ownedCrew($request->user(), $crew));
    }

    /**
     * If-Match (أو حقل version) بنسخة قديمة → 409 ولا يُكتب شيء.
     */
    public function update(CrewRequest $request, int $crew): JsonResponse
    {
        $fisher = DB::transaction(function () use ($request, $crew) {
            $fisher = $this->lockedCrew($request->user(), $crew);
            $fisher->assertVersion($request);

            return $this->staff->updateCrew($fisher, $request->validated());
        });

        return $this->respond($fisher);
    }

    public function destroy(Request $request, int $crew): JsonResponse
    {
        $this->ownedCrew($request->user(), $crew)->delete();

        return response()->json(['message' => 'تم حذف عضو الطاقم.']);
    }

    public function updateAvatar(AvatarRequest $request, int $crew): JsonResponse
    {
        return $this->respond($this->staff->storeCrewAvatar($this->ownedCrew($request->user(), $crew), $request->file('avatar')));
    }

    public function removeAvatar(Request $request, int $crew): JsonResponse
    {
        return $this->respond($this->staff->removeCrewAvatar($this->ownedCrew($request->user(), $crew)));
    }

    private function respond(Fisher $fisher, int $status = 200): JsonResponse
    {
        return (new FisherResource($fisher->load(self::WITH)))->response()->setStatusCode($status)->header('ETag', $fisher->etag());
    }
}
