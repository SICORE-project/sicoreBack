<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Administration\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardService $dashboard)
    {
        // Identity, role and scope come only from the authenticated account.
        return response()->json(['data' => $dashboard->forUser($request->user())])
            ->header('Cache-Control', 'private, no-store');
    }
}
