<?php

namespace Modules\Pos\Http\Controllers;

use App\Services\Generator;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Modules\Administration\Http\Controllers\ErrorLogController;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\Business\Entities\Terminal;
use Modules\Parametrage\Entities\Pays;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;
use Modules\GestionStock\Entities\TransfertStock;
use Nette\Schema\ValidationException;

class SessionCaisseManagerController extends Controller
{
    public function __construct()
    {
         $this->middleware('permission.check:session-caisse-manager-list', ['only' => ['index', 'show']]);
         $this->middleware('permission.check:session-caisse-manager-create', ['only' => ['create', 'attribution']]);
         $this->middleware('permission.check:session-caisse-manager-fermerture', ['only' => ['fermerture']]);
    }

    public function index(Request $request)
    {
        try {
            $caisseId = $request->input('caisse_id');
            $caissierId = $request->input('caissier_id');
            $terminalId = $request->input('terminal_id');
            $statut = $request->input('statut');
            $user = auth()->user();
            if(!$user->hasRole(config('appconstants.role.manager'))){
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();
            if (!$employeManager || !$employeManager->points_vente_id) {
                return Inertia::render('Pos::SessionCaisseManager/Index', [
                    'sessionsCaisse' => [],
                    'statuts' => $this->getStatuts(),
                    'employes' => [],
                    'terminaux' => [],
                    'caisses' => [],
                    'filters' => [],
                    'error' => trans('manager_feature'),
                ]);
            }

            $pointVenteId = $employeManager->points_vente_id;
            $caisses = Caisse::select('id', 'code','nom')
                ->where('points_vente_id', $pointVenteId)
                ->orderBy('nom')
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->code . ' - ' . $pv->nom]);
            $terminaux = Terminal::select('id', 'numero_serie')
                ->where('statut', config('appconstants.terminaux_statut.actif'))
                ->orderBy('numero_serie')
                ->get()
                 ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->numero_serie]);
            $caissiers = Employe::with(['user:id,nom,prenoms'])
                ->withCount([
                    'sessionsCaisse as session_active' => function ($query) {
                        $query->where('statut', config('appconstants.session_caisse_statut.ouverte'));
                    }
                ])
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->where('points_vente_id', $pointVenteId)
                ->whereHas('user', function ($query) {
                    $query->where('statut', config('appconstants.user_statut.actif'));
                })
                ->get()
                ->map(function ($employe) {
                    $status = $employe->session_active > 0 ? '(En session)' : '(Disponible)';

                    return [
                        'value' => $employe->id,
                        'label' => $employe->user?->prenoms . ' ' . $employe->user?->nom . ' ' . $status,
                    ];
                });

            $query = SessionCaisse::with(['caisse', 'caissier.user', 'terminal'])
                ->whereHas('caissier', function ($q) use ($pointVenteId) {
                    $q->where('points_vente_id', $pointVenteId);
                })
                ->orderBy('created_at', 'DESC');


            if (!empty($caisseId)) {
                $query->where('caisse_id', $caisseId);
            }
            if (!empty($caissierId)) {
                $query->where('caissier_id', $caissierId);
            }
            if (!empty($terminalId)) {
                $query->where('terminal_id', $terminalId);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            $sessioncaisses = $query->paginate(10)->withQueryString();
            $sessioncaisses->getCollection()->transform(function ($session) {
                return [
                    'id' => $session->id,
                    'reference' => $session->reference,
                    'caisse' => ($session->caisse->code ?? '') . ' - ' . ($session->caisse->nom ?? ''),
                    'caissier_nom' => $session->caissier?->user?->nom . ' ' . $session->caissier?->user?->prenoms ?? '',
                    'terminal_numero' => $session->terminal?->numero_serie ?? '',
                    'statut' => $session->statut,
                    'statut_label' => collect($this->getStatuts())->firstWhere('value', $session->statut)['label'] ?? $session->statut,
                    'created_at' => $session->created_at?->format('d/m/Y H:i'),
                    'updated_at' => $session->updated_at?->format('d/m/Y H:i'),
                    // Données supplémentaires pour le modal de détails
                    'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                    'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                    'fond_ouverture' => $session->fond_ouverture_cents !== null && $session->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise))
                        : null,
                    'total_encaisse' => $session->total_encaisse_cents !== null && $session->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($session->total_encaisse_cents, $session->devise))
                        : null,
                    'total_reel' => $session->total_reel_cents ?? null,
                    'ecart' => $session->ecart_cents ?? null,
                    'devise' => $session->devise,
                ];
            });


            // ============================================
            // VENTES DU POINT DE VENTE (pour onglet Ventes)
            // ============================================
            $venteStatut = $request->input('vente_statut');
            $venteModePaiement = $request->input('vente_mode_paiement');
            $venteSessionId = $request->input('vente_session_id');

            $ventesQuery = VentePos::with(['sessionCaisse','sessionCaisse.caisse.pointVente', 'employe.user', 'lignes'])
                ->whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
                    $q->where('points_vente_id', $pointVenteId);
                })
                ->orderBy('created_at', 'DESC');

            if (!empty($venteStatut)) {
                $ventesQuery->where('statut', $venteStatut);
            }
            if (!empty($venteModePaiement)) {
                $ventesQuery->where('mode_paiement', $venteModePaiement);
            }
            if (!empty($venteSessionId)) {
                $ventesQuery->where('sessions_caisse_id', $venteSessionId);
            }

            $ventes = $ventesQuery->paginate(10, ['*'], 'ventes_page')->withQueryString();
            $ventes->getCollection()->transform(function ($vente) {
                return [
                    'id' => $vente->id,
                    'reference' => $vente->reference ?? 'N/A',
                    'point_vente' => $vente->sessionCaisse?->caisse?->pointVente?->nom ?? '',
                    'total' => $vente->total_cents !== null && $vente->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($vente->total_cents, $vente->devise))
                        : null,
                    'refund' => $vente->refund_cents !== null && $vente->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($vente->refund_cents, $vente->devise))
                        : 0,
                    'devise' => $vente->devise ?? 'XOF',
                    'mode_paiement' => $vente->mode_paiement,
                    'statut' => $vente->statut,
                    'caissier' => $vente->employe?->user?->prenoms . ' ' . $vente->employe?->user?->nom,
                    'session_id' => $vente->sessions_caisse_id,
                    'session_reference' => $vente->sessionCaisse->reference,
                    'date' => $vente->created_at?->format('d/m/Y H:i'),
                    'lignes' => $vente->lignes->map(function ($ligne) use ($vente) {
                        return [
                            'id' => $ligne->id,
                            'libelle' => $ligne->libelle ?? 'Article',
                            'quantite' => $ligne->quantite ?? 1,
                            'prix_unitaire' => (float) str_replace(' ', '', MoneyService::toDisplay($ligne->prix_unitaire_cents ?? 0, $vente->devise ?? 'XOF')),
                            'total_ligne' => (float) str_replace(' ', '', MoneyService::toDisplay($ligne->total_ligne_cents ?? 0, $vente->devise ?? 'XOF')),
                        ];
                    })->toArray(),
                ];
            });

            // Sessions pour le filtre des ventes
            $sessionsForFilter = SessionCaisse::whereHas('caissier', function ($q) use ($pointVenteId) {
                $q->where('points_vente_id', $pointVenteId);
            })
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn($s) => [
                    'value' => $s->id,
                    'label' => 'Session ' . $s->reference . ' - ' . ucfirst($s->statut),
                ]);

            // ============================================
            // STATISTIQUES DASHBOARD
            // ============================================
            $stats = $this->getDashboardStats($pointVenteId);

            return Inertia::render('Pos::SessionCaisseManager/Index', [
                'sessionsCaisse' => $sessioncaisses,
                'ventes' => $ventes,
                'stats' => $stats,
                'statuts' => $this->getStatuts(),
                'statutsVente' => $this->getStatutsVente(),
                'modePaiements' => $this->getModePaiements(),
                'sessionsForFilter' => $sessionsForFilter,
                'employes' => $caissiers,
                'terminaux' => $terminaux,
                'caisses' => $caisses,
                'filters' => [
                    'caisse_id' => $caisseId,
                    'caissier_id' => $caissierId,
                    'terminal_id' => $terminalId,
                    'statut' => $statut,
                ],
                'venteFilters' => [
                    'vente_statut' => $venteStatut,
                    'vente_mode_paiement' => $venteModePaiement,
                    'vente_session_id' => $venteSessionId,
                ],
            ]);
        } catch (\Throwable $e) {
           log_error("SessionCaisseManager", "index", $e->getMessage());
            return Inertia::render('Pos::SessionCaisseManager/Index', [
                'sessionsCaisse' => [],
                'ventes' => [],
                'stats' => [],
                'statuts' => $this->getStatuts(),
                'statutsVente' => $this->getStatutsVente(),
                'modePaiements' => $this->getModePaiements(),
                'sessionsForFilter' => [],
                'employes' => [],
                'terminaux' => [],
                'caisses' => [],
                'filters' => [],
                'venteFilters' => [],
                'error' => 'Une erreur est survenue lors du chargement des caisses.',
            ]);
        }
    }
    /**
     * Affiche le formulaire de création d'une nouvelle session de caisse
     *
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function create()
    {
        try {
            $user = auth()->user();
            if(!$user->hasRole(config('appconstants.role.manager'))){
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }
            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager || !$employeManager->points_vente_id) {
                return back()->with('error', trans('manager_feature'));
            }

            $pointVenteId = $employeManager->points_vente_id;

            // Récupérer les caisses actives
            $caisses = Caisse::select('id', 'code','nom')
                ->where('points_vente_id', $pointVenteId)
                ->orderBy('nom')
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->code . ' - ' . $pv->nom]);
            $terminaux = Terminal::select('id', 'numero_serie')
                ->where('statut', config('appconstants.terminaux_statut.actif'))
                ->orderBy('numero_serie')
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->numero_serie]);
            $caissiers = Employe::with(['user:id,nom,prenoms'])
                ->withCount([
                    'sessionsCaisse as session_active' => function ($query) {
                        $query->where('statut', config('appconstants.session_caisse_statut.ouverte'));
                    }
                ])
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->where('points_vente_id', $pointVenteId)
                ->whereHas('user', function ($query) {
                    $query->where('statut', config('appconstants.user_statut.actif'));
                })
                ->get()
                ->map(function ($employe) {
                    $status = $employe->session_active > 0 ? '(En session)' : '(Disponible)';

                    return [
                        'value' => $employe->id,
                        'label' => $employe->user?->prenoms . ' ' . $employe->user?->nom . ' ' . $status,
                    ];
                });


            return Inertia::render('Pos::SessionCaisseManager/Create', [
                'caisses' => $caisses,
                'caissiers' => $caissiers,
                'terminaux' => $terminaux,
            ]);

        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "create", $e->getMessage());
            return redirect()
                ->route('session-caisse-manager.index')
                ->with('error', trans('error_loading_form'));
        }
    }
    public function attribution(Request $request)
    {
        try {
            $user = auth()->user();
            if(!$user->hasRole(config('appconstants.role.manager'))){
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }
            $pays = Pays::find($user->pays_id);
            $paysIso = $pays->iso??"XX";
            $validatedData = $request->validate([
                'caisse_id'   => 'required|exists:caisses,id',
                'caissier_id' => 'required|exists:employes,id',
                'terminal_id' => 'nullable|exists:terminaux,id'
            ]);

            // Vérifier qu'aucune session ouverte ou en attente n'existe déjà pour cette caisse
            $sessionExistante = SessionCaisse::where('caisse_id', $validatedData['caisse_id'])
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.ouverte'),
                    config('appconstants.session_caisse_statut.attente')
                ])
                ->first();

            if ($sessionExistante) {
                return back()->with('error', trans('caisse_affect'));
            }

            // Vérifier que le caissier n'est pas déjà affecté à une autre session active
            $sessionCaissier = SessionCaisse::where('caissier_id', $validatedData['caissier_id'])
                ->whereIn('statut', [
                    config('appconstants.session_caisse_statut.ouverte'),
                    config('appconstants.session_caisse_statut.attente')
                ])
                ->first();

            if ($sessionCaissier) {
                return back()->with('error', trans('caissier_affect'));
            }
            $caissier = Employe::findOrFail($validatedData['caissier_id']);
            $pointVente =PointVente::findOrFail($caissier->points_vente_id);
            $reference = Generator::generateSessionCaisseReference($paysIso,$pointVente->id,$validatedData['caisse_id']);
            // Création de la session en statut attente
            SessionCaisse::create([
                'caisse_id' => $validatedData['caisse_id'],
                'caissier_id' => $validatedData['caissier_id'],
                'terminal_id' => $validatedData['terminal_id'] ?? null,
                'devise'=> config('appconstants.devisedefault'), //Sera changé à l ouverture
                'reference'=> $reference,
                'statut' => config('appconstants.session_caisse_statut.attente'),
            ]);
            return redirect()
                ->back()
                ->with('success', trans('succes_attribution'));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "attribution", $e->getMessage());
            return back()->with('error', trans('erreur_attribution'))->withInput();
        }
    }

    /**
     * Affiche les détails d'une session de caisse
     *
     * @param int $id
     * @return \Inertia\Response|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        try {
            $user = auth()->user();
            if(!$user->hasRole(config('appconstants.role.manager'))){
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }
            $session = SessionCaisse::with([
                'caisse',
                'user',
                'terminal'
            ])->findOrFail($id);

            return Inertia::render('Pos::SessionCaisseManager/Show', [
                'session' => [
                    'id' => $session->id,
                    'caisse_nom' =>
                        ($session->caisse->code ?? 'N/A') .
                        ' - ' .
                        ($session->caisse->nom ?? 'N/A'),
                    'caissier_nom' =>
                        $session->caissier->user->nom . ' ' .
                        $session->caissier->user->prenoms,
                    'terminal_numero' =>
                        $session->terminal->numero_serie ?? 'N/A',
                    'date_ouverture' => $session->opened_at?->format('d/m/Y H:i'),
                    'date_fermeture' => $session->closed_at?->format('d/m/Y H:i'),
                    // SAFE — PAS de formatage tant que non ouvert
                    'fond_ouverture' => $session->fond_ouverture_cents !== null && $session->devise
                        ? (float) str_replace(' ', '', MoneyService::toDisplay($session->fond_ouverture_cents, $session->devise))
                        : null,
                    'total_encaisse' => $session->total_encaisse_cents ?? 0,
                    'total_reel'     => $session->total_reel_cents ?? null,
                    'ecart'          => $session->ecart_cents ?? null,
                    'statut' => $session->statut,
                    // liste pour sélection UI
                    'devises' => $session->devise,
                ]
            ]);

        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "show", $e->getMessage());
            return redirect()
                ->route('session-caisse-manager.index')
                ->with('error', trans('error_loading_form'));
        }
    }
    public function fermerture(Request $request)
    {
        try {
            $user = auth()->user();
            // Vérifier rôle manager / admin
            $employe = Employe::where('users_id',$user->id)->where('type_employe', config('appconstants.type_employe.manager'))->first();
            if(!$employe){
                return back()->with('error', trans('manager_feature'));
            }
            // Validation
            $validatedData = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'commentaire' => 'nullable|string|max:500',
            ]);
            // Charger la session
            $session = SessionCaisse::findOrFail($validatedData['session_id']);

            if (
                !$session->caisse?->pointVente ||
                !$employe->pointVente ||
                $session->caisse->pointVente->marchand_id !== $employe->pointVente->marchand_id
            ) {
                return back()->with('error', trans('unauthorized_action'));
            }


            // Vérifier statut
            if ($session->statut !== config('appconstants.session_caisse_statut.attente_validation')) {
                return back()->with('error', trans('session_not_waiting_validation'));
            }
            // Vérifier qu’il y a bien un écart
            if ((int) $session->ecart_cents === 0) {
                return back()->with('error', trans('no_gap_to_validate'));
            }
            // Validation finale
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.fermee'),
                'validated_by' => $user->id,
                'validated_at' => now(),
                'validation_commentaire' => $validatedData['commentaire'] ?? null,
                'closed_at' => now(),
            ]);
            return redirect()
                ->back()
                ->with('success', trans('session_cloture_validated'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "fermerture", $e->getMessage());
            return back()->with('error', trans('session_validation_error'))->withInput();
        }
    }



    /**
     * Valide la cloture d'une session de caisse
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function validate($id)
    {
        try {
            $user = auth()->user();
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }

            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return back()->with('error', trans('manager_feature'));
            }

            $session = SessionCaisse::findOrFail($id);

            // Vérifier que la session appartient au point de vente du manager
            if ($session->caissier?->points_vente_id !== $employeManager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            // Vérifier le statut
            if ($session->statut !== config('appconstants.session_caisse_statut.attente_validation')) {
                return back()->with('error', trans('session_not_waiting_validation'));
            }

            // Valider la session
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.fermee'),
                'validated_by' => $user->id,
                'validated_at' => now(),
                'closed_at' => $session->closed_at ?? now(),
            ]);

            return back()->with('success', trans('session_cloture_validated'));
        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "validate", $e->getMessage());
            return back()->with('error', trans('session_validation_error'));
        }
    }

    /**
     * Annule une session de caisse
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel(Request $request, $id)
    {
        try {
            $user = auth()->user();
            if (!$user->hasRole(config('appconstants.role.manager'))) {
                return redirect()->route('home')->with('error', trans('manager_feature'));
            }

            $employeManager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$employeManager) {
                return back()->with('error', trans('manager_feature'));
            }

            $validatedData = $request->validate([
                'motif' => 'required|string|max:500',
            ]);

            $session = SessionCaisse::findOrFail($id);

            // Vérifier que la session appartient au point de vente du manager
            if ($session->caissier?->points_vente_id !== $employeManager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            // Vérifier le statut - on ne peut annuler que les sessions en attente ou attente_validation
            $statutsAnnulables = [
                config('appconstants.session_caisse_statut.attente'),
                config('appconstants.session_caisse_statut.attente_validation'),
            ];

            if (!in_array($session->statut, $statutsAnnulables)) {
                return back()->with('error', trans('session_cannot_be_cancelled'));
            }

            // Annuler la session
            $session->update([
                'statut' => config('appconstants.session_caisse_statut.annulee'),
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancel_reason' => $validatedData['motif'],
            ]);

            return back()->with('success', trans('session_cancelled'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("SessionCaisseManager", "cancel", $e->getMessage());
            return back()->with('error', trans('session_cancel_error'));
        }
    }

    protected function getStatuts(): array
    {
        return [
            ['value' => 'attente', 'label' => __('pos_session_statut_attente_non_ouverte')],
            ['value' => 'ouverte', 'label' => __('pos_session_statut_ouverte')],
            ['value' => 'attente_validation', 'label' => __('pos_session_statut_attente_validation')],
            ['value' => 'fermee', 'label' => __('pos_session_statut_fermee')],
            ['value' => 'annulee', 'label' => __('pos_session_statut_annulee')],
        ];
    }

    protected function getStatutsVente(): array
    {
        return [
            ['value' => 'en_attente', 'label' => __('pos.ventePos.statuts.en_attente')],
            ['value' => 'validee', 'label' => __('pos.ventePos.statuts.validee')],
            ['value' => 'annulee', 'label' => __('pos.ventePos.statuts.annulee')],
            ['value' => 'remboursee', 'label' => __('pos.ventePos.statuts.remboursee')],
            ['value' => 'partielle', 'label' => __('pos.ventePos.statuts.partielle')],
        ];
    }

    protected function getModePaiements(): array
    {
        return [
            ['value' => 'espece', 'label' => __('pos.ventePos.modePaiement.espece')],
            ['value' => 'electronique', 'label' => __('pos.ventePos.modePaiement.electronique')],
            ['value' => 'mixte', 'label' => __('pos.ventePos.modePaiement.mixte')],
        ];
    }

    protected function getDashboardStats(int $pointVenteId): array
    {
        // Sessions actives (ouvertes)
        $sessionsOuvertes = SessionCaisse::whereHas('caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })->where('statut', config('appconstants.session_caisse_statut.ouverte'))->count();

        // Sessions en attente de validation
        $sessionsAttenteValidation = SessionCaisse::whereHas('caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })->where('statut', config('appconstants.session_caisse_statut.attente_validation'))->count();

        // Ventes du jour
        $ventesAujourdhui = VentePos::whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })->whereDate('created_at', today())->count();

        // Chiffre d'affaires du jour (ventes validées)
        $caJour = VentePos::whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })
            ->whereDate('created_at', today())
            ->where('statut', config('appconstants.statut_vente_pos.validee'))
            ->sum('total_cents');
        $caJour_display = (float) str_replace(' ', '', MoneyService::toDisplay($caJour, 'XOF'));
        $caJourParDevise = VentePos::whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })
            ->whereDate('created_at', today())
            ->where('statut', config('appconstants.statut_vente_pos.validee'))
            ->selectRaw('devise, SUM(total_cents) as total_cents')
            ->groupBy('devise')
            ->get();

        $caJourDisplayParDevise = [];

        foreach ($caJourParDevise as $row) {
            $caJourDisplayParDevise[$row->devise] = [
                'total_cents' => (int) $row->total_cents,
                'display' => MoneyService::toDisplay(
                    (int) $row->total_cents,
                    $row->devise
                ),
            ];
        }
        // Ventes en attente de paiement
        $ventesEnAttente = VentePos::whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })->where('statut', config('appconstants.statut_vente_pos.en_attente'))->count();

        // Remboursements du jour
        $remboursementsJour = VentePos::whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
            $q->where('points_vente_id', $pointVenteId);
        })
            ->whereDate('created_at', today())
            ->whereIn('statut', [
                config('appconstants.statut_vente_pos.remboursee'),
                config('appconstants.statut_vente_pos.partielle'),
            ])
            ->count();

        // Demandes de transfert de stock en attente (reçues par ce point de vente)
        $demandesTransfertEnAttente = TransfertStock::where('emplacement_destination_id', $pointVenteId)
            ->where('statut', TransfertStock::STATUT_EN_COURS)
            ->count();

        return [
            'sessions_ouvertes' => $sessionsOuvertes,
            'sessions_attente_validation' => $sessionsAttenteValidation,
            'ventes_aujourdhui' => $ventesAujourdhui,
            'ca_jour_cents' => $caJour,
            'ca_jour_display' => $caJour_display ?? 0,
            'ca_jour_display_par_devise' => $caJourDisplayParDevise,
            'ventes_en_attente' => $ventesEnAttente,
            'remboursements_jour' => $remboursementsJour,
            'demandes_transfert_en_attente' => $demandesTransfertEnAttente,
        ];
    }

}
