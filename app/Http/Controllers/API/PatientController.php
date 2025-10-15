<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PatientController extends Controller
{
    /**
     * Liste tous les patients
     */
    public function index()
    {
        try {
            $patients = Patient::with(['derniereMesure', 'commandesEnAttente'])
                              ->orderBy('created_at', 'desc')
                              ->get()
                              ->map(function ($patient) {
                                  return [
                                      'id' => $patient->id,
                                      'nom_complet' => $patient->nom . ' ' . $patient->prenom,
                                      'numero_patient' => $patient->numero_patient,
                                      'esp32_id' => $patient->esp32_id,
                                      'perfusion_active' => $patient->perfusion_active,
                                      'pourcentage_perfusion' => $patient->pourcentage_perfusion,
                                      'temps_restant_estime' => $patient->temps_restant_estime,
                                      'derniere_mesure' => $patient->derniereMesure,
                                      'commandes_en_attente' => $patient->commandesEnAttente->count(),
                                      'statut_global' => $patient->derniereMesure?->statut ?? 'inconnu'
                                  ];
                              });

            return response()->json([
                'success' => true,
                'data' => $patients
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des patients',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer un nouveau patient
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nom' => 'required|string|max:255',
                'prenom' => 'required|string|max:255',
                'numero_patient' => 'required|string|unique:patients',
                'age' => 'nullable|integer|min:0|max:120',
                'sexe' => 'nullable|in:M,F',
                'diagnostic' => 'nullable|string',
                'esp32_id' => 'required|string|unique:patients',
                'volume_total_ml' => 'required|numeric|min:0',
                'debit_ml_h' => 'required|numeric|min:0.1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient = Patient::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Patient créé avec succès',
                'data' => $patient
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du patient',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher un patient spécifique
     */
    public function show($id)
    {
        try {
            $patient = Patient::with(['mesures' => function($query) {
                $query->orderBy('timestamp_mesure', 'desc')->limit(50);
            }, 'commandes' => function($query) {
                $query->orderBy('created_at', 'desc')->limit(20);
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'patient' => $patient,
                    'pourcentage_perfusion' => $patient->pourcentage_perfusion,
                    'temps_restant_estime' => $patient->temps_restant_estime,
                    'mesures_recentes' => $patient->mesures,
                    'commandes_recentes' => $patient->commandes
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Patient non trouvé',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour un patient
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = Patient::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|required|string|max:255',
                'prenom' => 'sometimes|required|string|max:255',
                'numero_patient' => 'sometimes|required|string|unique:patients,numero_patient,' . $id,
                'age' => 'nullable|integer|min:0|max:120',
                'sexe' => 'nullable|in:M,F',
                'diagnostic' => 'nullable|string',
                'esp32_id' => 'sometimes|required|string|unique:patients,esp32_id,' . $id,
                'volume_total_ml' => 'sometimes|required|numeric|min:0',
                'debit_ml_h' => 'sometimes|required|numeric|min:0.1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Patient mis à jour avec succès',
                'data' => $patient
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
     * Supprimer un patient
     */
    public function destroy($id)
    {
        try {
            $patient = Patient::findOrFail($id);
            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
