<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pressure;

class PressureController extends Controller
{
    // ✅ Stocker une nouvelle mesure
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial' => 'required|string|max:255',
            'temperature' => 'required|numeric',
            'bpm' => 'required|integer',
            'spo2' => 'required|integer',
            'batterie' => 'required|integer|min:0|max:100',
        ]);

        $pressure = Pressure::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mesure enregistrée avec succès',
            'data' => $pressure
        ], 201);
    }

    // ✅ Récupérer toutes les mesures
    public function all()
    {
        $pressures = Pressure::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $pressures
        ]);
    }

    // ✅ Récupérer la dernière mesure (celle que ton ESP32 envoie toutes les 10s)
    public function latest()
    {
        $latest = Pressure::orderBy('created_at', 'desc')->first();

        return response()->json([
            'success' => true,
            'data' => $latest
        ]);
    }
}
