<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Communication\Entities\Message;

class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:messages-list', ['only' => ['index', 'show', 'reply']]);
        $this->middleware('permission.check:messages-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:messages-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:messages-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Message::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('objet', 'like', "%$search%")
                    ->orWhere('contenu', 'like', "%$search%");
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->input('etat'));
            }

            $messages = $query->with(['expediteur'])->paginate(10)->withQueryString();

            return Inertia::render('Communication::Messages/Index', [
                'messages' => $messages,
                'filters' => $request->only(['search', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            // Vérifier que l'utilisateur est connecté
            if (!auth()->check() || !auth()->user()) {
                return redirect()->route('login')->with('error', 'Vous devez être connecté');
            }

            // Charger tous les utilisateurs sauf l'utilisateur connecté (pour destinataire)
            $currentUserId = auth()->user()->id;

            $users = \App\Models\User::where('id', '!=', $currentUserId)
                ->select('id', 'nom', 'prenoms', 'email', 'login')
                ->orderBy('nom')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'label' => trim(($user->nom ?? '') . ' ' . ($user->prenoms ?? '')) . ' (' . ($user->email ?? '') . ')',
                    'nom' => $user->nom ?? '',
                    'prenoms' => $user->prenoms ?? '',
                    'email' => $user->email ?? '',
                ])
                ->toArray();

            return Inertia::render('Communication::Messages/Create', [
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            \Log::error("MessageController::create - Exception: " . $th->getMessage());
            \Log::error("Stack trace: " . $th->getTraceAsString());
            log_error("Communication", "MessageController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'destinataires_ids' => 'required|array|min:1',
                'destinataires_ids.*' => 'exists:users,id',
                'objet' => 'required|string|max:255',
                'contenu' => 'required|string',
            ]);

            $expediteurId = auth()->user()->id;

            // Vérifier que l'expéditeur ne s'envoie pas un message à lui-même
            if (in_array($expediteurId, $validated['destinataires_ids'])) {
                return back()->with('error', 'Vous ne pouvez pas vous envoyer un message à vous-même');
            }

            // Auto-remplir les champs système
            $validated['expediteur_id'] = $expediteurId;
            $validated['date_envoi'] = now();
            $validated['lu_par'] = []; // Vide au départ - aucun destinataire n'a lu
            $validated['creation_username'] = auth()->user()->name;

            Message::create($validated);

            return redirect()->route('messages.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Message $message)
    {
        try {
            $message->load('expediteur');

            $currentUserId = auth()->user()->id;
            $users = \App\Models\User::where('id', '!=', $currentUserId)
                ->select('id', 'nom', 'prenoms', 'email')
                ->orderBy('nom')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'label' => trim(($user->nom ?? '') . ' ' . ($user->prenoms ?? '')) . ' (' . ($user->email ?? '') . ')',
                    'nom' => $user->nom ?? '',
                    'prenoms' => $user->prenoms ?? '',
                    'email' => $user->email ?? '',
                ])
                ->toArray();

            return Inertia::render('Communication::Messages/Show', [
                'item' => $message,
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function reply(Message $message)
    {
        try {
            $message->load('expediteur');

            $currentUserId = auth()->user()->id;
            $users = \App\Models\User::where('id', '!=', $currentUserId)
                ->select('id', 'nom', 'prenoms', 'email')
                ->orderBy('nom')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'label' => trim(($user->nom ?? '') . ' ' . ($user->prenoms ?? '')) . ' (' . ($user->email ?? '') . ')',
                    'nom' => $user->nom ?? '',
                    'prenoms' => $user->prenoms ?? '',
                    'email' => $user->email ?? '',
                ])
                ->toArray();

            return Inertia::render('Communication::Messages/Reply', [
                'item' => $message,
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::reply", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Message $message)
    {
        try {
            $message->load('expediteur');

            $currentUserId = auth()->user()->id;
            $users = \App\Models\User::where('id', '!=', $currentUserId)
                ->select('id', 'nom', 'prenoms', 'email')
                ->orderBy('nom')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'label' => trim(($user->nom ?? '') . ' ' . ($user->prenoms ?? '')) . ' (' . ($user->email ?? '') . ')',
                    'nom' => $user->nom ?? '',
                    'prenoms' => $user->prenoms ?? '',
                    'email' => $user->email ?? '',
                ])
                ->toArray();

            return Inertia::render('Communication::Messages/Edit', [
                'item' => $message,
                'users' => $users,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Message $message)
    {
        try {
            // Les messages ne peuvent être modifiés que pour marquer comme lu
            // Seul le destinataire (actuellement connecté) peut marquer comme lu
            $userId = auth()->user()->id;

            // Vérifier que l'utilisateur est un des destinataires
            if (!in_array($userId, $message->destinataires_ids ?? [])) {
                return back()->with('error', 'Vous ne pouvez pas modifier ce message');
            }

            // Marquer le message comme lu par cet utilisateur
            $luPar = $message->lu_par ?? [];
            if (!in_array($userId, $luPar)) {
                $luPar[] = $userId;
            }

            $message->update([
                'lu_par' => $luPar,
                'modification_username' => auth()->user()->name,
            ]);

            return redirect()->route('messages.show', $message)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Message $message)
    {
        try {
            $message->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Message $message)
    {
        try {
            if ($message->trashed()) {
                $message->restore();
            } else {
                $message->delete();
            }

            return redirect()->route('messages.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Communication", "MessageController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
