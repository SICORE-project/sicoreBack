<?php

namespace App\Http\Controllers;

use App\Models\Structure;
use Illuminate\Http\Request;

class StructureController extends Controller
{
    public function index()
    {
        return response()->json(Structure::with('services')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $structure = Structure::create($validated);

        return response()->json($structure, 201);
    }

    public function show(string $id)
    {
        return response()->json(Structure::with('services')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $structure = Structure::findOrFail($id);
        $structure->update($request->all());

        return response()->json($structure);
    }

    public function destroy(string $id)
    {
        Structure::findOrFail($id)->delete();

        return response()->json(['message' => 'Structure supprimée.']);
    }
}
