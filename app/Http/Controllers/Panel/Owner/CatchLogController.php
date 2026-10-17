<?php

namespace App\Http\Controllers\Panel\Owner;

use App\Http\Controllers\Controller;
use App\Models\Boat;
use App\Models\Fisher;
use App\Services\Owner\CatchLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * سجل الصيد: ما أعلنه المالك من مصيد على رحلاته وما عُدّ منه، بالكابتن أو بالقارب.
 */
class CatchLogController extends Controller
{
    public function index(Request $request, CatchLog $log): View
    {
        $owner = $request->user();
        $filters = ['captain_id' => $request->query('captain'), 'boat_id' => $request->query('boat'), 'search' => $request->query('search')];
        $summary = $log->summary($owner, $filters);

        return view('panel.owner.catch-log', [
            'entries' => $log->entries($owner, $filters),
            'summary' => $summary,
            'totals' => $log->totals($summary),
            'captains' => Fisher::forOwner($owner)->captains()->orderBy('name')->get(['id', 'name']),
            'boats' => Boat::forOwner($owner)->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
