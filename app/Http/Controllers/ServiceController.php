<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::with('structure');

        if ($request->has('structure_id')) {
            $query->where('structure_id', $request->structure_id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'structure_id' => 'required|exists:structures,id',
        ]);

        $service = Service::create($validated);

        return response()->json($service, 201);
    }

    public function show(string $id)
    {
        return response()->json(Service::with('structure')->findOrFail($id));
    }

    public function update(Request $request, string $id)
    {
        $service = Service::findOrFail($id);
        $service->update($request->all());

        return response()->json($service);
    }

    public function destroy(string $id)
    {
        Service::findOrFail($id)->delete();

        return response()->json(['message' => 'Service supprimé.']);
    }
}
