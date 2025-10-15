<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Mesure;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MesureController extends Controller
{
    /**
     * Liste des mesures d'un patient
     */
    public function index($patient_id)
    {
        try {
            $patient = Patient::findOrFail($patient_id);

            $mesures = Mesure::where('patient_id', $patient_id)
                            ->orderBy('timestamp_mesure', 'desc')
                            ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $mesures,
                'patient' => [
                    'id' => $patient->id,
                    'nom_complet' => $patient->nom . ' ' . $patient->prenom,
                    'esp32_id' => $patient->esp32_id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des mesures',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle mesure (ESP32)
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'esp32_id' => 'required|string',
                'temperature' => 'nullable|numeric|between:-50,100',
                'pression' => 'nullable|numeric|min:0',
                'bulle_detectee' => 'nullable|boolean',
                'debit_actuel' => 'nullable|numeric|min:0',
                'volume_perfuse' => 'nullable|numeric|min:0',
                'batterie_pourcent' => 'nullable|integer|between:0,100',
                'pompe_active' => 'nullable|boolean',
                'vanne_ouverte' => 'nullable|boolean',
                'message_alerte' => 'nullable|string|max:500',
                'timestamp_mesure' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Trouver le patient par ESP32 ID
            $patient = Patient::where('esp32_id', $request->esp32_id)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'ESP32 non enregistré dans le système'
                ], 404);
            }

            // Déterminer le statut automatiquement
            $statut = 'normal';
            if ($request->bulle_detectee) {
                $statut = 'critique';
            } elseif ($request->batterie_pourcent && $request->batterie_pourcent < 20) {
                $statut = 'critique';
            } elseif ($request->temperature && ($request->temperature < 15 || $request->temperature > 40)) {
                $statut = 'critique';
            } elseif ($request->batterie_pourcent && $request->batterie_pourcent < 30) {
                $statut = 'alerte';
            } elseif ($request->temperature && ($request->temperature < 18 || $request->temperature > 35)) {
                $statut = 'alerte';
            }

            $mesure = Mesure::create([
                'patient_id' => $patient->id,
                'esp32_id' => $request->esp32_id,
                'temperature' => $request->temperature,
                'pression' => $request->pression,
                'bulle_detectee' => $request->bulle_detectee ?? false,
                'debit_actuel' => $request->debit_actuel ?? 0,
                'volume_perfuse' => $request->volume_perfuse ?? 0,
                'batterie_pourcent' => $request->batterie_pourcent,
                'pompe_active' => $request->pompe_active ?? false,
                'vanne_ouverte' => $request->vanne_ouverte ?? false,
                'statut' => $statut,
                'message_alerte' => $request->message_alerte,
                'timestamp_mesure' => $request->timestamp_mesure ?? now()
            ]);

            // Mettre à jour le patient avec les dernières données
            if ($request->has('volume_perfuse')) {
                $patient->update([
                    'volume_perfuse_ml' => $request->volume_perfuse
                ]);
            }

            // Récupérer les commandes en attente pour cet ESP32
            $commandesEnAttente = \App\Models\Commande::where('esp32_id', $request->esp32_id)
                                                     ->whereIn('statut', ['pending', 'sent'])
                                                     ->pluck('id')
                                                     ->toArray();

            return response()->json([
                'success' => true,
                'message' => 'Mesure enregistrée avec succès',
                'data' => $mesure,
                'commandes_en_attente' => $commandesEnAttente,
                'statut_detected' => $statut
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la mesure',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une mesure manuelle pour un patient (via app mobile)
     */
    public function storeForPatient(Request $request, $patient_id)
    {
        try {
            $patient = Patient::findOrFail($patient_id);

            $validator = Validator::make($request->all(), [
                'temperature' => 'nullable|numeric|between:-50,100',
                'pression' => 'nullable|numeric|min:0',
                'bulle_detectee' => 'nullable|boolean',
                'debit_actuel' => 'nullable|numeric|min:0',
                'volume_perfuse' => 'nullable|numeric|min:0',
                'batterie_pourcent' => 'nullable|integer|between:0,100',
                'pompe_active' => 'nullable|boolean',
                'vanne_ouverte' => 'nullable|boolean',
                'message_alerte' => 'nullable|string|max:500',
                'timestamp_mesure' => 'nullable|date'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Déterminer un statut simple à partir des valeurs saisies
            $statut = 'normal';
            if ($request->bulle_detectee) {
                $statut = 'critique';
            } elseif ($request->batterie_pourcent && $request->batterie_pourcent < 20) {
                $statut = 'critique';
            } elseif ($request->temperature && ($request->temperature < 15 || $request->temperature > 40)) {
                $statut = 'critique';
            } elseif ($request->batterie_pourcent && $request->batterie_pourcent < 30) {
                $statut = 'alerte';
            } elseif ($request->temperature && ($request->temperature < 18 || $request->temperature > 35)) {
                $statut = 'alerte';
            }

            $mesure = Mesure::create([
                'patient_id' => $patient->id,
                'esp32_id' => $patient->esp32_id,
                'temperature' => $request->temperature,
                'pression' => $request->pression,
                'bulle_detectee' => $request->bulle_detectee ?? false,
                'debit_actuel' => $request->debit_actuel ?? 0,
                'volume_perfuse' => $request->volume_perfuse ?? 0,
                'batterie_pourcent' => $request->batterie_pourcent,
                'pompe_active' => $request->pompe_active ?? false,
                'vanne_ouverte' => $request->vanne_ouverte ?? false,
                'statut' => $statut,
                'message_alerte' => $request->message_alerte,
                'timestamp_mesure' => $request->timestamp_mesure ?? now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mesure enregistrée',
                'data' => $mesure,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la mesure',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Afficher une mesure spécifique
     */
    public function show($id)
    {
        try {
            $mesure = Mesure::with('patient')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $mesure
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesure non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une mesure
     */
    public function update(Request $request, $id)
    {
        try {
            $mesure = Mesure::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'temperature' => 'nullable|numeric|between:-50,100',
                'pression' => 'nullable|numeric|min:0',
                'bulle_detectee' => 'nullable|boolean',
                'debit_actuel' => 'nullable|numeric|min:0',
                'volume_perfuse' => 'nullable|numeric|min:0',
                'batterie_pourcent' => 'nullable|integer|between:0,100',
                'pompe_active' => 'nullable|boolean',
                'vanne_ouverte' => 'nullable|boolean',
                'statut' => 'nullable|in:normal,alerte,critique',
                'message_alerte' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $mesure->update($request->only([
                'temperature', 'pression', 'bulle_detectee', 'debit_actuel',
                'volume_perfuse', 'batterie_pourcent', 'pompe_active',
                'vanne_ouverte', 'statut', 'message_alerte'
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Mesure mise à jour avec succès',
                'data' => $mesure->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Supprimer une mesure
     */
    public function destroy($id)
    {
        try {
            $mesure = Mesure::findOrFail($id);
            $mesure->delete();

            return response()->json([
                'success' => true,
                'message' => 'Mesure supprimée avec succès'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Statistiques des mesures d'un patient
     */
    public function statistiques($patient_id)
    {
        try {
            $patient = Patient::findOrFail($patient_id);

            $mesures24h = Mesure::where('patient_id', $patient_id)
                               ->where('timestamp_mesure', '>=', Carbon::now()->subHours(24))
                               ->get();

            $stats = [
                'nombre_mesures_24h' => $mesures24h->count(),
                'debit_moyen' => $mesures24h->avg('debit_actuel'),
                'debit_min' => $mesures24h->min('debit_actuel'),
                'debit_max' => $mesures24h->max('debit_actuel'),
                'temperature_moyenne' => $mesures24h->avg('temperature'),
                'temperature_min' => $mesures24h->min('temperature'),
                'temperature_max' => $mesures24h->max('temperature'),
                'volume_total_perfuse' => $mesures24h->max('volume_perfuse'),
                'alertes_count' => $mesures24h->where('statut', 'alerte')->count(),
                'critiques_count' => $mesures24h->where('statut', 'critique')->count(),
                'bulles_detectees' => $mesures24h->where('bulle_detectee', true)->count(),
                'derniere_mesure' => $patient->mesures()->latest('timestamp_mesure')->first()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats,
                'periode' => '24 dernières heures'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du calcul des statistiques',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
