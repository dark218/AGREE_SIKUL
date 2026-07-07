<?php

namespace Modules\Communication\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Communication\Entities\Notification;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:notifications-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:notifications-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:notifications-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:notifications-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            // Notification::$fillable = user_id, type, titre, message,
            // data, lu_at, action_url. Pas de colonne `statut` : le
            // filtre "non lu / lu" se base sur `lu_at` (null vs date).
            $query = Notification::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('titre', 'like', "%$search%")
                        ->orWhere('message', 'like', "%$search%");
                });
            }

            if ($request->filled('etat_lecture')) {
                $etat = $request->input('etat_lecture');
                if ($etat === 'lu')     $query->whereNotNull('lu_at');
                if ($etat === 'non_lu') $query->whereNull('lu_at');
            }

            $notifications = $query->with(['user'])->paginate(10)->withQueryString();

            return Inertia::render('Communication::Notifications/Index', [
                'notifications' => $notifications,
                'filters' => $request->only(['search', 'etat_lecture']),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Communication::Notifications/Create', [
                'users' => User::orderBy('nom')->get(['id', 'nom', 'prenoms', 'email'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            // Aligné sur Notification::$fillable (user_id, type, titre,
            // message, data, lu_at, action_url). `date_time` n'existe pas
            // dans Laravel — utiliser `date`.
            $validated = $request->validate([
                'user_id'    => 'required|exists:users,id',
                'titre'      => 'required|string|max:255',
                'message'    => 'required|string',
                'type'       => 'required|string|max:100',
                'data'       => 'nullable|array',
                'lu_at'      => 'nullable|date',
                'action_url' => 'nullable|string',
            ]);

            Notification::create($validated);

            return redirect()->route('notifications.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function show(Notification $notification)
    {
        try {
            $notification->load('user');

            return Inertia::render('Communication::Notifications/Show', [
                'notification' => $notification,
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit(Notification $notification)
    {
        try {
            return Inertia::render('Communication::Notifications/Edit', [
                'item'  => $notification->load('user'),
                'users' => User::orderBy('nom')->get(['id', 'nom', 'prenoms', 'email'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, Notification $notification)
    {
        try {
            // Aligné sur Notification::$fillable (user_id, type, titre,
            // message, data, lu_at, action_url). `date_time` n'existe pas
            // dans Laravel — utiliser `date`.
            $validated = $request->validate([
                'user_id'    => 'required|exists:users,id',
                'titre'      => 'required|string|max:255',
                'message'    => 'required|string',
                'type'       => 'required|string|max:100',
                'data'       => 'nullable|array',
                'lu_at'      => 'nullable|date',
                'action_url' => 'nullable|string',
            ]);

            $notification->update($validated);

            return redirect()->route('notifications.show', $notification)
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Notification $notification)
    {
        try {
            $notification->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut(Notification $notification)
    {
        try {
            if ($notification->trashed()) {
                $notification->restore();
            } else {
                $notification->delete();
            }

            return redirect()->route('notifications.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Communication", "NotificationController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
