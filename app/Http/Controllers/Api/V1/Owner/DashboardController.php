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
            'fleet' => collect($data['fleet'])->map(fn (array $row) => [
                'id' => $row['boat']->id,
                'name' => $row['boat']->name,
                'boat_number' => $row['boat']->boat_number,
                'status' => $row['boat']->status,
                'trip' => $row['trip'] ? TripResource::make($row['trip']) : null,
            ]),
            'trips_to_sell' => TripResource::collection($data['trips_to_sell']),
            'unpaid_sales' => SaleResource::collection($data['unpaid_sales']),
        ]]);
    }
}
