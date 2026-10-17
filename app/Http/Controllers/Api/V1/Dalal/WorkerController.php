<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\AvatarRequest;
use App\Http\Requests\Dalal\WorkerRequest;
use App\Http\Resources\Api\DalalWorkerResource;
use App\Models\DalalWorker;
use App\Services\Dalal\DalalProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * عمال دكة الدلال أفرادًا: قائمة بصفحات وبحث، وإضافة وتعديل، والتعطيل
 * بالحالة (لا حذف)، والصورة الشخصية. عامل دلال آخر 404.
 */
class WorkerController extends Controller
{
    use ResolvesDalalRecords;

    private const WITH = ['type', 'idType'];

    public function __construct(private readonly DalalProfileService $profiles) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $search = $request->query('search');

        $workers = DalalWorker::forDalal($request->user())->with(self::WITH)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
            ->when($request->filled('dalal_worker_type_id'), fn ($q) => $q->where('dalal_worker_type_id', $request->query('dalal_worker_type_id')))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('id_number', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(min((int) $request->query('per_page', 25), 100));

        return DalalWorkerResource::collection($workers);
    }

    public function store(WorkerRequest $request): JsonResponse
    {
        $worker = $this->profiles->createWorker($request->user(), $request->validated());

        return (new DalalWorkerResource($worker->load(self::WITH)))->response()->setStatusCode(201);
    }

    public function show(Request $request, int $worker): DalalWorkerResource
    {
        return new DalalWorkerResource($this->dalalWorker($request->user(), $worker)->load(self::WITH));
    }

    public function update(WorkerRequest $request, int $worker): DalalWorkerResource
    {
        $model = $this->profiles->updateWorker($this->dalalWorker($request->user(), $worker), $request->validated());

        return new DalalWorkerResource($model->load(self::WITH));
    }

    public function updateAvatar(AvatarRequest $request, int $worker): DalalWorkerResource
    {
        $model = $this->profiles->storeWorkerAvatar($this->dalalWorker($request->user(), $worker), $request->file('avatar'));

        return new DalalWorkerResource($model->load(self::WITH));
    }

    public function removeAvatar(Request $request, int $worker): DalalWorkerResource
    {
        $model = $this->profiles->removeWorkerAvatar($this->dalalWorker($request->user(), $worker));

        return new DalalWorkerResource($model->load(self::WITH));
    }
}
