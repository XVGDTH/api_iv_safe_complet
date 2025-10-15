<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserSession;

class PresenceController extends Controller
{
    // Update last_seen and keep session alive
    public function ping(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
            ], 401);
        }

        $user->last_seen_at = now();
        $user->save();

        // If no open session, start one now
        $open = UserSession::where('user_id', $user->id)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();
        if (!$open) {
            UserSession::create([
                'user_id'    => $user->id,
                'started_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'presence updated',
            'data'    => [
                'last_seen_at' => $user->last_seen_at,
                'is_online'    => $user->is_online,
            ],
        ]);
    }
}

