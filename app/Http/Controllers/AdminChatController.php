<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AdminChatController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Liste des conversations pour l'admin
     */
    public function index()
    {
        $adminId = Auth::id();

        // Récupérer tous les utilisateurs qui ont envoyé des messages à cet admin
        $conversations = ChatMessage::where('receiver_id', $adminId)
            ->orWhere('sender_id', $adminId)
            ->with(['sender:id,nom,prenoms,email', 'receiver:id,nom,prenoms,email'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($msg) use ($adminId) {
                return $msg->sender_id === $adminId ? $msg->receiver_id : $msg->sender_id;
            })
            ->map(function ($messages, $userId) use ($adminId) {
                $lastMsg = $messages->first();
                $otherUser = $lastMsg->sender_id === $adminId ? $lastMsg->receiver : $lastMsg->sender;
                $unread = $messages->where('receiver_id', $adminId)->where('is_read', false)->count();

                return [
                    'user_id' => $userId,
                    'user_nom' => trim(($otherUser->prenoms ?? '') . ' ' . ($otherUser->nom ?? '')),
                    'user_email' => $otherUser->email ?? '',
                    'user_initials' => strtoupper(substr($otherUser->prenoms ?? '', 0, 1) . substr($otherUser->nom ?? '', 0, 1)),
                    'last_message' => \Illuminate\Support\Str::limit($lastMsg->message, 60),
                    'last_time' => $lastMsg->created_at->format('d/m/Y H:i'),
                    'unread_count' => $unread,
                ];
            })
            ->values()
            ->toArray();

        return Inertia::render('Dashboard/AdminChat', [
            'conversations' => $conversations,
            'currentUserId' => $adminId,
        ]);
    }

    /**
     * Voir les messages d'une conversation spécifique
     */
    public function show($userId)
    {
        $adminId = Auth::id();
        $otherUser = User::select('id', 'nom', 'prenoms', 'email')->findOrFail($userId);

        $messages = ChatMessage::where(function ($q) use ($adminId, $userId) {
                $q->where('sender_id', $adminId)->where('receiver_id', $userId);
            })
            ->orWhere(function ($q) use ($adminId, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $adminId);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'message' => $m->message,
                'is_read' => $m->is_read,
                'is_mine' => $m->sender_id === $adminId,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time' => $m->created_at->format('H:i'),
            ])->toArray();

        // Marquer les messages comme lus
        ChatMessage::where('sender_id', $userId)
            ->where('receiver_id', $adminId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return Inertia::render('Dashboard/AdminChatConversation', [
            'otherUser' => [
                'id' => $otherUser->id,
                'nom' => trim(($otherUser->prenoms ?? '') . ' ' . ($otherUser->nom ?? '')),
                'email' => $otherUser->email,
                'initials' => strtoupper(substr($otherUser->prenoms ?? '', 0, 1) . substr($otherUser->nom ?? '', 0, 1)),
            ],
            'messages' => $messages,
            'currentUserId' => $adminId,
        ]);
    }

    /**
     * Répondre à un message (admin vers apprenant)
     */
    public function reply(Request $request, $userId)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $admin = Auth::user();
        $receiver = User::findOrFail($userId);

        // Créer le message
        ChatMessage::create([
            'sender_id' => $admin->id,
            'receiver_id' => $userId,
            'message' => $validated['message'],
        ]);

        // Notification sur le panel de l'apprenant
        Notification::create([
            'title' => 'Reponse de l\'administration',
            'body' => \Illuminate\Support\Str::limit($validated['message'], 100),
            'user_id' => $userId,
            'is_read' => false,
        ]);

        // Envoyer un email à l'apprenant
        if ($receiver->email) {
            try {
                Mail::raw(
                    "Bonjour " . trim(($receiver->prenoms ?? '') . ' ' . ($receiver->nom ?? '')) . ",\n\n" .
                    "L'administration vous a repondu :\n\n" .
                    "\"" . $validated['message'] . "\"\n\n" .
                    "Connectez-vous a votre espace pour voir la conversation complete.\n\n" .
                    "Cordialement,\nAGREE SIKUL",
                    function ($mail) use ($receiver) {
                        $mail->to($receiver->email)
                             ->subject('Reponse de l\'administration - AGREE SIKUL');
                    }
                );
            } catch (\Throwable $e) {
                \Log::warning('Email admin chat reply failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Reponse envoyee');
    }
}
