<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreAccuseReceptionRequest;
use App\Http\Requests\Indemnites\UpdateAccuseReceptionRequest;
use App\Http\Requests\Indemnites\SignerAccuseReceptionRequest;
use App\Models\AccuseReception;
use App\Models\ModeleAccuseReception;
use App\Models\PolitiqueArchivageAccuse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccuseReceptionController extends Controller
{
}
