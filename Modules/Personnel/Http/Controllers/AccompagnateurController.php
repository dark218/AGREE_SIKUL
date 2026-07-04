<?php

namespace Modules\Personnel\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Personnel\Entities\Accompagnateur;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Campus;

class AccompagnateurController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:accompagnateurs-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:accompagnateurs-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:accompagnateurs-edit', ['only' => ['edit', 'update', 'statut']]);
        $this->middleware('permission.check:accompagnateurs-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): Response
    {
        try {
            $query = Accompagnateur::with([
                'ecole',
                'institution',
                'campus',
                'apprenants:id,nom,prenoms,matricule',
            ])->whereNull('deleted_at');

            // Filter by search (accompagnant names)
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('accompagnant1_nom', 'like', "%{$search}%")
                      ->orWhere('accompagnant1_prenoms', 'like', "%{$search}%")
                      ->orWhere('accompagnant2_nom', 'like', "%{$search}%")
                      ->orWhere('accompagnant2_prenoms', 'like', "%{$search}%")
                      ->orWhere('accompagnant3_nom', 'like', "%{$search}%")
                      ->orWhere('accompagnant3_prenoms', 'like', "%{$search}%");
                });
            }

            // Filter by school
            if ($request->filled('ecole_id')) {
                $query->where('ecole_id', $request->ecole_id);
            }

            // Filter by status
            if ($request->filled('etat')) {
                $query->where('etat', $request->etat);
            }

            $accompagnateurs = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();

            return Inertia::render('Personnel/Accompagnateurs/Index', [
                'accompagnateurs' => $accompagnateurs,
                'ecoles' => $ecoles,
                'filters' => $request->only(['search', 'ecole_id', 'etat']),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::index", $th->getMessage());
            return Inertia::render('Personnel/Accompagnateurs/Index', [
                'accompagnateurs' => [],
                'ecoles' => [],
                'filters' => [],
            ]);
        }
    }

    public function create()
    {
        try {
            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            // Apprenants avec école pour le composant multi-select
            $apprenants = \Modules\Academique\Entities\Apprenant::whereNull('deleted_at')
                ->get([
                    'id', 'nom', 'prenoms', 'matricule', 'ecole_id',
                    'nom_pere', 'nom_mere', 'nom_tuteur', 'nom_responsable_legal',
                    'telephone', 'email',
                ])
                ->map(fn($a) => [
                    'id' => $a->id,
                    'libelle' => trim(($a->prenoms ?? '') . ' ' . ($a->nom ?? '')) . ' (' . ($a->matricule ?? '') . ')',
                    'ecole_id' => $a->ecole_id,
                    'nom_pere' => $a->nom_pere,
                    // Le patch civilites est renvoyé par une clé séparée, ce champ reste intact.
                    'nom_mere' => $a->nom_mere,
                    'nom_tuteur' => $a->nom_tuteur,
                    'nom_responsable_legal' => $a->nom_responsable_legal,
                    'telephone' => $a->telephone,
                    'email' => $a->email,
                ])->values();

            return Inertia::render('Personnel/Accompagnateurs/Create', [
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
                'apprenants' => $apprenants,
                'civilites' => \Modules\Parametrage\Entities\Civilite::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::create", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // School Information
                'ecole_id' => 'nullable|exists:ecoles,id',
                'institution_id' => 'nullable|exists:institutions,id',
                'campus_id' => 'nullable|exists:campuses,id',

                // Accompagnant 1
                'accompagnant1_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant1_nom' => 'nullable|string|max:255',
                'accompagnant1_prenoms' => 'nullable|string|max:255',
                'accompagnant1_nom_complet' => 'nullable|string|max:255',
                'accompagnant1_lien' => 'nullable|string|max:255',
                'accompagnant1_photo' => 'nullable|image|max:2048',
                'accompagnant1_photo_id' => 'nullable',

                // Accompagnant 2
                'accompagnant2_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant2_nom' => 'nullable|string|max:255',
                'accompagnant2_prenoms' => 'nullable|string|max:255',
                'accompagnant2_nom_complet' => 'nullable|string|max:255',
                'accompagnant2_lien' => 'nullable|string|max:255',
                'accompagnant2_photo' => 'nullable|image|max:2048',
                'accompagnant2_photo_id' => 'nullable',

                // Accompagnant 3
                'accompagnant3_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant3_nom' => 'nullable|string|max:255',
                'accompagnant3_prenoms' => 'nullable|string|max:255',
                'accompagnant3_nom_complet' => 'nullable|string|max:255',
                'accompagnant3_lien' => 'nullable|string|max:255',
                'accompagnant3_photo' => 'nullable|image|max:2048',
                'accompagnant3_photo_id' => 'nullable',

                // Apprenants rattachés (multi)
                'apprenant_ids' => ['nullable', 'array'],
                'apprenant_ids.*' => ['integer', 'exists:apprenants,id'],

                // Audit
                'etat' => 'required|in:actif,inactif',
            ]);

            // Règle métier : tous les apprenants dans la même école
            $apprenantIds = array_values(array_filter($request->input('apprenant_ids', [])));
            (new \App\Rules\SameSchoolForApprenants())->validate('apprenant_ids', $apprenantIds, function ($msg) {
                throw \Illuminate\Validation\ValidationException::withMessages(['apprenant_ids' => $msg]);
            });

            // Upload des photos
            for ($i = 1; $i <= 3; $i++) {
                $field = "accompagnant{$i}_photo";
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $file = $request->file($field);
                    $fileName = time() . '-acc' . $i . '-' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $destPath = public_path('images');
                    if (!file_exists($destPath)) mkdir($destPath, 0755, true);
                    copy($file->getRealPath(), $destPath . '/' . $fileName);

                    $fileId = \DB::table('fichiers')->insertGetId([
                        'code' => 'ACC-' . time() . '-' . $i,
                        'libelle' => $fileName,
                        'chemin_fichier' => 'images/' . $fileName,
                        'type_fichier' => $file->getClientOriginalExtension(),
                        'etat' => 'actif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $validated["{$field}_id"] = $fileId;
                }
                unset($validated[$field]);
            }

            // Retirer apprenant_ids du $validated (pas un champ de la table)
            unset($validated['apprenant_ids']);

            $accompagnateur = \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $apprenantIds) {
                // Auto-création du User associé — identité prise sur accompagnant1
                $validated['user_id'] = \App\Services\AutoUserCreator::forProfile([
                    'nom'     => $validated['accompagnant1_nom'] ?? 'Accompagnateur',
                    'prenoms' => $validated['accompagnant1_prenoms'] ?? null,
                    'role'    => 'accompagnateur',
                ]);

                $accompagnateur = Accompagnateur::create($validated);

                $sync = [];
                foreach ($apprenantIds as $i => $aid) {
                    $sync[$aid] = ['est_principal' => $i === 0];
                }
                if (!empty($sync)) {
                    $accompagnateur->apprenants()->sync($sync);
                }
                return $accompagnateur;
            });

            return redirect()->route('accompagnateurs.index')
                ->with('success', __('messages.created'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::store", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function show($id)
    {
        try {
            $accompagnateur = Accompagnateur::with([
                'ecole',
                'institution',
                'campus',
                'accompagnant1Photo',
                'accompagnant2Photo',
                'accompagnant3Photo',
                'apprenants:id,nom,prenoms,matricule',
            ])->findOrFail($id);

            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            // Transformer les photos en URLs
            $data = $accompagnateur->toArray();
            for ($i = 1; $i <= 3; $i++) {
                $photo = $accompagnateur->{"accompagnant{$i}Photo"};
                $data["accompagnant{$i}_photo_url"] = $photo ? asset($photo->chemin_fichier) : null;
            }

            return Inertia::render('Personnel/Accompagnateurs/Show', [
                'accompagnateur' => $data,
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::show", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function edit($id)
    {
        try {
            $accompagnateur = Accompagnateur::with([
                'ecole',
                'institution',
                'campus',
                'accompagnant1Photo',
                'accompagnant2Photo',
                'accompagnant3Photo',
                'apprenants:id',
            ])->findOrFail($id);

            $ecoles = Ecole::select('id', 'nom as libelle')->orderBy('nom')->get();
            $institutions = Institution::select('id', 'nom as libelle')->orderBy('nom')->get();
            $campuses = Campus::select('id', 'nom as libelle')->orderBy('nom')->get();

            $apprenants = \Modules\Academique\Entities\Apprenant::whereNull('deleted_at')
                ->get([
                    'id', 'nom', 'prenoms', 'matricule', 'ecole_id',
                    'nom_pere', 'nom_mere', 'nom_tuteur', 'nom_responsable_legal',
                    'telephone', 'email',
                ])
                ->map(fn($a) => [
                    'id' => $a->id,
                    'libelle' => trim(($a->prenoms ?? '') . ' ' . ($a->nom ?? '')) . ' (' . ($a->matricule ?? '') . ')',
                    'ecole_id' => $a->ecole_id,
                    'nom_pere' => $a->nom_pere,
                    // Le patch civilites est renvoyé par une clé séparée, ce champ reste intact.
                    'nom_mere' => $a->nom_mere,
                    'nom_tuteur' => $a->nom_tuteur,
                    'nom_responsable_legal' => $a->nom_responsable_legal,
                    'telephone' => $a->telephone,
                    'email' => $a->email,
                ])->values();

            $editData = $accompagnateur->toArray();
            for ($i = 1; $i <= 3; $i++) {
                $photo = $accompagnateur->{"accompagnant{$i}Photo"};
                $editData["accompagnant{$i}_photo_url"] = $photo ? asset($photo->chemin_fichier) : null;
            }
            $editData['apprenant_ids'] = $accompagnateur->apprenants->pluck('id')->all();

            return Inertia::render('Personnel/Accompagnateurs/Edit', [
                'accompagnateur' => $editData,
                'ecoles' => $ecoles,
                'institutions' => $institutions,
                'campuses' => $campuses,
                'apprenants' => $apprenants,
                'civilites' => \Modules\Parametrage\Entities\Civilite::actif()->orderBy('ordre')->get(['id', 'code', 'libelle'])->toArray(),
            ]);
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::edit", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function update($id, Request $request)
    {
        try {
            $accompagnateur = Accompagnateur::findOrFail($id);

            $validated = $request->validate([
                // School Information
                'ecole_id' => 'nullable|exists:ecoles,id',
                'institution_id' => 'nullable|exists:institutions,id',
                'campus_id' => 'nullable|exists:campuses,id',

                // Accompagnant 1
                'accompagnant1_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant1_nom' => 'nullable|string|max:255',
                'accompagnant1_prenoms' => 'nullable|string|max:255',
                'accompagnant1_nom_complet' => 'nullable|string|max:255',
                'accompagnant1_lien' => 'nullable|string|max:255',
                'accompagnant1_photo' => 'nullable|image|max:2048',
                'accompagnant1_photo_id' => 'nullable',

                // Accompagnant 2
                'accompagnant2_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant2_nom' => 'nullable|string|max:255',
                'accompagnant2_prenoms' => 'nullable|string|max:255',
                'accompagnant2_nom_complet' => 'nullable|string|max:255',
                'accompagnant2_lien' => 'nullable|string|max:255',
                'accompagnant2_photo' => 'nullable|image|max:2048',
                'accompagnant2_photo_id' => 'nullable',

                // Accompagnant 3
                'accompagnant3_civilite' => 'nullable|in:mr,mme,mlle',
                'accompagnant3_nom' => 'nullable|string|max:255',
                'accompagnant3_prenoms' => 'nullable|string|max:255',
                'accompagnant3_nom_complet' => 'nullable|string|max:255',
                'accompagnant3_lien' => 'nullable|string|max:255',
                'accompagnant3_photo' => 'nullable|image|max:2048',
                'accompagnant3_photo_id' => 'nullable',

                // Apprenants rattachés (multi)
                'apprenant_ids' => ['nullable', 'array'],
                'apprenant_ids.*' => ['integer', 'exists:apprenants,id'],

                // Audit
                'etat' => 'required|in:actif,inactif',
            ]);

            // Règle métier : tous les apprenants dans la même école
            $apprenantIds = array_values(array_filter($request->input('apprenant_ids', [])));
            (new \App\Rules\SameSchoolForApprenants())->validate('apprenant_ids', $apprenantIds, function ($msg) {
                throw \Illuminate\Validation\ValidationException::withMessages(['apprenant_ids' => $msg]);
            });

            // Upload des photos
            for ($i = 1; $i <= 3; $i++) {
                $field = "accompagnant{$i}_photo";
                if ($request->hasFile($field) && $request->file($field)->isValid()) {
                    $file = $request->file($field);
                    $fileName = time() . '-acc' . $i . '-' . \Illuminate\Support\Str::random(8) . '.' . $file->getClientOriginalExtension();
                    $destPath = public_path('images');
                    if (!file_exists($destPath)) mkdir($destPath, 0755, true);
                    copy($file->getRealPath(), $destPath . '/' . $fileName);

                    $fileId = \DB::table('fichiers')->insertGetId([
                        'code' => 'ACC-' . time() . '-' . $i,
                        'libelle' => $fileName,
                        'chemin_fichier' => 'images/' . $fileName,
                        'type_fichier' => $file->getClientOriginalExtension(),
                        'etat' => 'actif',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $validated["{$field}_id"] = $fileId;
                }
                unset($validated[$field]);
            }

            unset($validated['apprenant_ids']);

            \Illuminate\Support\Facades\DB::transaction(function () use ($accompagnateur, $validated, $apprenantIds) {
                $accompagnateur->update($validated);

                $sync = [];
                foreach ($apprenantIds as $i => $aid) {
                    $sync[$aid] = ['est_principal' => $i === 0];
                }
                $accompagnateur->apprenants()->sync($sync);
            });

            return redirect()->route('accompagnateurs.index')
                ->with('success', __('messages.updated'));
        } catch (\Illuminate\Validation\ValidationException $ve) {
            throw $ve;
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::update", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function destroy($id)
    {
        try {
            $accompagnateur = Accompagnateur::findOrFail($id);
            $accompagnateur->delete();

            return redirect()->route('accompagnateurs.index')
                ->with('success', __('messages.deleted'));
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::destroy", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }

    public function statut($id)
    {
        try {
            $accompagnateur = Accompagnateur::findOrFail($id);
            $accompagnateur->etat = $accompagnateur->etat === 'actif' ? 'inactif' : 'actif';
            $accompagnateur->save();

            return redirect()->route('accompagnateurs.index')
                ->with('success', __('messages.updated'));
        } catch (\Throwable $th) {
            log_error("Personnel", "AccompagnateurController::statut", $th->getMessage());
            return back()->withErrors(['_error' => $th->getMessage()]);
        }
    }
}
