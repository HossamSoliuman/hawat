<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Concerns\HandlesFormAvatar;
use App\Http\Controllers\Concerns\ResolvesOwnerRecords;
use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CaptainRequest;
use App\Models\Boat;
use App\Models\IdType;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use App\Services\Owner\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * كباتن المالك: حساب دخول للتطبيق فوق سجلّ صياد في الوزارة. بلا كلمة مرور
 * يُدعى الكابتن برمز تفعيل إلى جواله.
 */
class CaptainController extends Controller
{
    use HandlesFormAvatar, ResolvesOwnerRecords;

    public function __construct(private readonly StaffService $staff) {}

    public function index(Request $request): View
    {
        $owner = $request->user();

        $captains = $owner->staff()
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::CAPTAIN))
            ->with(['fisher.boat', 'fisher.port', 'fisher.idType'])
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->query('search').'%')
                ->orWhere('phone', 'like', '%'.$request->query('search').'%')))
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('panel.owner.captains.index', [
            'captains' => $captains,
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
            'ports' => Port::orderBy('name')->get(['id', 'name']),
            'idTypes' => IdType::options(),
        ]);
    }

    public function store(CaptainRequest $request): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $captain = $this->staff->createCaptain($request->user(), $request->validated());
        $this->syncAvatar($request, $captain);

        return redirect()->route('panel.owner.captains')->with('status', $captain->isPendingInvitation()
            ? 'تمت إضافة الكابتن وأُرسلت إلى جواله دعوة برمز تفعيل — يختار كلمة مروره بنفسه.'
            : 'تمت إضافة الكابتن — يدخل التطبيق بجواله وكلمة المرور.');
    }

    public function update(CaptainRequest $request, int $captain): RedirectResponse
    {
        $this->validateFormAvatar($request);

        $model = DB::transaction(function () use ($request, $captain) {
            $model = $this->ownedCaptain($request->user(), $captain);
            $this->lockedCaptainFisher($model)?->assertVersion($request);

            return $this->staff->updateCaptain($model, $request->validated());
        });
        $this->syncAvatar($request, $model);

        return redirect()->route('panel.owner.captains')->with('status', 'تم تحديث بيانات الكابتن.');
    }

    public function toggle(Request $request, int $captain): RedirectResponse
    {
        $model = $this->ownedCaptain($request->user(), $captain);
        $this->staff->setCaptainActive($model, ! $model->active);

        return redirect()->route('panel.owner.captains')->with('status', $model->active ? 'تم تفعيل حساب الكابتن.' : 'تم تعطيل حساب الكابتن.');
    }

    public function invitation(Request $request, int $captain): RedirectResponse
    {
        $this->staff->resendInvitation($this->ownedCaptain($request->user(), $captain));

        return redirect()->route('panel.owner.captains')->with('status', 'أُعيد إرسال الدعوة برمز جديد صالح ثلاثة أيام.');
    }

    private function syncAvatar(Request $request, User $captain): void
    {
        $this->syncFormAvatar(
            $request,
            fn ($file) => $this->staff->storeCaptainAvatar($captain, $file),
            fn () => $this->staff->removeCaptainAvatar($captain),
        );
    }
}
