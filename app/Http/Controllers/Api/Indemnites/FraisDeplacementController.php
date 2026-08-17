<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreFraisDeplacementRequest;
use App\Http\Requests\Indemnites\UpdateFraisDeplacementRequest;
use App\Http\Requests\Indemnites\CalculerFraisDeplacementRequest;
use App\Http\Requests\Indemnites\DeposerJustificatifFraisRequest;
use App\Http\Requests\Indemnites\RejeterFraisDeplacementRequest;
use App\Http\Requests\Indemnites\RembourserFraisDeplacementRequest;
use App\Models\MissionDeplacement;
use App\Models\LigneFraisDeplacement;
use App\Models\BaremeDeplacement;
use App\Models\JustificatifFraisDeplacement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FraisDeplacementController extends Controller
{
    
}
