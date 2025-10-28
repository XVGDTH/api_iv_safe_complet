<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Connexion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ConnexionController extends Controller
{
    /**
     * ✅ SOLUTION 1 : Détection basée sur TIMESTAMP
     * Détecte une nouvelle connexion quand :
     * 1. Des données récentes (< 10 secondes) existent
     * 2. Le serial est différent du dernier connu
     * 3. Il y a des données médicales complètes
     */
    public function checkNewConnections(Request $request)
    {
        try {
            $lastKnownSerial = $request->query('last_serial', '');
            $lastCheckTime = $request->query('last_check', null);
            
            Log::info("🔍 Vérification connexion", [
                'last_known_serial' => $lastKnownSerial,
                'last_check' => $lastCheckTime,
                'time' => now()
            ]);
            
            // ✅ Récupérer les données récentes (< 10 secondes)
            $recentPressures = DB::table('pressures')
                ->whereNotNull('temperature')
                ->whereNotNull('bpm')
                ->whereNotNull('spo2')
                ->where('temperature', '>', 0)
                ->where('bpm', '>', 0)
                ->where('spo2', '>', 0)
                ->where('created_at', '>=', Carbon::now()->subSeconds(10))
                ->orderBy('created_at', 'desc')
                ->get();

            if ($recentPressures->isEmpty()) {
                Log::info("❌ Aucune donnée récente");
                return response()->json([
                    'new_connection' => false,
                    'message' => 'Aucune donnée récente'
                ], 200);
            }

            // ✅ Grouper par serial
            $serialGroups = $recentPressures->groupBy('serial');
            
            // ✅ Chercher un serial DIFFÉRENT avec des données fraîches
            foreach ($serialGroups as $serial => $pressures) {
                // Si le serial est différent ET non vide
                if (!empty($serial) && $serial !== $lastKnownSerial) {
                    $latestPressure = $pressures->first();
                    
                    Log::info("🔔 NOUVEAU DISPOSITIF DÉTECTÉ", [
                        'new_serial' => $serial,
                        'previous_serial' => $lastKnownSerial,
                        'data_age' => Carbon::parse($latestPressure->created_at)->diffInSeconds(now()) . 's'
                    ]);
                    
                    // Mettre à jour la connexion
                    $connexion = Connexion::updateOrCreate(
                        ['serial' => $serial],
                        [
                            'is_connected' => true,
                            'last_seen_at' => now(),
                            'last_data' => [
                                'temperature' => $latestPressure->temperature,
                                'bpm' => $latestPressure->bpm,
                                'spo2' => $latestPressure->spo2,
                                'batterie' => $latestPressure->batterie ?? 0,
                            ]
                        ]
                    );

                    // Vérifier si un patient existe
                    $patient = DB::table('gestion_patients')
                        ->where('serial', $serial)
                        ->first();
                    
                    $patientExists = $patient !== null;
                    
                    if ($patientExists) {
                        $connexion->update(['patient_registered' => true]);
                    }

                    return response()->json([
                        'new_connection' => true,
                        'serial' => $serial,
                        'patient_exists' => $patientExists,
                        'patient_info' => $patientExists ? [
                            'nom' => $patient->nom,
                            'prenom' => $patient->prenom
                        ] : null,
                        'data' => [
                            'temperature' => $latestPressure->temperature,
                            'bpm' => $latestPressure->bpm,
                            'spo2' => $latestPressure->spo2,
                            'batterie' => $latestPressure->batterie ?? 0,
                        ],
                        'timestamp' => $latestPressure->created_at,
                        'data_age_seconds' => Carbon::parse($latestPressure->created_at)->diffInSeconds(now()),
                        'message' => 'Nouveau dispositif avec données fraîches'
                    ], 200);
                }
            }

            // Aucune nouvelle connexion
            Log::info("✅ Pas de nouvelle connexion", [
                'current_serial' => $serialGroups->keys()->first()
            ]);
            
            return response()->json([
                'new_connection' => false,
                'serial' => $serialGroups->keys()->first(),
                'message' => 'Même dispositif actif'
            ], 200);

        } catch (\Exception $e) {
            Log::error("❌ Erreur checkNewConnections", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'new_connection' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Récupère le dernier serial actif
     */
    public function getLatestSerial()
    {
        try {
            $latest = DB::table('pressures')
                ->whereNotNull('temperature')
                ->whereNotNull('bpm')
                ->whereNotNull('spo2')
                ->where('temperature', '>', 0)
                ->where('bpm', '>', 0)
                ->where('spo2', '>', 0)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$latest) {
                return response()->json([
                    'serial' => '',
                    'timestamp' => null,
                    'has_medical_data' => false
                ], 200);
            }

            return response()->json([
                'serial' => $latest->serial ?? '',
                'timestamp' => $latest->created_at,
                'has_medical_data' => true,
                'data' => [
                    'temperature' => $latest->temperature,
                    'bpm' => $latest->bpm,
                    'spo2' => $latest->spo2,
                    'batterie' => $latest->batterie ?? 0
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'serial' => '',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ Marquer comme enregistré
     */
    public function markAsRegistered($serial)
    {
        try {
            $connexion = Connexion::where('serial', $serial)->first();
            
            if (!$connexion) {
                return response()->json([
                    'success' => false,
                    'message' => 'Connexion non trouvée'
                ], 404);
            }

            $connexion->update(['patient_registered' => true]);

            return response()->json([
                'success' => true,
                'message' => 'Dispositif marqué comme enregistré'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✅ MÉTHODE ALTERNATIVE : Détecter changement de serial
     * Utilise une table d'historique
     */
    public function detectSerialChange(Request $request)
    {
        try {
            // Récupérer les 2 derniers serials distincts
            $recentSerials = DB::table('pressures')
                ->select('serial', DB::raw('MAX(created_at) as last_seen'))
                ->whereNotNull('serial')
                ->where('serial', '!=', '')
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->groupBy('serial')
                ->orderBy('last_seen', 'desc')
                ->limit(2)
                ->get();

            if ($recentSerials->count() >= 2) {
                $currentSerial = $recentSerials[0]->serial;
                $previousSerial = $recentSerials[1]->serial;
                
                // Si le serial actuel est différent du précédent
                if ($currentSerial !== $previousSerial) {
                    $latestData = DB::table('pressures')
                        ->where('serial', $currentSerial)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    
                    $patientExists = DB::table('gestion_patients')
                        ->where('serial', $currentSerial)
                        ->exists();
                    
                    return response()->json([
                        'serial_changed' => true,
                        'current_serial' => $currentSerial,
                        'previous_serial' => $previousSerial,
                        'patient_exists' => $patientExists,
                        'data' => [
                            'temperature' => $latestData->temperature,
                            'bpm' => $latestData->bpm,
                            'spo2' => $latestData->spo2,
                            'batterie' => $latestData->batterie ?? 0
                        ]
                    ], 200);
                }
            }

            return response()->json([
                'serial_changed' => false,
                'message' => 'Aucun changement de serial détecté'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'serial_changed' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}