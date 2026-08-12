<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Models\Parametrage\Ia;
use Illuminate\Http\Request;

class IaController extends Controller
{
    public function index()
    {
        try {
            $ias = Ia::where('deleted_at', null)
                ->orderBy('libelle')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $ias,
                'count' => $ias->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $ia = Ia::where('id', $id)->where('deleted_at', null)->first();
            
            if (!$ia) {
                return response()->json([
                    'success' => false,
                    'message' => 'IA non trouvée'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $ia
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:ias,code',
                'libelle' => 'required|string|max:200',
                'region' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'adresse' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'responsable' => 'nullable|string|max:100',
                'est_actif' => 'nullable|boolean',
            ]);

            $validated['est_actif'] = $request->has('est_actif');

            $ia = Ia::create($validated);

            return response()->json([
                'success' => true,
                'data' => $ia,
                'message' => 'IA créée avec succès.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $ia = Ia::where('id', $id)->where('deleted_at', null)->first();
            
            if (!$ia) {
                return response()->json([
                    'success' => false,
                    'message' => 'IA non trouvée'
                ], 404);
            }

            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:ias,code,' . $id,
                'libelle' => 'required|string|max:200',
                'region' => 'nullable|string|max:100',
                'departement' => 'nullable|string|max:100',
                'adresse' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
                'responsable' => 'nullable|string|max:100',
                'est_actif' => 'nullable|boolean',
            ]);

            $validated['est_actif'] = $request->has('est_actif');

            $ia->update($validated);

            return response()->json([
                'success' => true,
                'data' => $ia,
                'message' => 'IA mise à jour avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ia = Ia::where('id', $id)->where('deleted_at', null)->first();
            
            if (!$ia) {
                return response()->json([
                    'success' => false,
                    'message' => 'IA non trouvée'
                ], 404);
            }
            
            $ia->delete();

            return response()->json([
                'success' => true,
                'message' => 'IA supprimée avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }
}