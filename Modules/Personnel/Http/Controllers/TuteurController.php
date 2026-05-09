<?php

namespace Modules\Personnel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Tuteur;
use Modules\Academique\Entities\Apprenant;

class TuteurController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:tuteurs-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:tuteurs-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:tuteurs-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:tuteurs-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        $query = Tuteur::query();

        // Filtres
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nom', 'like', "%$search%")
                  ->orWhere('prenoms', 'like', "%$search%");
            })->orWhere('relation', 'like', "%$search%");
        }

        $relationLabels = [
            'pere' => 'Père', 'mere' => 'Mère', 'tuteur_legal' => 'Tuteur légal',
            'grand_parent' => 'Grand-parent', 'oncle' => 'Oncle', 'tante' => 'Tante',
            'frere' => 'Frère', 'soeur' => 'Sœur', 'cousin' => 'Cousin',
            'cousine' => 'Cousine', 'autre' => 'Autre',
        ];

        $tuteurs = $query->with(['user', 'apprenant'])->paginate(10)->withQueryString()
            ->through(function ($tuteur) use ($relationLabels) {
                return [
                    'id' => $tuteur->id,
                    'nom' => $tuteur->nom
                        ? trim($tuteur->prenoms . ' ' . $tuteur->nom)
                        : ($tuteur->user ? $tuteur->user->prenoms . ' ' . $tuteur->user->nom : '-'),
                    'relation' => $relationLabels[$tuteur->relation] ?? ($tuteur->relation ?: '-'),
                    'profession' => $tuteur->profession,
                    'employeur' => $tuteur->employeur,
                    'numero_urgence' => $tuteur->numero_urgence,
                    'apprenant' => $tuteur->apprenant ? $tuteur->apprenant->prenoms . ' ' . $tuteur->apprenant->nom : '-',
                    'deleted_at' => $tuteur->deleted_at,
                ];
            });

        return Inertia::render('Personnel::Tuteurs/Index', [
            'title' => __('common.tuteurs'),
            'tuteurs' => $tuteurs,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        try {
            $apprenants = Apprenant::whereNull('deleted_at')
                ->get(['id', 'nom', 'prenoms', 'matricule', 'user_id'])
                ->map(function ($apprenant) {
                    return [
                        'id' => $apprenant->id,
                        'libelle' => $apprenant->prenoms . ' ' . $apprenant->nom . ' (' . $apprenant->matricule . ')',
                    ];
                })->toArray();

            return Inertia::render('Personnel::Tuteurs/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            \Log::error('❌ TuteurController::create - ERROR', [
                'message' => $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ]);
            return Inertia::render('Personnel::Tuteurs/Create', [
                'title' => __('actions.create'),
                'apprenants' => [],
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'apprenant_id' => 'nullable|exists:apprenants,id',
                'nom' => 'required|string|max:255',
                'prenoms' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'adresse' => 'nullable|string|max:500',
                'relation' => 'nullable|string|max:100',
                'profession' => 'nullable|string|max:100',
                'employeur' => 'nullable|string|max:100',
                'numero_urgence' => 'nullable|string|max:20',
            ]);

            // Créer ou trouver le user associé au tuteur
            $userId = $validated['user_id'] ?? null;
            if (!$userId) {
                $userId = \App\Models\User::create([
                    'nom'       => $validated['nom'],
                    'prenoms'   => $validated['prenoms'] ?? null,
                    'email'     => $validated['email'] ?? null,
                    'login'     => $validated['telephone'] ?? $validated['email'] ?? 'tuteur-' . time(),
                    'full_login'=> $validated['telephone'] ?? $validated['email'] ?? 'tuteur-' . time(),
                    'password'  => bcrypt('password123'),
                    'role'      => 'parent',
                    'statut'    => 'actif',
                ])->id;
            }

            // Créer le tuteur avec uniquement les colonnes de la table tuteurs
            Tuteur::create([
                'user_id'          => $userId,
                'apprenant_id'     => $validated['apprenant_id'] ?? null,
                'relation'         => $validated['relation'] ?? null,
                'profession'       => $validated['profession'] ?? null,
                'employeur'        => $validated['employeur'] ?? null,
                'numero_urgence'   => $validated['numero_urgence'] ?? null,
            ]);

            return redirect()->route('tuteurs.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Personnel", "TuteurController::store", $th->getMessage());
            return back()->with('error', __('messages.error_occurred') . ': ' . $th->getMessage());
        }
    }

    public function show(Tuteur $tuteur)
    {
        $tuteur->load(['user', 'apprenant']);

        $apprenants = Apprenant::whereNull('deleted_at')
            ->get(['id', 'nom', 'prenoms', 'matricule'])
            ->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->prenoms . ' ' . $a->nom . ' (' . $a->matricule . ')',
            ])->toArray();

        return Inertia::render('Personnel::Tuteurs/Show', [
            'title' => __('actions.view'),
            'tuteur' => $tuteur,
            'apprenants' => $apprenants,
        ]);
    }

    public function edit(Tuteur $tuteur)
    {
        $apprenants = Apprenant::whereNull('deleted_at')
            ->get(['id', 'nom', 'prenoms', 'matricule'])
            ->map(fn($a) => [
                'id' => $a->id,
                'libelle' => $a->prenoms . ' ' . $a->nom . ' (' . $a->matricule . ')',
            ])->toArray();

        return Inertia::render('Personnel::Tuteurs/Edit', [
            'title' => __('actions.edit'),
            'tuteur' => $tuteur->load(['user', 'apprenant']),
            'apprenants' => $apprenants,
        ]);
    }

    public function update(Request $request, Tuteur $tuteur)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'nullable|exists:users,id',
                'apprenant_id' => 'nullable|exists:apprenants,id',
                'nom' => 'required|string|max:255',
                'prenoms' => 'nullable|string|max:255',
                'telephone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'adresse' => 'nullable|string|max:500',
                'relation' => 'nullable|string|max:100',
                'profession' => 'nullable|string|max:100',
                'employeur' => 'nullable|string|max:100',
                'numero_urgence' => 'nullable|string|max:20',
            ]);

            // Mettre à jour le user associé
            if ($tuteur->user_id) {
                \App\Models\User::where('id', $tuteur->user_id)->update(array_filter([
                    'nom'     => $validated['nom'] ?? null,
                    'prenoms' => $validated['prenoms'] ?? null,
                    'email'   => $validated['email'] ?? null,
                ]));
            }

            // Mettre à jour le tuteur
            $tuteur->update([
                'apprenant_id'   => $validated['apprenant_id'] ?? $tuteur->apprenant_id,
                'relation'       => $validated['relation'] ?? $tuteur->relation,
                'profession'     => $validated['profession'] ?? $tuteur->profession,
                'employeur'      => $validated['employeur'] ?? $tuteur->employeur,
                'numero_urgence' => $validated['numero_urgence'] ?? $tuteur->numero_urgence,
            ]);

            return redirect()->route('tuteurs.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Personnel", "TuteurController::update", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function destroy(Tuteur $tuteur)
    {
        try {
            $tuteur->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Personnel", "TuteurController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Tuteur $tuteur)
    {
        try {
            if ($tuteur->trashed()) {
                $tuteur->restore();
            } else {
                $tuteur->delete();
            }

            return redirect()->route('tuteurs.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Personnel", "TuteurController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
