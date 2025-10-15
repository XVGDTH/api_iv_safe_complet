<?php
// ===================================================================
// 1. CONTRÔLEUR COMMANDECONTROLLER
// app/Http/Controllers/API/CommandeController.php
// ===================================================================

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CommandeController extends Controller
{
    /**
     * Liste des commandes d'un patient
     */
    public function index($patient_id)
    {
        try {
            $patient = Patient::findOrFail($patient_id);

            $commandes = Commande::where('patient_id', $patient_id)
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $commandes,
                'patient' => [
                    'id' => $patient->id,
                    'nom_complet' => $patient->nom . ' ' . $patient->prenom,
                    'esp32_id' => $patient->esp32_id
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Créer une nouvelle commande
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'patient_id' => 'required|exists:patients,id',
                'type_commande' => 'required|in:start_perfusion,stop_perfusion,pause_perfusion,set_debit,open_vanne,close_vanne,activate_buzzer,deactivate_buzzer,led_on,led_off,emergency_stop',
                'parametres' => 'nullable|array',
                'envoye_par' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Récupérer le patient pour obtenir l'esp32_id
            $patient = Patient::findOrFail($request->patient_id);

            $commande = Commande::create([
                'patient_id' => $request->patient_id,
                'esp32_id' => $patient->esp32_id,
                'type_commande' => $request->type_commande,
                'parametres' => $request->parametres ?? [],
                'statut' => 'pending',
                'envoye_par' => $request->envoye_par ?? (Auth::check() ? Auth::user()->name : 'system')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => $commande
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Afficher une commande spécifique
     */
    public function show($id)
    {
        try {
            $commande = Commande::with('patient')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $commande
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mettre à jour une commande
     */
    public function update(Request $request, $id)
    {
        try {
            $commande = Commande::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'statut' => 'sometimes|in:pending,sent,acknowledged,executed,failed',
                'reponse_esp32' => 'nullable|string',
                'parametres' => 'nullable|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $updateData = [];

            if ($request->has('statut')) {
                $updateData['statut'] = $request->statut;

                // Marquer les timestamps selon le statut
                if ($request->statut === 'sent') {
                    $updateData['envoye_at'] = now();
                } elseif ($request->statut === 'executed') {
                    $updateData['execute_at'] = now();
                }
            }

            if ($request->has('reponse_esp32')) {
                $updateData['reponse_esp32'] = $request->reponse_esp32;
            }

            if ($request->has('parametres')) {
                $updateData['parametres'] = $request->parametres;
            }

            $commande->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Commande mise à jour avec succès',
                'data' => $commande->fresh()
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
     * Supprimer une commande
     */
    public function destroy($id)
    {
        try {
            $commande = Commande::findOrFail($id);
            $commande->delete();

            return response()->json([
                'success' => true,
                'message' => 'Commande supprimée avec succès'
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
     * ESP32 récupère ses commandes en attente
     */
    public function getCommandesEsp32($esp32_id)
    {
        try {
            $commandes = Commande::where('esp32_id', $esp32_id)
                                ->whereIn('statut', ['pending', 'sent'])
                                ->orderBy('created_at', 'asc')
                                ->get();

            // Marquer les commandes comme envoyées
            Commande::where('esp32_id', $esp32_id)
                   ->where('statut', 'pending')
                   ->update([
                       'statut' => 'sent',
                       'envoye_at' => now()
                   ]);

            return response()->json([
                'success' => true,
                'data' => $commandes,
                'count' => $commandes->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes ESP32',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ESP32 confirme l'exécution d'une commande
     */
    public function confirmerExecution(Request $request, $commande_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'statut' => 'required|in:executed,failed',
                'reponse_esp32' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Données de validation invalides',
                    'errors' => $validator->errors()
                ], 422);
            }

            $commande = Commande::findOrFail($commande_id);

            if ($request->statut === 'executed') {
                $commande->marquerExecute($request->reponse_esp32);
            } else {
                $commande->marquerEchoue($request->reponse_esp32 ?? 'Échec de l\'exécution');
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour',
                'data' => $commande->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

