<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StorePieceJustificativeRequest;
use App\Http\Requests\Indemnites\UpdatePieceJustificativeRequest;
use App\Http\Requests\Indemnites\DeposerPieceJustificativeRequest;
use App\Http\Requests\Indemnites\ClassifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\VerifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\RejeterPieceJustificativeRequest;
use App\Models\piece_justificatives;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PieceJustificativesController extends Controller
{
    
}
