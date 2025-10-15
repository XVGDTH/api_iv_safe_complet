<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\UserSession;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs (admin)
     */
    public function index()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(20);
        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Liste publique (sans Sanctum) – si vous souhaitez l'exposer.
     */
    public function indexPublic()
    {
        return $this->index();
    }

    /**
     * Créer un utilisateur (admin)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Id' => 'required|integer|unique:users,Id',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'age' => 'nullable|integer|min:0|max:120',
            'esp32_id' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'statut' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'Id' => $request->Id,
            'name' => $request->nom,
            'prenom' => $request->prenom,
            'email' => $request->email,
            'password' => $request->password, // cast 'hashed' dans le modèle
            'age' => $request->age,
            'esp32_id' => $request->esp32_id,
            'telephone' => $request->telephone,
            'statut' => $request->statut,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur créé avec succès',
            'data' => $user,
        ], 201);
    }

    /**
     * Création publique (sans Sanctum) – à utiliser depuis le dashboard si souhaité.
     * ATTENTION: expose une surface non authentifiée; utilisez-la uniquement si vous l'assumez.
     */
    public function storePublic(Request $request)
    {
        return $this->store($request);
    }

    /**
     * Voir un utilisateur (admin)
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    /**
     * Voir un utilisateur (public)
     */
    public function showPublic($id)
    {
        return $this->show($id);
    }

    /**
     * Mettre à jour un utilisateur (admin)
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'Id' => 'sometimes|integer|unique:users,Id,' . $user->id,
            'nom' => 'sometimes|string|max:255',
            'prenom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
            'age' => 'nullable|integer|min:0|max:120',
            'esp32_id' => 'nullable|string|max:255',
            'telephone' => 'nullable|string|max:50',
            'statut' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreurs de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $update = [];
        foreach (['Id','prenom','age','esp32_id','telephone','statut'] as $field) {
            if ($request->has($field)) $update[$field] = $request->input($field);
        }
        if ($request->has('nom')) $update['name'] = $request->nom;
        if ($request->has('email')) $update['email'] = $request->email;
        if ($request->filled('password')) $update['password'] = $request->password;

        $user->update($update);

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur mis à jour',
            'data' => $user->fresh(),
        ]);
    }

    /**
     * Mettre à jour (public)
     */
    public function updatePublic(Request $request, $id)
    {
        return $this->update($request, $id);
    }

    /**
     * Supprimer un utilisateur (admin)
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé'
        ], 200);
    }

    /**
     * Supprimer (public)
     */
    public function destroyPublic($id)
    {
        return $this->destroy($id);
    }

    /**
     * Historique des sessions d'un utilisateur (admin/public)
     */
    public function history($id)
    {
        $user = User::findOrFail($id);
        $sessions = UserSession::where('user_id', $user->id)
            ->orderBy('started_at', 'asc')
            ->get(['id','started_at','ended_at','duration_seconds']);

        $totalConnected = (int) $sessions->sum('duration_seconds');
        $totalDisconnected = 0;
        $prevEnd = null;
        foreach ($sessions as $s) {
            if ($prevEnd && $s->started_at) {
                $gap = $prevEnd->diffInSeconds($s->started_at, false);
                if ($gap > 0) $totalDisconnected += $gap;
            }
            $prevEnd = $s->ended_at ?: $s->started_at; // if open, treat start as end for next gap
        }
        // If currently offline and has last_logout_at, add gap since last logout
        if (!$user->is_online && $user->last_logout_at) {
            $totalDisconnected += now()->diffInSeconds($user->last_logout_at);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'Id' => $user->Id,
                    'name' => $user->name,
                    'prenom' => $user->prenom,
                    'email' => $user->email,
                    'is_online' => $user->is_online,
                    'last_login_at' => $user->last_login_at,
                    'last_logout_at' => $user->last_logout_at,
                    'last_seen_at' => $user->last_seen_at,
                ],
                'sessions' => $sessions,
                'totals' => [
                    'total_connected_seconds' => $totalConnected,
                    'total_disconnected_seconds' => $totalDisconnected,
                    'current_session_seconds' => $user->current_session_seconds,
                    'disconnected_since_seconds' => $user->disconnected_since_seconds,
                ],
            ],
        ]);
    }
}
