<?php

namespace Modules\Finances\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Modules\Finances\Entities\FacturationApprenant;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Ecole;

class FacturationApprenantController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = FacturationApprenant::query();

            if ($request->filled('code')) {
                $query->where('code', 'like', '%' . $request->code . '%');
            }

            if ($request->filled('libelle')) {
                $query->where('libelle', 'like', '%' . $request->libelle . '%');
            }

            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            if ($request->filled('annee_scolaire_id')) {
                $query->where('annee_scolaire_id', $request->annee_scolaire_id);
            }

            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $facturations = $query
                ->with(['anneeScolaire', 'section', 'ecole', 'campus', 'cycle', 'niveau'])
                ->paginate(10)
                ->withQueryString()
                ->through(function ($facturation) {
                    return [
                        'id' => $facturation->id,
                        'code' => $facturation->code,
                        'libelle' => $facturation->libelle,
                        'annee_scolaire_id' => $facturation->annee_scolaire_id,
                        'annee_scolaire' => $facturation->anneeScolaire ? ['id' => $facturation->anneeScolaire->id, 'libelle' => $facturation->anneeScolaire->libelle] : null,
                        'section_id' => $facturation->section_id,
                        'section' => $facturation->section ? ['id' => $facturation->section->id, 'libelle' => $facturation->section->libelle] : null,
                        'ecole_id' => $facturation->ecole_id,
                        'ecole' => $facturation->ecole ? ['id' => $facturation->ecole->id, 'nom' => $facturation->ecole->nom] : null,
                        'campus_id' => $facturation->campus_id,
                        'campus' => $facturation->campus ? ['id' => $facturation->campus->id, 'nom' => $facturation->campus->nom] : null,
                        'cycle_id' => $facturation->cycle_id,
                        'cycle' => $facturation->cycle ? ['id' => $facturation->cycle->id, 'libelle' => $facturation->cycle->libelle] : null,
                        'niveau_id' => $facturation->niveau_id,
                        'niveau' => $facturation->niveau ? ['id' => $facturation->niveau->id, 'libelle' => $facturation->niveau->libelle] : null,
                        'montant' => $facturation->montant,
                        'etat' => $facturation->etat,
                        'created_at' => $facturation->created_at,
                        'updated_at' => $facturation->updated_at,
                    ];
                });

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();

            return inertia('Finances::FacturationApprenant/Index', [
                'facturations' => $facturations,
                'anneesScolaires' => $anneesScolaires,
                'ecoles' => $ecoles,
                'filters' => $request->only(['code', 'libelle', 'ecole_id', 'annee_scolaire_id', 'etat']),
            ]);
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@index', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    public function create()
    {
        try {
            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $sections = Section::where('etat', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();
            $cycles = CycleEnseignement::where('etat', 'actif')->get();
            $niveaux = NiveauEtude::where('statut', 'actif')->get();

            return inertia('Finances::FacturationApprenant/Create', [
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'cycles' => $cycles,
                'niveaux' => $niveaux,
            ]);
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@create', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'section_id' => 'nullable|exists:sections,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'code' => 'nullable|string|max:255',
                'libelle' => 'nullable|string|max:255',
                'ligne_recette' => 'nullable|string|max:255',
                'unite_facturation' => 'nullable|string|max:255',
                'quantite' => 'nullable|numeric|min:0',
                'montant' => 'nullable|numeric|min:0',
                'date_debut_exigibilite' => 'nullable|date',
                'date_fin_exigibilite' => 'nullable|date',
                'compte_comptable' => 'nullable|string|max:255',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['creation_username'] = auth()->user()?->name ?? 'system';
            $validated['modification_username'] = auth()->user()?->name ?? 'system';

            FacturationApprenant::create($validated);

            return redirect()->route('finances.facturation-apprenants.index')
                ->with('message', trans('common.record_created'));
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@store', $e);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $facturation = FacturationApprenant::with(['anneeScolaire', 'section', 'ecole', 'campus', 'cycle', 'niveau'])
                ->findOrFail($id);

            // Convert to array and format dates BEFORE casting interferes
            $item = $facturation->toArray();

            // Cast FK IDs back to int (toArray converts them to strings)
            $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
            $item['section_id'] = (int)$item['section_id'];
            $item['ecole_id'] = (int)$item['ecole_id'];
            $item['campus_id'] = (int)$item['campus_id'];
            $item['cycle_id'] = (int)$item['cycle_id'];
            $item['niveau_id'] = (int)$item['niveau_id'];

            if ($facturation->date_debut_exigibilite) {
                $item['date_debut_exigibilite'] = $facturation->date_debut_exigibilite->toDateString();
            }
            if ($facturation->date_fin_exigibilite) {
                $item['date_fin_exigibilite'] = $facturation->date_fin_exigibilite->toDateString();
            }

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $sections = Section::where('etat', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();
            $cycles = CycleEnseignement::where('etat', 'actif')->get();
            $niveaux = NiveauEtude::where('statut', 'actif')->get();

            return inertia('Finances::FacturationApprenant/Show', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'cycles' => $cycles,
                'niveaux' => $niveaux,
            ]);
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@show', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    public function edit($id)
    {
        try {
            $facturation = FacturationApprenant::findOrFail($id);

            // Convert to array and format dates BEFORE casting interferes
            $item = $facturation->toArray();

            // Cast FK IDs back to int (toArray converts them to strings)
            $item['annee_scolaire_id'] = (int)$item['annee_scolaire_id'];
            $item['section_id'] = (int)$item['section_id'];
            $item['ecole_id'] = (int)$item['ecole_id'];
            $item['campus_id'] = (int)$item['campus_id'];
            $item['cycle_id'] = (int)$item['cycle_id'];
            $item['niveau_id'] = (int)$item['niveau_id'];

            if ($facturation->date_debut_exigibilite) {
                $item['date_debut_exigibilite'] = $facturation->date_debut_exigibilite->toDateString();
            }
            if ($facturation->date_fin_exigibilite) {
                $item['date_fin_exigibilite'] = $facturation->date_fin_exigibilite->toDateString();
            }

            $anneesScolaires = AnneeScolaire::where('etat', 'actif')->get();
            $sections = Section::where('etat', 'actif')->get();
            $ecoles = Ecole::where('statut', 'actif')->get();
            $campuses = Campus::where('statut', 'actif')->get();
            $cycles = CycleEnseignement::where('etat', 'actif')->get();
            $niveaux = NiveauEtude::where('statut', 'actif')->get();

            return inertia('Finances::FacturationApprenant/Edit', [
                'item' => $item,
                'anneesScolaires' => $anneesScolaires,
                'sections' => $sections,
                'ecoles' => $ecoles,
                'campuses' => $campuses,
                'cycles' => $cycles,
                'niveaux' => $niveaux,
            ]);
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@edit', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $facturation = FacturationApprenant::findOrFail($id);

            $validated = $request->validate([
                'annee_scolaire_id' => 'required|exists:annees_scolaires,id',
                'ecole_id' => 'required|exists:ecoles,id',
                'section_id' => 'nullable|exists:sections,id',
                'campus_id' => 'nullable|exists:campuses,id',
                'cycle_id' => 'nullable|exists:cycles_enseignement,id',
                'niveau_id' => 'nullable|exists:niveaux,id',
                'code' => 'nullable|string|max:255',
                'libelle' => 'nullable|string|max:255',
                'ligne_recette' => 'nullable|string|max:255',
                'unite_facturation' => 'nullable|string|max:255',
                'quantite' => 'nullable|numeric|min:0',
                'montant' => 'nullable|numeric|min:0',
                'date_debut_exigibilite' => 'nullable|date',
                'date_fin_exigibilite' => 'nullable|date',
                'compte_comptable' => 'nullable|string|max:255',
                'etat' => 'required|in:actif,inactif',
            ]);

            $validated['modification_username'] = auth()->user()?->name ?? 'system';

            $facturation->update($validated);

            return redirect()->route('finances.facturation-apprenants.index')
                ->with('message', trans('common.record_updated'));
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@update', $e);
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $facturation = FacturationApprenant::findOrFail($id);
            $facturation->delete();

            return back()->with('message', trans('common.record_deleted'));
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@destroy', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }

    public function statut($id)
    {
        try {
            $facturation = FacturationApprenant::findOrFail($id);
            $facturation->etat = $facturation->etat === 'actif' ? 'inactif' : 'actif';
            $facturation->modification_username = auth()->user()?->name ?? 'system';
            $facturation->save();

            return redirect()->route('finances.facturation-apprenants.index')
                ->with('message', trans('common.status_updated'));
        } catch (\Exception $e) {
            log_error('FacturationApprenantController@statut', $e);
            return back()->withErrors(['error' => 'Une erreur est survenue']);
        }
    }
}
