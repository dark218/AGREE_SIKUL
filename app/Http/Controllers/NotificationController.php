<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Récupérer les notifications de l'utilisateur
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $notifications = Notification::where("user_id", $user->id)
            ->orderBy('is_read', 'asc')  // Non lues en premier
            ->orderBy('created_at', 'desc')  // Ordre par date de création
            ->take(10)
            ->get();

        return response()->json($notifications);
    }

    /**
     * Voir une notification spécifique
     */
    public function show($id): JsonResponse
    {
        $notification = Notification::where('user_id', auth()->id())
            ->findOrFail($id);
        
        // Marquer comme lue
        $notification->is_read = true;
        $notification->save();
        
        return response()->json([
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->body,
            'created_at' => $notification->created_at->diffForHumans(),
            'is_read' => $notification->is_read,
        ]);
    }

    /**
     * Récupérer le nombre de notifications non lues
     */
    public function getUnreadCount(): JsonResponse
    {
        $unreadCount = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $unreadCount]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead($id): JsonResponse
    {
        $notification = Notification::where('user_id', auth()->id())
            ->find($id);
        
        if ($notification) {
            $notification->is_read = true;
            $notification->save();

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false], 404);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    /**
     * Supprimer toutes les notifications
     */
    public function clearAll(): JsonResponse
    {
        Notification::where('user_id', auth()->id())->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Enregistrer le token FCM dans la session
     */
    public function saveFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = auth()->user();
        $fcmToken = $request->input('fcm_token');

        // Mettre à jour la session actuelle avec le nouveau token FCM
        $sessionId = $request->session()->getId();

        \DB::table('sessions')
            ->where('id', $sessionId)
            ->update([
                'fcm_token' => $fcmToken,
                'last_activity' => now()->timestamp,
            ]);

        // AUSSI stocker dans users (pour référence)
        $user->fcm_token = $fcmToken;
        $user->save();

        return response()->json(['message' => 'Token FCM Web enregistré avec succès.']);
    }
}
