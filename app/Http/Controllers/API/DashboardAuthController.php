<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dashboard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DashboardAuthController extends Controller
{
    /**
     * Enregistrement (Register)
     */
    public function register(Request $request)
    {
        $request->validate([
            'nom'        => 'required|string|max:255',
            'prenom'     => 'required|string|max:255',
            'email'      => 'required|string|email|max:255|unique:dashboards',
            'password'   => 'required|string|min:8|confirmed',
            'specialite' => 'nullable|string|max:255',
        ]);

        $dashboard = Dashboard::create([
            'nom'        => $request->nom,
            'prenom'     => $request->prenom,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'specialite' => $request->specialite,
        ]);

        $token = $dashboard->createToken('dashboard_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inscription réussie',
            'user'    => $dashboard,
            'token'   => $token,
        ], 201);
    }

    /**
     * Connexion (Login)
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        $dashboard = Dashboard::where('email', $request->email)->first();

        if (!$dashboard || !Hash::check($request->password, $dashboard->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Identifiants invalides',
            ], 401);
        }

        // Déconnexion automatique des sessions précédentes
        $dashboard->tokens()->delete();

        $token = $dashboard->createToken('dashboard_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Connexion réussie',
            'user'    => $dashboard,
            'token'   => $token,
        ]);
    }

    /**
     * Déconnexion (Logout)
     */
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Profil courant
     */
    public function me(Request $request)
    {
        $u = $request->user(); // résolu par le token Sanctum

        if (!$u) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        return response()->json([
            'id'         => $u->id,
            // on renvoie 'name' ET 'nom' pour compat UI Flutter
            'name'       => $u->name ?? $u->nom ?? '',
            'nom'        => $u->nom ?? $u->name ?? '',
            'prenom'     => $u->prenom ?? '',
            'email'      => $u->email,
            'specialite' => $u->specialite ?? null,
            'created_at' => $u->created_at,
        ]);
    }
}
