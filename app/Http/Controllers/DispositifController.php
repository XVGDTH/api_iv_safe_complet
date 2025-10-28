<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Dispositif;

class DispositifController extends Controller
{
    /**
     * Récupère tous les dispositifs
     */
    public function index()
    {
        try {
            $dispositifs = Dispositif::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'data' => $dispositifs
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur index dispositifs', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données'
            ], 500);
        }
    }

    /**
     * Récupère le dernier dispositif enregistré
     */
    public function latest()
    {
        try {
            $dernier = Dispositif::latest('created_at')->first();

            if (!$dernier) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune donnée disponible',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $dernier
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur latest dispositif', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Récupère un dispositif par son ID
     */
    public function show($id)
    {
        try {
            $dispositif = Dispositif::find($id);
            
            if (!$dispositif) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dispositif non trouvé'
                ], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $dispositif
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Erreur show dispositif', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération'
            ], 500);
        }
    }

    /**
     * Enregistre un nouveau dispositif
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'volume_initial' => 'required|numeric',
                'debit_courant' => 'required|numeric',
                'batterie' => 'required|integer|min:0|max:100',
                'temps_restant' => 'nullable|integer|min:0',
            ]);

            $dispositif = Dispositif::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Données enregistrées avec succès',
                'data' => $dispositif
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Erreur store dispositif', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement'
            ], 500);
        }
    }

    /**
     * Récupère les données depuis l'ESP32
     */
    public function fetchFromESP32()
    {
        try {
            $espIp = 'http://192.168.137.235/data';
            $response = Http::timeout(5)->get($espIp);

            \Log::info('Réponse brute ESP32:', ['body' => $response->body()]);

            if (!$response->successful()) {
                \Log::warning('ESP32 non joignable');
                return response()->json([
                    'success' => false,
                    'warning' => 'ESP32 temporairement injoignable'
                ], 200);
            }

            $data = $response->json();

            if (!is_array($data)) {
                \Log::warning('Réponse invalide reçue du ESP32', ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'warning' => 'Réponse invalide du ESP32',
                    'contenu' => $response->body()
                ], 200);
            }

            // Enregistrement des données
            $dispositif = Dispositif::create([
                'volume_initial' => $data['volume_initial'] ?? null,
                'debit_courant' => $data['debit_courant'] ?? $data['debit'] ?? null,
                'batterie' => intval($data['batterie'] ?? 0),
                'temps_restant' => $data['temps_restant'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Données reçues du ESP32 et enregistrées',
                'data' => $dispositif
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Erreur fetchFromESP32', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur interne : ' . $e->getMessage()
            ], 500);
        }
    }
}