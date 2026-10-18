<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\DashboardRequest;
use App\Http\Resources\Api\SaleResource;
use App\Http\Resources\Api\TripResource;
use App\Services\Owner\OwnerDashboard;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request, OwnerDashboard $dashboard): JsonResponse
    {
        $data = $dashboard->for($request->user(), $request->period());

        return response()->json(['data' => [
            'period' => $data['period'],
            'kpis' => $data['kpis'],
            'action_items' => $data['action_items'],
            'revenue_by_month' => $data['revenue_by_month'],
            'stock' => $data['stock'],
            'scheduled_trips' => TripResource::collection($data['scheduled_trips']),
            'active_trips' => TripResource::collection($data['active_trips']),
            'recent_sales' => SaleResource::collection($data['recent_sales']),
        ]]);
    }
}
