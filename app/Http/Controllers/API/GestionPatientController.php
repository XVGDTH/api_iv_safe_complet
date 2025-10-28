<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\GestionPatient;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;  // ✅ AJOUTEZ CETTE LIGNE

class GestionPatientController extends Controller
{
    /**
     * ✅ Récupérer tous les patients avec cast de types
     */
    public function index()
    {
        try {
            $patients = DB::table('gestion_patients')
                ->select(
                    'id',
                    'nom',
                    'prenom',
                    'age',
                    DB::raw('CAST(poids AS DECIMAL(10,2)) as poids'),
                    'telephone',
                    'serial',
                    DB::raw('CAST(temperature AS DECIMAL(10,2)) as temperature'),
                    DB::raw('CAST(bpm AS UNSIGNED) as bpm'),
                    DB::raw('CAST(spo2 AS UNSIGNED) as spo2'),
                    DB::raw('CAST(batterie AS UNSIGNED) as batterie'),
                    'created_at',
                    'updated_at'
                )
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($patient) {
                    return [
                        'id' => (int)$patient->id,
                        'nom' => $patient->nom,
                        'prenom' => $patient->prenom,
                        'age' => (int)$patient->age,
                        'poids' => (float)$patient->poids,
                        'telephone' => $patient->telephone,
                        'serial' => $patient->serial,
                        'temperature' => (float)$patient->temperature,
                        'bpm' => (int)$patient->bpm,
                        'spo2' => (int)$patient->spo2,
                        'batterie' => (int)$patient->batterie,
                        'created_at' => $patient->created_at,
                        'updated_at' => $patient->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $patients
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Ajouter un nouveau patient avec données médicales
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'age' => 'required|integer|min:0|max:120',
            'poids' => 'required|numeric|min:1|max:500',
            'telephone' => 'required|string|max:20',
            'serial' => 'nullable|string|max:255',
            'temperature' => 'nullable|numeric|min:0|max:50',
            'bpm' => 'nullable|integer|min:0|max:300',
            'spo2' => 'nullable|integer|min:0|max:100',
            'batterie' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $patient = GestionPatient::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'age' => $request->age,
                'poids' => $request->poids,
                'telephone' => $request->telephone,
                'serial' => $request->serial,
                'temperature' => $request->temperature ?? 0,
                'bpm' => $request->bpm ?? 0,
                'spo2' => $request->spo2 ?? 0,
                'batterie' => $request->batterie ?? 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Patient enregistré avec succès',
                'patient' => $patient
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Afficher un patient
     */
    public function show($id)
    {
        try {
            $patient = GestionPatient::find($id);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient introuvable'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $patient
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Mettre à jour un patient
     */
    public function update(Request $request, $id)
    {
        try {
            $patient = GestionPatient::find($id);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient introuvable'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nom' => 'sometimes|string|max:255',
                'prenom' => 'sometimes|string|max:255',
                'age' => 'sometimes|integer|min:0|max:120',
                'poids' => 'sometimes|numeric|min:1|max:500',
                'telephone' => 'sometimes|string|max:20',
                'serial' => 'sometimes|nullable|string|max:255',
                'temperature' => 'sometimes|nullable|numeric|min:0|max:50',
                'bpm' => 'sometimes|nullable|integer|min:0|max:300',
                'spo2' => 'sometimes|nullable|integer|min:0|max:100',
                'batterie' => 'sometimes|nullable|integer|min:0|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $patient->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Patient mis à jour',
                'data' => $patient
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Supprimer un patient
     */
    public function destroy($id)
    {
        try {
            $patient = GestionPatient::find($id);
            
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Patient introuvable'
                ], 404);
            }

            $patient->delete();

            return response()->json([
                'success' => true,
                'message' => 'Patient supprimé avec succès'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}