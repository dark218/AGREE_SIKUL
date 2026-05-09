<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class ApprenantPortalController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Récupère l'apprenant lié à l'utilisateur connecté
     */
    private function getApprenant()
    {
        return DB::table('apprenants')->where('user_id', Auth::id())->first();
    }

    // =========================================================================
    // CERTIFICATS
    // =========================================================================

    public function certificats()
    {
        $apprenant = $this->getApprenant();

        if (!$apprenant) {
            return Inertia::render('Apprenant/Certificats', [
                'apprenant' => null,
                'certificats' => [],
            ]);
        }

        $classe = DB::table('classes')->where('id', $apprenant->classe_id)->first();
        $ecole = $classe ? DB::table('ecoles')->where('id', $classe->ecole_id)->first() : null;

        // Bulletins disponibles (peuvent être téléchargés comme certificats)
        $bulletins = DB::table('bulletins')
            ->where('apprenant_id', $apprenant->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $periodeLabels = [
            'trimestre1' => '1er Trimestre', 'trimestre2' => '2e Trimestre', 'trimestre3' => '3e Trimestre',
            'semestre1' => '1er Semestre', 'semestre2' => '2e Semestre', 'annuel' => 'Annuel',
        ];

        // Construire la liste des certificats disponibles
        $certificats = [];

        // Attestation d'inscription
        $certificats[] = [
            'id' => 'attestation-inscription',
            'type' => 'attestation',
            'titre' => 'Attestation d\'inscription',
            'description' => 'Certifie que vous êtes inscrit(e) en ' . ($classe->nom ?? '—') . ' à ' . ($ecole->nom ?? '—'),
            'disponible' => true,
            'url' => '/academique/pdf/apprenant/' . $apprenant->id . '/attestation',
            'icone' => 'fas fa-file-certificate',
        ];

        // Fiche apprenant
        $certificats[] = [
            'id' => 'fiche-apprenant',
            'type' => 'fiche',
            'titre' => 'Fiche individuelle',
            'description' => 'Votre fiche personnelle avec toutes vos informations',
            'disponible' => true,
            'url' => '/academique/pdf/apprenant/' . $apprenant->id . '/fiche',
            'icone' => 'fas fa-id-card',
        ];

        // Bulletins
        foreach ($bulletins as $b) {
            $certificats[] = [
                'id' => 'bulletin-' . $b->id,
                'type' => 'bulletin',
                'titre' => 'Bulletin - ' . ($periodeLabels[$b->periode] ?? $b->periode),
                'description' => 'Moyenne: ' . ($b->moyenne_generale ?? '—') . '/20 — Rang: ' . ($b->rang ?? '—'),
                'disponible' => true,
                'url' => '/academique/pdf/bulletin/' . $b->id,
                'icone' => 'fas fa-file-pdf',
            ];
        }

        return Inertia::render('Apprenant/Certificats', [
            'apprenant' => [
                'id' => $apprenant->id,
                'nom' => $apprenant->nom,
                'prenoms' => $apprenant->prenoms,
                'matricule' => $apprenant->matricule,
                'classe' => $classe->nom ?? '—',
                'ecole' => $ecole->nom ?? '—',
            ],
            'certificats' => $certificats,
        ]);
    }

    // =========================================================================
    // PAIEMENTS
    // =========================================================================

    public function paiements()
    {
        $apprenant = $this->getApprenant();

        if (!$apprenant) {
            return Inertia::render('Apprenant/Paiements', [
                'apprenant' => null,
                'paiements' => [],
                'facturations' => [],
                'solde' => 0,
            ]);
        }

        $classe = DB::table('classes')->where('id', $apprenant->classe_id)->first();

        // Paiements effectués
        $paiements = DB::table('paiements')
            ->where('apprenant_id', $apprenant->id)
            ->orderBy('date_paiement', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'montant' => $p->montant_cents / 100,
                'date' => $p->date_paiement ? Carbon::parse($p->date_paiement)->format('d/m/Y') : '—',
                'mode' => $p->mode_paiement ?? '—',
                'numero_recu' => $p->numero_recu ?? '—',
                'reference' => $p->reference_transaction ?? '—',
                'statut' => $p->statut ?? 'en_attente',
                'observations' => $p->observations ?? '',
            ])->toArray();

        // Facturations
        $facturations = DB::table('facturations_apprenants')
            ->where('apprenant_id', $apprenant->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'libelle' => $f->libelle ?? '—',
                'montant' => ($f->montant_cents ?? 0) / 100,
                'montant_paye' => ($f->montant_paye_cents ?? 0) / 100,
                'reste' => (($f->montant_cents ?? 0) - ($f->montant_paye_cents ?? 0)) / 100,
                'echeance' => $f->date_echeance ? Carbon::parse($f->date_echeance)->format('d/m/Y') : '—',
                'statut' => $f->statut ?? 'en_cours',
            ])->toArray();

        $totalFacture = collect($facturations)->sum('montant');
        $totalPaye = collect($paiements)->where('statut', 'confirmé')->sum('montant');

        return Inertia::render('Apprenant/Paiements', [
            'apprenant' => [
                'id' => $apprenant->id,
                'nom' => $apprenant->nom,
                'prenoms' => $apprenant->prenoms,
                'matricule' => $apprenant->matricule,
                'classe' => $classe->nom ?? '—',
            ],
            'paiements' => $paiements,
            'facturations' => $facturations,
            'totalFacture' => $totalFacture,
            'totalPaye' => $totalPaye,
            'solde' => $totalFacture - $totalPaye,
        ]);
    }

    // =========================================================================
    // NOTIFICATIONS
    // =========================================================================

    public function notifications()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->orderBy('is_read', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Apprenant/Notifications', [
            'notifications' => $notifications,
            'unreadCount' => Notification::where('user_id', Auth::id())->where('is_read', false)->count(),
        ]);
    }

    public function markNotificationRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())->findOrFail($id);
        $notification->update(['is_read' => true]);

        return back()->with('success', 'Notification marquée comme lue');
    }

    public function markAllNotificationsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return back()->with('success', 'Toutes les notifications marquées comme lues');
    }

    // =========================================================================
    // CHAT
    // =========================================================================

    public function chat()
    {
        $userId = Auth::id();

        // Trouver les admins (users avec rôle super-admin ou admin)
        $admins = User::role(['super-admin', 'admin', 'directeur'])
            ->select('id', 'nom', 'prenoms', 'email')
            ->get()
            ->map(fn($u) => [
                'id' => $u->id,
                'nom' => trim(($u->prenoms ?? '') . ' ' . ($u->nom ?? '')),
                'email' => $u->email,
                'initials' => strtoupper(substr($u->prenoms ?? '', 0, 1) . substr($u->nom ?? '', 0, 1)),
            ])->toArray();

        // Messages de chat de l'utilisateur
        $messages = ChatMessage::where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->with(['sender:id,nom,prenoms', 'receiver:id,nom,prenoms'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'receiver_id' => $m->receiver_id,
                'sender_nom' => trim(($m->sender->prenoms ?? '') . ' ' . ($m->sender->nom ?? '')),
                'receiver_nom' => trim(($m->receiver->prenoms ?? '') . ' ' . ($m->receiver->nom ?? '')),
                'message' => $m->message,
                'is_read' => $m->is_read,
                'is_mine' => $m->sender_id === $userId,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time' => $m->created_at->format('H:i'),
            ])->toArray();

        // Marquer les messages reçus comme lus
        ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return Inertia::render('Apprenant/Chat', [
            'admins' => $admins,
            'messages' => $messages,
            'currentUserId' => $userId,
        ]);
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message' => 'required|string|max:2000',
        ]);

        $user = Auth::user();

        // Créer le message chat
        ChatMessage::create([
            'sender_id' => $user->id,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
        ]);

        // Créer une notification pour le destinataire
        Notification::create([
            'title' => 'Nouveau message de ' . trim(($user->prenoms ?? '') . ' ' . ($user->nom ?? '')),
            'body' => \Illuminate\Support\Str::limit($validated['message'], 100),
            'user_id' => $validated['receiver_id'],
            'is_read' => false,
        ]);

        // Envoyer un email au destinataire
        $receiver = User::find($validated['receiver_id']);
        if ($receiver && $receiver->email) {
            try {
                Mail::raw(
                    "Bonjour " . trim(($receiver->prenoms ?? '') . ' ' . ($receiver->nom ?? '')) . ",\n\n" .
                    "Vous avez reçu un nouveau message de " . trim(($user->prenoms ?? '') . ' ' . ($user->nom ?? '')) . " :\n\n" .
                    "\"" . $validated['message'] . "\"\n\n" .
                    "Connectez-vous à votre espace pour répondre.\n\n" .
                    "Cordialement,\nAGREE SIKUL",
                    function ($mail) use ($receiver) {
                        $mail->to($receiver->email)
                             ->subject('Nouveau message - AGREE SIKUL');
                    }
                );
            } catch (\Throwable $e) {
                // Log l'erreur mais ne pas bloquer l'envoi du message
                \Log::warning('Email chat notification failed: ' . $e->getMessage());
            }
        }

        return back()->with('success', 'Message envoyé');
    }

    public function chatMessages(Request $request)
    {
        $userId = Auth::id();

        $messages = ChatMessage::where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            })
            ->with(['sender:id,nom,prenoms', 'receiver:id,nom,prenoms'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'sender_id' => $m->sender_id,
                'receiver_id' => $m->receiver_id,
                'sender_nom' => trim(($m->sender->prenoms ?? '') . ' ' . ($m->sender->nom ?? '')),
                'message' => $m->message,
                'is_read' => $m->is_read,
                'is_mine' => $m->sender_id === $userId,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'time' => $m->created_at->format('H:i'),
            ])->toArray();

        // Marquer comme lus
        ChatMessage::where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json($messages);
    }

    public function unreadChatCount()
    {
        $count = ChatMessage::where('receiver_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }
}
