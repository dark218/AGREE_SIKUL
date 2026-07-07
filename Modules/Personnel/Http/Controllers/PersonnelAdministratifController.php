<?php

namespace Modules\Personnel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Personnel\Entities\PersonnelAdministratif;

class PersonnelAdministratifController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:personnels-administratifs-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:personnels-administratifs-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:personnels-administratifs-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:personnels-administratifs-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            $query = PersonnelAdministratif::query();

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('matricule', 'like', "%$search%")
                        ->orWhereHas('user', function ($sub) use ($search) {
                            $sub->where('nom', 'like', "%$search%")
                                ->orWhere('prenoms', 'like', "%$search%");
                        });
                });
            }

            $personnels = $query->with('user', 'departement')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($personnel) => [
                    'id' => $personnel->id,
                    'user' => [
                        'prenoms' => $personnel->user?->prenoms,
                        'nom' => $personnel->user?->nom,
                    ],
                    'matricule' => $personnel->matricule,
                    'poste' => $personnel->poste,
                    'departement' => $personnel->departement?->libelle ?? '-',
                    'statut' => $personnel->statut,
                    'deleted_at' => $personnel->deleted_at,
                ]);

            return Inertia::render('Personnel::PersonnelsAdministratifs/Index', [
                'title' => __('common.personnels_administratifs'),
                'personnelsAdministratifs' => $personnels,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::index", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Personnel::PersonnelsAdministratifs/Create', [
                'title' => __('actions.create'),
                'users' => $this->lookupUsers(),
                'departements' => $this->lookupDepartements(),
                'statutsEmployes' => \Modules\Parametrage\Entities\StatutEmploye::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
                'typesContrats' => \Modules\Parametrage\Entities\NatureContrat::actif()->orderBy('id')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'matricule' => 'required|unique:personnels_administratifs',
                'poste' => 'nullable|string|max:100',
                'departement_id' => 'nullable|exists:departements,id',
                'date_embauche' => 'nullable|date',
                'type_contrat' => 'nullable|in:cdi,cdd,vacataire,autre',
                'statut' => 'required|in:actif,suspendu,conge,retraite',
            ]);

            PersonnelAdministratif::create($validated);

            return redirect()->route('personnels_administratifs.index')
                ->with('success', __('messages.created_successfully'));
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show(PersonnelAdministratif $personnel)
    {
        try {
            $personnel->load('user', 'departement');
            $data = $personnel->toArray();
            if ($personnel->date_embauche) {
                $data['date_embauche'] = $personnel->date_embauche->format('Y-m-d');
            }

            return Inertia::render('Personnel::PersonnelsAdministratifs/Show', [
                'title' => __('actions.view'),
                'personnel' => $data,
                'users' => $this->lookupUsers(),
                'departements' => $this->lookupDepartements(),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit(PersonnelAdministratif $personnel)
    {
        try {
            $personnel->load('user', 'departement');
            $data = $personnel->toArray();
            if ($personnel->date_embauche) {
                $data['date_embauche'] = $personnel->date_embauche->format('Y-m-d');
            }

            return Inertia::render('Personnel::PersonnelsAdministratifs/Edit', [
                'title' => __('actions.edit'),
                'personnel' => $data,
                'users' => $this->lookupUsers(),
                'departements' => $this->lookupDepartements(),
                'statutsEmployes' => \Modules\Parametrage\Entities\StatutEmploye::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
                'typesContrats' => \Modules\Parametrage\Entities\NatureContrat::actif()->orderBy('id')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update(Request $request, PersonnelAdministratif $personnel)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'matricule' => 'required|unique:personnels_administratifs,matricule,' . $personnel->id,
                'poste' => 'nullable|string|max:100',
                'departement_id' => 'nullable|exists:departements,id',
                'date_embauche' => 'nullable|date',
                'type_contrat' => 'nullable|in:cdi,cdd,vacataire,autre',
                'statut' => 'required|in:actif,suspendu,conge,retraite',
            ]);

            $personnel->update($validated);

            return redirect()->route('personnels_administratifs.show', $personnel)
                ->with('success', __('messages.updated_successfully'));
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy(PersonnelAdministratif $personnel)
    {
        try {
            $personnel->delete();
            return back()->with('success', __('messages.deleted_successfully'));
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut(PersonnelAdministratif $personnel)
    {
        try {
            if ($personnel->trashed()) {
                $personnel->restore();
            } else {
                $personnel->delete();
            }

            return redirect()->route('personnels_administratifs.index')
                ->with('success', __('messages.status_changed'));
        } catch (\Throwable $th) {
            log_error("Personnel", "PersonnelAdministratifController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    private function lookupUsers(): array
    {
        return User::select('id', 'prenoms', 'nom', 'email')->get()
            ->map(fn($u) => ['id' => $u->id, 'libelle' => trim(($u->prenoms ?? '') . ' ' . ($u->nom ?? ''))])
            ->toArray();
    }

    private function lookupDepartements(): array
    {
        return \Modules\Parametrage\Entities\Departement::select('id', 'libelle')->get()->toArray();
    }
}
