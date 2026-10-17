<?php

namespace App\Http\Controllers\Panel\Dalal;

use App\Http\Controllers\Concerns\HandlesFormAvatar;
use App\Http\Controllers\Concerns\ResolvesDalalRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\LogoRequest;
use App\Http\Requests\Dalal\SettingsRequest;
use App\Http\Requests\Dalal\WorkerRequest;
use App\Models\DalalProfile;
use App\Models\DalalWorker;
use App\Models\DalalWorkerCount;
use App\Models\DalalWorkerType;
use App\Models\Governorate;
use App\Models\IdType;
use App\Models\Port;
use App\Models\Region;
use App\Services\Dalal\DalalProfileService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إعدادات الدلال: الملف التجاري والدكة، والشركة وشعارها، وعمالة الدكة.
 * كلمة المرور والاسم والصورة في الملف الشخصي المشترك (panel.profile).
 */
class SettingsController extends Controller
{
    use HandlesFormAvatar, ResolvesDalalRecords;

    public function __construct(private readonly DalalProfileService $profiles) {}

    public function index(Request $request): View
    {
        $dalal = $request->user();
        $search = $request->query('search');

        return view('panel.dalal.settings.index', [
            'user' => $dalal,
            'profile' => DalalProfile::forUser($dalal),
            'workers' => DalalWorker::forDalal($dalal)->with(['type', 'idType'])
                ->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))
                ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('id_number', 'like', "%{$search}%")))
                ->orderBy('name')
                ->paginate(25)->withQueryString(),
            'workforce' => $this->profiles->workforce($dalal),
            'legacyCounts' => DalalWorkerCount::forDalal($dalal)->with('type')->orderBy('id')->get(),
            'workerTypes' => DalalWorkerType::options(),
            'idTypes' => IdType::options(),
            'regions' => Region::orderBy('name')->get(['id', 'name']),
            'governorates' => Governorate::orderBy('name')->get(['id', 'name', 'region_id']),
            'ports' => Port::orderBy('name')->get(['id', 'name', 'governorate_id']),
            'tab' => $request->query('tab', 'profile'),
        ]);
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $this->profiles->update($request->user(), $request->validated());

        return back()->with('status', 'حُفظت الإعدادات.');
    }

    public function logo(LogoRequest $request): RedirectResponse
    {
        $this->profiles->storeLogo($request->user(), $request->file('logo'));

        return redirect()->route('panel.dalal.settings', ['tab' => 'company'])->with('status', 'حُفظ الشعار.');
    }

    public function removeLogo(Request $request): RedirectResponse
    {
        $this->profiles->removeLogo($request->user());

        return redirect()->route('panel.dalal.settings', ['tab' => 'company'])->with('status', 'حُذف الشعار.');
    }

    public function storeWorker(WorkerRequest $request): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $worker = $this->profiles->createWorker($request->user(), $request->validated());
        $this->syncWorkerAvatar($request, $worker);

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', 'أُضيف العامل.');
    }

    public function updateWorker(WorkerRequest $request, int $worker): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $model = $this->profiles->updateWorker($this->dalalWorker($request->user(), $worker), $request->validated());
        $this->syncWorkerAvatar($request, $model);

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', 'حُدّثت بيانات العامل.');
    }

    public function toggleWorker(Request $request, int $worker): RedirectResponse
    {
        $model = $this->dalalWorker($request->user(), $worker);
        $this->profiles->setWorkerStatus($model, $model->status === 'نشط' ? 'غير نشط' : 'نشط');

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', $model->status === 'نشط' ? 'فُعّل العامل.' : 'عُطّل العامل.');
    }

    public function destroyWorkerCount(Request $request, int $count): RedirectResponse
    {
        $this->dalalWorkerCount($request->user(), $count)->delete();

        return redirect()->route('panel.dalal.settings', ['tab' => 'workers'])->with('status', 'حُذف سطر الأعداد السابق.');
    }

    private function syncWorkerAvatar(Request $request, DalalWorker $worker): void
    {
        $this->syncFormAvatar(
            $request,
            fn ($file) => $this->profiles->storeWorkerAvatar($worker, $file),
            fn () => $this->profiles->removeWorkerAvatar($worker),
        );
    }
}
