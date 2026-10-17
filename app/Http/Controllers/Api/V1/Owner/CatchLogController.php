<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CatchLogEntryResource;
use App\Services\Owner\CatchLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * سجل الصيد: السطور مصفّحة، والملخص بالصنف في ردّ مستقل. كلاهما يُصفّى
 * بـ captain_id و boat_id، والسطور بـ search أيضًا.
 */
class CatchLogController extends Controller
{
    public function index(Request $request, CatchLog $log): AnonymousResourceCollection
    {
        return CatchLogEntryResource::collection(
            $log->entries($request->user(), $request->only(['captain_id', 'boat_id', 'search']), min((int) $request->query('per_page', 25), 100)),
        );
    }

    public function summary(Request $request, CatchLog $log): JsonResponse
    {
        $summary = $log->summary($request->user(), $request->only(['captain_id', 'boat_id']));

        return response()->json(['data' => [
            'totals' => $log->totals($summary),
            'by_species' => $summary->values(),
        ]]);
    }
}
