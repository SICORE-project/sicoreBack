<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Administration\Personnel\DrhDashboard;
use Illuminate\Http\Request;

class DrhDashboardController extends Controller
{
    public function __invoke(Request $request, DrhDashboard $dashboard)
    {
        return response()->json(['data' => $dashboard->data($request->user())]);
    }
}
