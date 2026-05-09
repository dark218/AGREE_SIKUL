<?php

namespace Modules\Business\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Marchand;
use Modules\Business\Entities\PointVente;
use Modules\Business\Entities\Terminal;

class TerminalController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:terminal-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:terminal-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:terminal-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:terminal-statut', ['only' => ['statut']]);
        $this->middleware('permission.check:terminal-validate', ['only' => ['validation']]);

    }

    public function index(Request $request)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $typeTerminal = $request->input('type_terminal');
            $statut = $request->input('statut');
            $pointVenteId = $request->input('points_vente_id');
            $raison_sociale = $request->input('raison_sociale');
            $numeroSerie = $request->input('numero_serie');

            $query = Terminal::with(['pointsVente', 'marchand'])
                ->orderBy('created_at', 'DESC');

            if (!empty($typeTerminal)) {
                $query->where('type_terminal', $typeTerminal);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            if (!empty($pointVenteId)) {
                $query->where('points_vente_id', $pointVenteId);
            }

            if (!empty($marchandId)) {
                $query->where('marchand_id', $marchandId);
            }
            if (!empty($raison_sociale)) {
                $query->whereHas('marchand', function ($q) use ($raison_sociale) {
                    $q->where('raison_sociale', 'LIKE', "%{$raison_sociale}%");
                });
            }

            if (!empty($numeroSerie)) {
                $query->where('numero_serie', 'LIKE', "%{$numeroSerie}%");
            }

            $terminaux = $query->paginate(10)->withQueryString();

            $terminaux->getCollection()->transform(function ($terminal) {
                return [
                    'id' => $terminal->id,
                    'uuid' => $terminal->uuid,
                    'type_terminal' => $terminal->type_terminal,
                    'type_terminal_label' => $this->getTypeTerminalLabel($terminal->type_terminal),
                    'fabricant' => $terminal->fabricant,
                    'modele' => $terminal->modele,
                    'numero_serie' => $terminal->numero_serie,
                    'statut' => $terminal->statut,
                    'statut_label' => $this->getStatusLabel($terminal->statut),
                    'point_vente' => $terminal->pointsVente ? $terminal->pointsVente->nom : null,
                    'marchand' => $terminal->marchand ? $terminal->marchand->raison_sociale : null,
                    'last_checkin' => $terminal->last_checkin?->format('d/m/Y H:i'),
                    'created_at' => $terminal->created_at?->format('d/m/Y H:i'),
                ];
            });

            // Récupérer les listes pour les filtres
            $pointsVente = PointVente::where('statut', config('appconstants.pointvente_statut.actif'))
                ->orderBy('nom')
                ->get()
                ->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'label' => $pv->nom
                    ];
                });

            $marchandQuery = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->orderBy('raison_sociale');

            if (!is_null($paysCurrent)) {
                $marchandQuery->where('users.pays_id', $paysCurrent);
            }

            $marchands = $marchandQuery->get()->map(function ($m) {
                return ['id' => $m->id, 'label' => $m->raison_sociale];
            });

            $typesTerminaux = [
                ['value' => 'pos', 'label' => 'Terminal de paiement'],
                ['value' => 'mobile', 'label' => 'Application mobile'],
                ['value' => 'kiosk', 'label' => 'Kiosque'],
                ['value' => 'web', 'label' => 'Interface web'],
                ['value' => 'api', 'label' => 'API'],
            ];

            $statuts = [
                ['value' => 'deploye', 'label' => 'Déployé'],
                ['value' => 'actif', 'label' => 'Actif'],
                ['value' => 'suspendu', 'label' => 'Suspendu'],
                ['value' => 'retire', 'label' => 'Retiré'],
            ];

            return Inertia::render('Business::Terminaux/Index', [
                'terminaux' => $terminaux,
                'pointsVente' => $pointsVente,
                'marchands' => $marchands,
                'typesTerminaux' => $typesTerminaux,
                'statuts' => $statuts,
                'filters' => [
                    'type_terminal' => $typeTerminal,
                    'statut' => $statut,
                    'points_vente_id' => $pointVenteId,
                    'raison_sociale' => $raison_sociale,
                    'numero_serie' => $numeroSerie,
                ],
            ]);
        } catch (\Throwable $e) {
            log_error("Terminal", "index", $e->getMessage());
            return redirect()->route('home')->with('error', 'Une erreur est survenue lors du chargement des terminaux.');
        }
    }

    public function create(Request $request)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $pointsVente = PointVente::where('statut', config('appconstants.pointvente_statut.actif'))
                ->orderBy('nom')
                ->get()
                ->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'label' => $pv->nom
                    ];
                });

            $marchandQuery = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->orderBy('raison_sociale');

            if (!is_null($paysCurrent)) {
                $marchandQuery->where('users.pays_id', $paysCurrent);
            }

            $marchands = $marchandQuery->get()->map(function ($m) {
                return ['id' => $m->id, 'label' => $m->raison_sociale];
            });
            $typesTerminaux = [
                ['value' => 'pos', 'label' => 'Terminal de paiement'],
                ['value' => 'mobile', 'label' => 'Application mobile'],
                ['value' => 'kiosk', 'label' => 'Kiosque'],
                ['value' => 'web', 'label' => 'Interface web'],
                ['value' => 'api', 'label' => 'API'],
            ];

            $statuts = [
                ['value' => 'deploye', 'label' => 'Déployé'],
                ['value' => 'actif', 'label' => 'Actif'],
                ['value' => 'suspendu', 'label' => 'Suspendu'],
                ['value' => 'retire', 'label' => 'Retiré'],
            ];

            return Inertia::render('Business::Terminaux/Create', [
                'pointsVente' => $pointsVente,
                'marchands' => $marchands,
                'typesTerminaux' => $typesTerminaux,
                'statuts' => $statuts,
            ]);
        } catch (\Throwable $e) {
            log_error("Terminal", "create", $e->getMessage());
            return redirect()->route('terminal.index')->with('error', 'Erreur lors de l\'affichage du formulaire de création.');
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'type_terminal' => 'required|in:pos,mobile,kiosk,web,api',
                'fabricant' => 'nullable|string|max:100',
                'modele' => 'nullable|string|max:100',
                'numero_serie' => 'required|string|max:255|unique:terminaux,numero_serie',
                'points_vente_id' => 'required|exists:points_vente,id',
                'marchand_id' => 'nullable|exists:marchands,id',
                'version_firmware' => 'nullable|string|max:50',
                'pki_cert_id' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            // Générer un UUID unique si non fourni
            if (empty($validatedData['uuid'])) {
                $validatedData['uuid'] = (string) Str::uuid();
            }
            $validatedData['statut'] = config('appconstants.terminaux_statut.deploye');

            $terminal = Terminal::create($validatedData);

            return redirect()
                ->route('terminal.index')
                ->with('success', 'Le terminal a été créée avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            log_error("Terminal", "store", $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la création du terminal.')
                ->withInput();
        }
    }

    public function show($id)
    {
        try {
            $terminal = Terminal::with(['pointsVente', 'marchand'])->findOrFail($id);

            return Inertia::render('Business::Terminaux/Show', [
                'terminal' => [
                    'id' => $terminal->id,
                    'uuid' => $terminal->uuid,
                    'type_terminal' => $terminal->type_terminal,
                    'type_terminal_label' => $this->getTypeTerminalLabel($terminal->type_terminal),
                    'fabricant' => $terminal->fabricant,
                    'modele' => $terminal->modele,
                    'numero_serie' => $terminal->numero_serie,
                    'statut' => $terminal->statut,
                    'statut_label' => $this->getStatusLabel($terminal->statut),
                    'point_vente' => $terminal->pointsVente ? [
                        'id' => $terminal->pointsVente->id,
                        'nom' => $terminal->pointsVente->nom,
                    ] : null,
                    'marchand' => $terminal->marchand ? [
                        'id' => $terminal->marchand->id,
                        'raison_sociale' => $terminal->marchand->raison_sociale,
                    ] : null,
                    'version_firmware' => $terminal->version_firmware,
                    'pki_cert_id' => $terminal->pki_cert_id,
                    'last_checkin' => $terminal->last_checkin?->format('d/m/Y H:i'),
                    'metadata' => $terminal->metadata,
                    'created_at' => $terminal->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $terminal->updated_at?->format('d/m/Y H:i'),
                ]
            ]);
        } catch (\Throwable $e) {
            log_error("Terminal", "show", $e->getMessage());
            return redirect()->route('terminal.index')->with('error', 'Terminal non trouvé.');
        }
    }

    public function edit($id)
    {
        try {
            $paysCurrent = auth()->user()->pays_id;
            $terminal = Terminal::findOrFail($id);

            $pointsVente = PointVente::where('statut', config('appconstants.pointvente_statut.actif'))
                ->orderBy('nom')
                ->get()
                ->map(function ($pv) {
                    return [
                        'id' => $pv->id,
                        'label' => $pv->nom
                    ];
                });

            $marchandQuery = Marchand::select('marchands.id', 'raison_sociale')
                ->join('users', 'marchands.proprietaire_id', '=', 'users.id')
                ->where('users.statut', config('appconstants.user_statut.actif'))
                ->orderBy('raison_sociale');

            if (!is_null($paysCurrent)) {
                $marchandQuery->where('users.pays_id', $paysCurrent);
            }

            $marchands = $marchandQuery->get()->map(function ($m) {
                return ['id' => $m->id, 'label' => $m->raison_sociale];
            });

            $typesTerminaux = [
                ['value' => 'pos', 'label' => 'Terminal de paiement'],
                ['value' => 'mobile', 'label' => 'Application mobile'],
                ['value' => 'kiosk', 'label' => 'Kiosque'],
                ['value' => 'web', 'label' => 'Interface web'],
                ['value' => 'api', 'label' => 'API'],
            ];

            $statuts = [
                ['value' => 'deploye', 'label' => 'Déployé'],
                ['value' => 'actif', 'label' => 'Actif'],
                ['value' => 'suspendu', 'label' => 'Suspendu'],
                ['value' => 'retire', 'label' => 'Retiré'],
            ];

            return Inertia::render('Business::Terminaux/Edit', [
                'terminal' => [
                    'id' => $terminal->id,
                    'type_terminal' => $terminal->type_terminal,
                    'fabricant' => $terminal->fabricant,
                    'modele' => $terminal->modele,
                    'numero_serie' => $terminal->numero_serie,
                    'statut' => $terminal->statut,
                    'points_vente_id' => $terminal->points_vente_id,
                    'marchand_id' => $terminal->marchand_id,
                    'version_firmware' => $terminal->version_firmware,
                    'pki_cert_id' => $terminal->pki_cert_id,
                    'metadata' => $terminal->metadata,
                ],
                'pointsVente' => $pointsVente,
                'marchands' => $marchands,
                'typesTerminaux' => $typesTerminaux,
                'statuts' => $statuts,
            ]);
        } catch (\Throwable $e) {
            log_error("Terminal", "edit", $e->getMessage());
            return redirect()->route('terminal.index')->with('error', 'Terminal non trouvé.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $terminal = Terminal::findOrFail($id);

            $validatedData = $request->validate([
                'type_terminal' => 'required|in:pos,mobile,kiosk,web,api',
                'fabricant' => 'nullable|string|max:100',
                'modele' => 'nullable|string|max:100',
                'numero_serie' => 'required|string|max:255|unique:terminaux,numero_serie,' . $id,
                'statut' => 'required|in:deploye,actif,suspendu,retire',
                'points_vente_id' => 'required|exists:points_vente,id',
                'marchand_id' => 'nullable|exists:marchands,id',
                'version_firmware' => 'nullable|string|max:50',
                'pki_cert_id' => 'nullable|string|max:255',
                'metadata' => 'nullable|array',
            ]);

            $terminal->update($validatedData);

            return redirect()
                ->route('terminal.index')
                ->with('success', 'Le terminal a été mis à jour avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Throwable $e) {
            log_error("Terminal", "update", $e->getMessage());
            return redirect()
                ->back()
                ->with('error', 'Une erreur est survenue lors de la mise à jour du terminal.')
                ->withInput();
        }
    }

    public function valider(Request $request, $id)
    {
        try {
            $terminal = Terminal::findOrFail($id);

            $terminal->update([
                'statut' => config('appconstants.terminaux_statut.actif'),
                'validated_by' => auth()->user()->id,
                'validated_at' => now(),
            ]);
           return redirect()->route('terminal.index')->with('success', 'Le terminal a été valider avec succès.');
        } catch (\Throwable $e) {
            log_error("Terminal", "valider", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }
    public function suspendre(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $terminal = Terminal::findOrFail($id);


            if (empty($motif)) {
                return redirect()->route('terminal.index')->with('error', 'Le motif est obligatoire.');
            }
            $terminal->update([
                'motif' => $motif,
                'statut' => config('appconstants.terminaux_statut.suspendu'),
                'suspended_by' => auth()->user()->id,
                'suspended_at' => now(),
            ]);
           return redirect()->route('terminal.index')->with('success', 'Le terminal a été suspendu avec succès.');
        } catch (\Throwable $e) {
            log_error("Terminal", "suspendre", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }
    public function retirer(Request $request, $id)
    {
        try {
            $motif = $request->input('motif');
            $terminal = Terminal::findOrFail($id);


            if (empty($motif)) {
                return redirect()->route('terminal.index')->with('error', 'Le motif est obligatoire.');
            }
            $terminal->update([
                'motif' => $motif,
                'statut' => config('appconstants.terminaux_statut.retire'),
                'retired_by' => auth()->user()->id,
                'retired_at' => now(),
            ]);
           return redirect()->route('terminal.index')->with('success', 'Le terminal a été rétiré avec succès.');
        } catch (\Throwable $e) {
            log_error("Terminal", "retirer", $e->getMessage());
            return redirect()->route('marchand.show', $id)->with('error', trans('Erreur'));
        }
    }
    public function statut($id)
    {
        try {
            $terminal
                = Terminal::withTrashed()->findOrFail($id);

            if ($terminal
                ->trashed()) {
                $terminal
                    ->restore();
                $message = trans('restaurationsucces');
            } else {
                $terminal
                    ->delete();
                $message = trans('suppressionsucces');
            }

            return redirect()->route('terminal.index')->with('success', $message);

        } catch (\Throwable $e) {
            log_error("Terminal", "statut", $e->getMessage());
            return redirect()->route('terminal.index')->with('error', trans('Erreur'));

        }
    }


    /**
     * Retourne le libellé du type de terminal
     */
    protected function getTypeTerminalLabel($type): ?string
    {
        if (!$type) {
            return null;
        }

        $types = [
            'pos' => 'Terminal de paiement',
            'mobile' => 'Application mobile',
            'kiosk' => 'Kiosque',
            'web' => 'Interface web',
            'api' => 'API',
        ];

        return $types[$type] ?? $type;
    }

    /**
     * Retourne le libellé du statut
     */
    protected function getStatusLabel($status): ?string
    {
        if (!$status) {
            return null;
        }

        $statuts = [
            'deploye' => 'Déployé',
            'actif' => 'Actif',
            'suspendu' => 'Suspendu',
            'retire' => 'Retiré',
        ];

        return $statuts[$status] ?? $status;
    }
}
