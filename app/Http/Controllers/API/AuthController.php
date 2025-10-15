<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\UserSession;

class AuthController extends Controller
{
    /**
     * Inscription d'un nouvel utilisateur
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'Id' => 'required|integer|unique:users,Id',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create([
                'Id' => $request->Id,
                // 'name' => $request->Id, 
                'name' => $request->name,

                'prenom' => $request->prenom ?? null,
                'email' => $request->email,
                'telephone' => $request->telephone ?? null,
                'statut' => $request->statut ?? null,

                'password' => Hash::make($request->password),
                
                
                
                
            ]);

            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Inscription réussie',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Connexion d'un utilisateur
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                "Id" => 'required|integer',
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreurs de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            // PROBLÈME PRINCIPAL : Auth::attempt() ne fonctionne pas avec 'Id'
            // Laravel s'attend à 'email' ou 'username' par défaut
            // Solution : Rechercher l'utilisateur manuellement
            $user = User::where('Id', $request->Id)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Les informations d\'identification sont incorrectes.'
                ], 401);
            }

            // Connecter l'utilisateur manuellement
            Auth::login($user);

            // Supprimer les anciens tokens et créer un nouveau
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            // Presence: close any open session then start a new one
            $open = UserSession::where('user_id', $user->id)
                ->whereNull('ended_at')
                ->latest('started_at')
                ->first();
            if ($open) {
                $open->ended_at = now();
                $open->duration_seconds = $open->started_at->diffInSeconds($open->ended_at);
                $open->save();
            }
            UserSession::create([
                'user_id'    => $user->id,
                'started_at' => now(),
            ]);

            // Update presence timestamps
            $user->last_login_at = now();
            $user->last_seen_at = now();
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Connexion réussie',
                'data' => [
                    'user' => $user,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Déconnexion d'un utilisateur
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            if ($user) {
                // Close open session if any
                $open = UserSession::where('user_id', $user->id)
                    ->whereNull('ended_at')
                    ->latest('started_at')
                    ->first();
                if ($open) {
                    $open->ended_at = now();
                    $open->duration_seconds = $open->started_at->diffInSeconds($open->ended_at);
                    $open->save();
                }
                $user->last_logout_at = now();
                $user->last_seen_at = now();
                $user->save();
            }

            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Déconnexion réussie.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la déconnexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupération des informations de l'utilisateur connecté
     */
    public function user(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $request->user()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des données utilisateur',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
