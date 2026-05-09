<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Presence;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Seance;

class PresencesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:presences-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:presences-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:presences-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:presences-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Presence::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->whereHas('user', function ($user) use ($search) {
                        $user->where('nom', 'like', "%$search%")
                            ->orWhere('prenoms', 'like', "%$search%");
                    });
                });
            }

            $presences = $query->with(['apprenant.user', 'seance.cours', 'saisitPar'])
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Academique::Presences/Index', [
                'title' => __('common.presences'),
                'presences' => $presences,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::index - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            $apprenants = Apprenant::whereNull('deleted_at')->with('user')
                ->select('id', 'user_id', 'matricule')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'libelle' => ($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? '') . ' (' . $a->matricule . ')'
                ])->toArray();

            $seances = Seance::whereNull('deleted_at')->with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'libelle' => ($s->cours ? $s->cours->titre : 'Sans cours') . ' - ' . $s->date->format('d/m/Y') . ' ' . (isset($s->heure_debut) && $s->heure_debut ? substr($s->heure_debut, 0, 5) : '--:--')
                ])->toArray();

            return Inertia::render('Academique::Presences/Create', [
                'title' => __('actions.create'),
                'apprenants' => $apprenants,
                'seances' => $seances,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::create - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'seance_id' => 'required|exists:seances,id',
                'statut' => 'required|in:present,retard,absent,malade,permis',
                'heure_arrivee' => 'nullable|date_format:H:i',
                'remarques' => 'nullable|string|max:1000',
            ]);

            $validated['saisi_par'] = auth()->id();
            Presence::create($validated);

            return redirect()->route('academique.presences.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::store - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(Presence $presence)
    {
        try {
            $presence->load('apprenant.user', 'seance.cours');

            $apprenants = Apprenant::whereNull('deleted_at')->with('user')
                ->select('id', 'user_id', 'matricule')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'libelle' => ($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? '') . ' (' . $a->matricule . ')'
                ])->toArray();

            $seances = Seance::whereNull('deleted_at')->with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'libelle' => ($s->cours ? $s->cours->titre : 'Sans cours') . ' - ' . $s->date->format('d/m/Y') . ' ' . (isset($s->heure_debut) && $s->heure_debut ? substr($s->heure_debut, 0, 5) : '--:--')
                ])->toArray();

            return Inertia::render('Academique::Presences/Show', [
                'title' => __('actions.view'),
                'presence' => $presence,
                'apprenants' => $apprenants,
                'seances' => $seances,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::show - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(Presence $presence)
    {
        try {
            $presence->load('apprenant.user', 'seance.cours');

            $apprenants = Apprenant::whereNull('deleted_at')->with('user')
                ->select('id', 'user_id', 'matricule')
                ->get()
                ->map(fn($a) => [
                    'id' => $a->id,
                    'libelle' => ($a->user?->prenoms ?? '') . ' ' . ($a->user?->nom ?? '') . ' (' . $a->matricule . ')'
                ])->toArray();

            $seances = Seance::whereNull('deleted_at')->with('cours:id,titre')
                ->select('id', 'date', 'heure_debut', 'heure_fin', 'cours_id')
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'libelle' => ($s->cours ? $s->cours->titre : 'Sans cours') . ' - ' . $s->date->format('d/m/Y') . ' ' . (isset($s->heure_debut) && $s->heure_debut ? substr($s->heure_debut, 0, 5) : '--:--')
                ])->toArray();

            return Inertia::render('Academique::Presences/Edit', [
                'title' => __('actions.edit'),
                'presence' => $presence,
                'apprenants' => $apprenants,
                'seances' => $seances,
            ]);
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::edit - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, Presence $presence)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id',
                'seance_id' => 'required|exists:seances,id',
                'statut' => 'required|in:present,retard,absent,malade,permis',
                'heure_arrivee' => 'nullable|date_format:H:i',
                'remarques' => 'nullable|string|max:1000',
            ]);

            $presence->update($validated);

            return redirect()->route('academique.presences.show', $presence)
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::update - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(Presence $presence)
    {
        try {
            $presence->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::destroy - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(Presence $presence)
    {
        try {
            if ($presence->trashed()) {
                $presence->restore();
            } else {
                $presence->delete();
            }
            return redirect()->route('academique.presences.index')
                ->with('success', __('messages.status_changed'));
        } catch (\Throwable $th) {
            \Log::error("Academique::PresencesController::statut - " . $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
