<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreServiceFaitRequest;
use App\Http\Requests\Indemnites\UpdateServiceFaitRequest;
use App\Http\Requests\Indemnites\RejeterServiceFaitRequest;
use App\Http\Requests\Indemnites\CorrigerServiceFaitRequest;
use App\Models\ServiceFait;
use App\Models\ServiceFaitHistorique;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicesFaitsController extends Controller
{
    
}
