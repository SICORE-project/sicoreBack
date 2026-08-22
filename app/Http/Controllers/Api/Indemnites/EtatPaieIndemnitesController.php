<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\UpdateEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\GenererEtatPaieIndemniteRequest;
use App\Models\etat_paie_indemnites;
use App\Models\indemnites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EtatPaieIndemnitesController extends Controller
{
    
}