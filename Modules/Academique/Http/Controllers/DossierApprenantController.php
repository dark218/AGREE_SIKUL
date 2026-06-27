<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\DossierApprenant;
use Modules\Academique\Entities\Apprenant;

class DossierApprenantController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:dossiers_apprenants-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:dossiers_apprenants-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:dossiers_apprenants-update', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:dossiers_apprenants-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        try {
            $query = DossierApprenant::query();

            // Filtres
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->whereHas('apprenant', function ($q) use ($search) {
                    $q->where('matricule', 'like', "%$search%");
                });
            }

            $dossiers = $query->with('apprenant.user', 'apprenant.classe')
                ->paginate(10)
                ->withQueryString()
                ->through(fn($dossier) => [
                    'id' => $dossier->id,
                    'apprenant_id' => $dossier->apprenant_id,
                    'extrait_naissance_id' => $dossier->extrait_naissance_id,
                    'certificat_residence_id' => $dossier->certificat_residence_id,
                    'carnet_sante_id' => $dossier->carnet_sante_id,
                    'dernier_bulletin_id' => $dossier->dernier_bulletin_id,
                    'extrait_naissance' => $dossier->extrait_naissance,
                    'certificat_residence' => $dossier->certificat_residence,
                    'carnet_sante' => $dossier->carnet_sante,
                    'dernier_bulletin' => $dossier->dernier_bulletin,
                    'apprenant' => [
                        'id' => $dossier->apprenant->id,
                        'matricule' => $dossier->apprenant->matricule,
                        'user' => [
                            'id' => $dossier->apprenant->user->id ?? null,
                            'prenoms' => $dossier->apprenant->user->prenoms ?? null,
                            'nom' => $dossier->apprenant->user->nom ?? null,
                            'email' => $dossier->apprenant->user->email ?? null,
                        ],
                        'classe' => [
                            'id' => $dossier->apprenant->classe->id ?? null,
                            'nom' => $dossier->apprenant->classe->nom ?? null,
                        ],
                    ],
                ]);

            return Inertia::render('Academique::DossiersApprenants/Index', [
                'title' => 'Dossiers Apprenants',
                'dossiersApprenants' => $dossiers,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "DossierApprenantController::index", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function create()
    {
        $apprenants = Apprenant::with('user')
            ->get(['id', 'user_id', 'matricule', 'nom', 'prenoms', 'classe_id', 'ecole_id', 'campus_id', 'niveau_id', 'section_id', 'cycle_id', 'pays_id'])
            ->map(function ($apprenant) {
                if ($apprenant->user) {
                    $name = $apprenant->user->prenoms . ' ' . $apprenant->user->nom;
                } else {
                    $name = ($apprenant->prenoms ? $apprenant->prenoms . ' ' : '') . ($apprenant->nom ?: 'Sans nom');
                }
                return [
                    'id' => $apprenant->id,
                    'libelle' => $name . ' (' . $apprenant->matricule . ')',
                    'classe_id' => $apprenant->classe_id,
                    'ecole_id' => $apprenant->ecole_id,
                    'campus_id' => $apprenant->campus_id,
                    'niveau_id' => $apprenant->niveau_id,
                    'section_id' => $apprenant->section_id,
                    'cycle_id' => $apprenant->cycle_id,
                    'pays_id' => $apprenant->pays_id,
                ];
            })->values()->toArray();

        \Log::info('DEBUG DossierApprenant::create - Apprenants:', [
            'count' => count($apprenants),
            'sample' => array_slice($apprenants, 0, 3),
        ]);

        return Inertia::render('Academique::DossiersApprenants/Create', [
            'title' => 'Nouveau Dossier Apprenant',
            'apprenants' => $apprenants,
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id|unique:dossiers_apprenants',
                'extrait_naissance' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'certificat_residence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'carnet_sante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'dernier_bulletin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            // Store files and get their paths
            $data = [
                'apprenant_id' => $validated['apprenant_id'],
            ];

            // Handle file uploads
            foreach (['extrait_naissance', 'certificat_residence', 'carnet_sante', 'dernier_bulletin'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('dossiers-apprenants', 'public');
                    $data[$field] = $path;
                }
            }

            DossierApprenant::create($data);

            return redirect()->route('academique.dossiers_apprenants.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "DossierApprenantController::store", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function show(DossierApprenant $dossier_apprenant)
    {
        try {
            $apprenants = Apprenant::with('user')
                ->get(['id', 'user_id', 'matricule', 'nom', 'prenoms'])
                ->map(function ($apprenant) {
                    // Use user name if exists, otherwise use apprenant name
                    if ($apprenant->user) {
                        $name = $apprenant->user->prenoms . ' ' . $apprenant->user->nom;
                    } else {
                        $name = ($apprenant->prenoms ? $apprenant->prenoms . ' ' : '') . ($apprenant->nom ?: 'Sans nom');
                    }
                    return [
                        'id' => $apprenant->id,
                        'libelle' => $name . ' (' . $apprenant->matricule . ')',
                    ];
                })->values()->toArray();

            $dossier_apprenant->load(['apprenant', 'extraitNaissance', 'certificatResidence', 'carnetSante', 'dernierBulletin']);

            // Manually construct the array to ensure all fields are included
            $arr = [
                'id' => $dossier_apprenant->id,
                'apprenant_id' => $dossier_apprenant->apprenant_id,
                'extrait_naissance_id' => $dossier_apprenant->extrait_naissance_id,
                'certificat_residence_id' => $dossier_apprenant->certificat_residence_id,
                'carnet_sante_id' => $dossier_apprenant->carnet_sante_id,
                'dernier_bulletin_id' => $dossier_apprenant->dernier_bulletin_id,
                'extrait_naissance' => $dossier_apprenant->extrait_naissance,
                'certificat_residence' => $dossier_apprenant->certificat_residence,
                'carnet_sante' => $dossier_apprenant->carnet_sante,
                'dernier_bulletin' => $dossier_apprenant->dernier_bulletin,
                'apprenant' => $dossier_apprenant->apprenant,
                'extraitNaissance' => $dossier_apprenant->extraitNaissance,
                'certificatResidence' => $dossier_apprenant->certificatResidence,
                'carnetSante' => $dossier_apprenant->carnetSante,
                'dernierBulletin' => $dossier_apprenant->dernierBulletin,
            ];

            return Inertia::render('Academique::DossiersApprenants/Show', [
                'title' => 'Détails du Dossier',
                'dossierApprenant' => $arr,
                'apprenants' => $apprenants,
            ]);
        } catch (\Throwable $th) {
            log_error("Academique", "DossierApprenantController::show", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function edit(DossierApprenant $dossier_apprenant)
    {
        $dossier_apprenant->load('apprenant.user');

        // Manually construct the array to ensure all fields are included
        $arr = [
            'id' => $dossier_apprenant->id,
            'apprenant_id' => $dossier_apprenant->apprenant_id,
            'extrait_naissance_id' => $dossier_apprenant->extrait_naissance_id,
            'certificat_residence_id' => $dossier_apprenant->certificat_residence_id,
            'carnet_sante_id' => $dossier_apprenant->carnet_sante_id,
            'dernier_bulletin_id' => $dossier_apprenant->dernier_bulletin_id,
            'extrait_naissance' => $dossier_apprenant->extrait_naissance,
            'certificat_residence' => $dossier_apprenant->certificat_residence,
            'carnet_sante' => $dossier_apprenant->carnet_sante,
            'dernier_bulletin' => $dossier_apprenant->dernier_bulletin,
            'apprenant' => $dossier_apprenant->apprenant,
        ];

        $apprenants = Apprenant::with('user')
            ->get(['id', 'user_id', 'matricule'])
            ->filter(fn($apprenant) => $apprenant->user !== null)
            ->map(function ($apprenant) {
                return [
                    'id' => $apprenant->id,
                    'libelle' => $apprenant->user->name . ' (' . $apprenant->matricule . ')',
                ];
            })->values()->toArray();

        return Inertia::render('Academique::DossiersApprenants/Edit', [
            'title' => 'Modifier le Dossier',
            'dossierApprenant' => $arr,
            'apprenants' => $apprenants,
        ]);
    }

    public function update(Request $request, DossierApprenant $dossier_apprenant)
    {
        try {
            $validated = $request->validate([
                'apprenant_id' => 'required|exists:apprenants,id|unique:dossiers_apprenants,apprenant_id,' . $dossier_apprenant->id,
                'extrait_naissance' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'certificat_residence' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'carnet_sante' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'dernier_bulletin' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            ]);

            // Store files and get their paths
            $data = [
                'apprenant_id' => $validated['apprenant_id'],
            ];

            // Handle file uploads
            foreach (['extrait_naissance', 'certificat_residence', 'carnet_sante', 'dernier_bulletin'] as $field) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store('dossiers-apprenants', 'public');
                    $data[$field] = $path;
                }
            }

            $dossier_apprenant->update($data);

            return redirect()->route('academique.dossiers_apprenants.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "DossierApprenantController::update", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }

    public function destroy(DossierApprenant $dossier_apprenant)
    {
        try {
            $dossier_apprenant->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Academique", "DossierApprenantController::destroy", $th->getMessage());
            return back()->withInput()->withErrors(['_error' => 'Erreur: ' . $th->getMessage()]);
        }
    }
}
