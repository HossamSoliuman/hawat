<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\BoatRequest;
use App\Http\Resources\Api\BoatResource;
use App\Models\Boat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BoatController extends Controller
{
    use ResolvesOwnerRecords;

    private const WITH = ['port', 'category', 'type', 'captainFisher'];

    public function index(Request $request): AnonymousResourceCollection
    {
        $boats = Boat::forOwner($request->user())->with(self::WITH)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('boat_number', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return BoatResource::collection($boats);
    }

    public function store(BoatRequest $request): JsonResponse
    {
        $boat = Boat::create($request->validated() + ['status' => 'نشط', 'owner_id' => $request->user()->id]);

        return $this->respond($boat, 201);
    }

    public function show(Request $request, int $boat): JsonResponse
    {
        return $this->respond($this->ownedBoat($request->user(), $boat));
    }

    /**
     * If-Match (أو حقل version) بنسخة قديمة → 409 ولا يُكتب شيء.
     */
    public function update(BoatRequest $request, int $boat): JsonResponse
    {
        $model = DB::transaction(function () use ($request, $boat) {
            $model = $this->lockedBoat($request->user(), $boat);
            $model->assertVersion($request);
            $model->update($request->validated());

            return $model;
        });

        return $this->respond($model);
    }

    public function destroy(Request $request, int $boat): JsonResponse
    {
        $model = $this->ownedBoat($request->user(), $boat);

        if ($model->trips()->exists()) {
            return response()->json(['message' => 'لا يُحذف قارب له رحلات — عطّله بدل حذفه.'], 422);
        }

        $model->delete();

        return response()->json(['message' => 'تم حذف القارب.']);
    }

    private function respond(Boat $boat, int $status = 200): JsonResponse
    {
        // الإسناد يحدّث سجلّ الكابتن وقد يعيد حساب عدد الطاقم — الردّ أحدث ما كُتب.
        $boat->refresh()->load(self::WITH);

        return (new BoatResource($boat))->response()->setStatusCode($status)->header('ETag', $boat->etag());
    }
}
