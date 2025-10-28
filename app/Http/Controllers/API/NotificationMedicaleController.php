<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;  // ✅ Ajoutez cette ligne
use App\Models\NotificationMedicale;

class NotificationMedicaleController extends Controller
{
    // ✅ Lister toutes les notifications
    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => NotificationMedicale::orderBy('created_at', 'desc')->get(),
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

    // ✅ Lister les non-lues
    public function unread()
    {
        return response()->json([
            'success' => true,
            'data' => NotificationMedicale::where('is_read', false)->orderBy('created_at', 'desc')->get(),
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