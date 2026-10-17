<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\SaleResource;
use App\Http\Resources\Api\TripResource;
use App\Services\Owner\OwnerDashboard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, OwnerDashboard $dashboard): JsonResponse
    {
        $data = $dashboard->for($request->user());

        return response()->json(['data' => [
            'kpis' => $data['kpis'],
            'revenue_by_month' => $data['revenue_by_month'],
            'stock' => $data['stock'],
            'scheduled_trips' => TripResource::collection($data['scheduled_trips']),
            'active_trips' => TripResource::collection($data['active_trips']),
            'recent_sales' => SaleResource::collection($data['recent_sales']),
        ]]);
    }
}
