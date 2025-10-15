<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Dispositif;

class DispositifController extends Controller
{
    public function index()
    {
        return response()->json(['data' => Dispositif::all()], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'volume_initial' => 'required|numeric',
            'debit_courant' => 'required|numeric',
            'batterie' => 'required|integer|min:0|max:100',
            'temps_restant' => 'nullable|integer|min:0',
        ]);

        $dispositif = Dispositif::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Données enregistrées avec succès',
            'data' => $dispositif
        ], 201);
    }

    

//     public function fetchFromESP32()
// {
//     try {
//         $espIp = 'http://192.168.137.235/data';
//         $response = Http::timeout(5)->get($espIp);

//         \Log::info('Réponse brute ESP32:', ['body' => $response->body()]);

//         if (!$response->successful()) {
//             return response()->json(['error' => 'ESP32 inaccessible'], 500);
//         }

//         $data = $response->json();

//         if (!is_array($data)) {
//             return response()->json([
//                 'error' => 'Réponse invalide du ESP32',
//                 'contenu' => $response->body()
//             ], 400);
//         }

//         $dispositif = Dispositif::create([
//             'volume_initial' => $data['volume_initial'] ?? null,
//             'debit_courant' => $data['debit_courant'] ?? null,
//             'batterie' => intval($data['batterie'] ?? 0),
//             'temps_restant' => $data['temps_restant'] ?? null,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Données reçues du ESP32 et enregistrées',
//             'data' => $dispositif
//         ], 201);
//     } catch (\Exception $e) {
//         \Log::error('Erreur fetchFromESP32', ['message' => $e->getMessage()]);
//         return response()->json([
//             'error' => 'Erreur interne : ' . $e->getMessage()
//         ], 500);
//     }
// }


public function fetchFromESP32()
{
    try {
        $espIp = 'http://192.168.137.235/data'; // ✅ Vérifie que cette IP correspond à celle affichée dans ton Serial Monitor
        $response = Http::timeout(5)->get($espIp);

        \Log::info('Réponse brute ESP32:', ['body' => $response->body()]);

        // ✅ Si le ESP32 ne répond pas correctement, on renvoie un warning (pas une erreur)
        if (!$response->successful()) {
            \Log::warning('ESP32 non joignable, tentative ignorée');
            return response()->json(['warning' => 'ESP32 temporairement injoignable'], 200);
        }

        $data = $response->json();

        // ✅ Si le JSON reçu est invalide
        if (!is_array($data)) {
            \Log::warning('Réponse invalide reçue du ESP32', ['body' => $response->body()]);
            return response()->json([
                'warning' => 'Réponse invalide du ESP32',
                'contenu' => $response->body()
            ], 200); // ⚠️ On renvoie 200 aussi pour ne pas déclencher d’erreur dans Flutter
        }

        // ✅ Enregistrement des données dans la base
        $dispositif = Dispositif::create([
            'volume_initial' => $data['volume_initial'] ?? null,
            // 'debit_courant' => $data['debit_courant'] ?? null,
            'debit_courant' => $data['debit_courant'] ?? $data['debit'] ?? null,


            'batterie'      => intval($data['batterie'] ?? 0),
            'temps_restant' => $data['temps_restant'] ?? null,
        ]);

        // ✅ Réponse réussie
        return response()->json([
            'success' => true,
            'message' => 'Données reçues du ESP32 et enregistrées',
            'data' => $dispositif
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Erreur fetchFromESP32', ['message' => $e->getMessage()]);
        return response()->json([
            'error' => 'Erreur interne : ' . $e->getMessage()
        ], 500);
    }
}




public function show($id)
    {
        $dispositif = Dispositif::find($id);
        if (!$dispositif) {
            return response()->json(['message' => 'Dispositif non trouvé'], 404);
        }
        return response()->json(['data' => $dispositif], 200);
    }
}
