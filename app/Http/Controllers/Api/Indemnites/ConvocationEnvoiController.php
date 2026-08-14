<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\EnvoyerConvocationRequest;
use App\Http\Requests\Indemnites\RelancerConvocationRequest;
use App\Mail\Indemnites\ConvocationMail;
use App\Models\Indemnite\Convocations as ConvocationModel;
use App\Models\Indemnite\ConvocationEnvoi;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ConvocationEnvoiController extends Controller
{
   
}
