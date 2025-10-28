<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\NotificationMedicale;

class NotificationMedicaleController extends Controller
{
    // ✅ Lister toutes les notifications avec filtrage optionnel
    public function index(Request $request)
    {
        $query = NotificationMedicale::orderBy('created_at', 'desc');

        // Filtrage par statut de lecture
        if ($request->has('is_read')) {
            $isRead = filter_var($request->query('is_read'), FILTER_VALIDATE_BOOLEAN);
            $query->where('is_read', $isRead);
        }

        $notifications = $query->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    // ✅ Créer une notification
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'message' => 'required|string',
            'serial' => 'nullable|string',
        ]);

        $n = NotificationMedicale::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Notification enregistrée',
            'data' => $n,
        ], 201);
    }

    // ✅ Lister les non-lues (conservé pour compatibilité)
    public function unread()
    {
        $notifications = NotificationMedicale::where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    // ✅ Marquer comme lue
    public function markAsRead($id)
    {
        $n = NotificationMedicale::find($id);
        if (!$n) {
            return response()->json(['success' => false, 'message' => 'Notification introuvable'], 404);
        }
        $n->update(['is_read' => true]);
        return response()->json(['success' => true, 'message' => 'Notification lue']);
    }

    // ✅ Supprimer
    public function destroy($id)
    {
        $n = NotificationMedicale::find($id);
        if (!$n) {
            return response()->json(['success' => false, 'message' => 'Notification introuvable'], 404);
        }
        $n->delete();
        return response()->json(['success' => true, 'message' => 'Notification supprimée']);
    }
}