<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pressure;
use Illuminate\Support\Facades\Http;


class PressureController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'serial'      => 'required|string|max:255',
            'temperature' => 'required|numeric',
            'bpm'         => 'required|integer',
            'spo2'        => 'required|integer',
            'batterie'    => 'required|integer|min:0|max:100',
        ]);

        $pressure = Pressure::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Mesure enregistrée avec succès',
            'data'    => $pressure,
        ], 201);
    }

    public function latest()
    {
        $last = Pressure::latest()->first();

        return response()->json([
            'success' => true,
            'data'    => $last,
        ]);
    }

    public function all()
    {
        $all = Pressure::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data'    => $all,
        ]);
    }
}
