<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'prenom' => $data['prenom'],
            'nom' => $data['nom'],
            'email' => $data['email'],
            'login' => $data['login'],
            'telephone' => $data['telephone'] ?? null,
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
            'lieu_service_id' => $data['structure_id'] ?? null,
            'ia_id' => $data['ia_id'] ?? null,
            'ief_id' => $data['ief_id'] ?? null,
            'statut' => $data['statut'],
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès.',
            'data' => $user->load(['role', 'ia', 'ief']),
        ], 201);
    }

    public function creationOptions(Request $request): JsonResponse
    {
        $this->ensureAdministrator($request);

        return response()->json([
            'roles' => DB::table('roles')->orderBy('libelle')->get(['id', 'libelle']),
            'structures' => DB::table('lieu_de_services')->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'ias' => DB::table('ias')->orderBy('libelle')->get(['id', 'code', 'libelle']),
            'iefs' => DB::table('iefs')->orderBy('libelle')->get(['id', 'ia_id', 'code', 'libelle']),
        ]);
    }

    private function ensureAdministrator(Request $request): void
    {
        abort_unless(
            in_array(mb_strtolower((string) $request->user()->role?->libelle), ['administrateur', 'super administrateur'], true),
            403,
            'Accès réservé aux administrateurs.'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        //
    }
}
