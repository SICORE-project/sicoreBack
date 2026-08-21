<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreAideEtudianteRequest;
use App\Http\Requests\Indemnites\UpdateAideEtudianteRequest;
use App\Http\Requests\Indemnites\RejeterAideEtudianteRequest;
use App\Http\Requests\Indemnites\DeposerPieceAideRequest;
use App\Models\DemandeAide;
use App\Models\TypeAide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AidesEtudiantesController extends Controller
{
    
}
