<?php

namespace Modules\GestionStock\Http\Controllers;

use App\Helpers\StockMovementHelper;
use App\Http\Controllers\Controller;
use App\Services\Generator;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Modules\GestionStock\Entities\MouvementStock;
use Modules\GestionStock\Entities\TransfertStock;
use Modules\GestionStock\Entities\TransfertStockLigne;
use Modules\Parametrage\Entities\Pays;
use Illuminate\Support\Facades\Log;

class TransfertStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:transfert-stock-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:transfert-stock-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:transfert-stock-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:transfert-stock-validate', ['only' => ['validateTransfert']]);
    }

    /**
     * Affiche la liste des transferts de stock
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $paysCurrent = $user->pays_id;

            $query = TransfertStock::with([
                'emplacementSource',
                'emplacementDestination',
                'demandePar.user',
                'confirmePar.user',
                'lignes.article',
            ])->orderBy('created_at', 'DESC');

            /**
             * =========================
             * Filtres
             * =========================
             */
            $filters = [
                'pays_id' => $request->input('pays_id'),
                'reference' => $request->input('reference'),
                'statut' => $request->input('statut'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
                'emplacement_source_id' => $request->input('emplacement_source_id'),
                'emplacement_destination_id' => $request->input('emplacement_destination_id'),
            ];
            $pointsVenteSource = collect();
            $pointsVenteDestination = collect();


            if (!empty($filters['reference'])) {
                $query->where('reference', 'LIKE', "%{$filters['reference']}%");
            }

            if (!empty($filters['statut'])) {
                $query->where('statut', $filters['statut']);
            }

            if (!empty($filters['date_debut'])) {
                $query->whereDate('date_demande', '>=', $filters['date_debut']);
            }

            if (!empty($filters['date_fin'])) {
                $query->whereDate('date_demande', '<=', $filters['date_fin']);
            }

            if (!empty($filters['emplacement_source_id'])) {
                $query->where('emplacement_source_id', $filters['emplacement_source_id']);
            }

            if (!empty($filters['emplacement_destination_id'])) {
                $query->where('emplacement_destination_id', $filters['emplacement_destination_id']);
            }

            /**
             * =========================
             * VISIBILITÉ SELON RÔLE
             * =========================
             */

            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {

                // Détermination du pays cible
                $paysId = null;

                if ($user->hasRole(config('appconstants.role.superadmin'))) {
                    // Superadmin → pays choisi dans la vue
                    $paysId = $filters['pays_id'];
                } else {
                    // Admin simple → pays du compte
                    $paysId = $paysCurrent;
                }

                if ($paysId) {
                    $pointsVenteIds = PointVente::whereHas(
                        'marchand.proprietaire',
                        fn ($q) => $q->where('pays_id', $paysId)
                    )->pluck('id');

                    $query->where(function ($q) use ($pointsVenteIds) {
                        $q->whereIn('emplacement_source_id', $pointsVenteIds)
                            ->orWhereIn('emplacement_destination_id', $pointsVenteIds);
                    });
                }
            }
            // PROPRIÉTAIRE (marchand)
            elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;

                if (!$marchand) {
                    return back()->with('error', trans('marchand_not_rattach'));
                }

                $pointsVenteIds = PointVente::where('marchand_id', $marchand->id)
                    ->pluck('id');

                $query->where(function ($q) use ($pointsVenteIds) {
                    $q->whereIn('emplacement_source_id', $pointsVenteIds)
                        ->orWhereIn('emplacement_destination_id', $pointsVenteIds);
                });
            }
            // EMPLOYÉ (manager / caissier)
            else {
                $employe = $user->employePrincipal;

                if (!$employe) {
                    return back()->with('error', trans('employe_not_rattach'));
                }

                $pointVenteId = $employe->points_vente_id;

                $query->where(function ($q) use ($pointVenteId) {
                    $q->where('emplacement_source_id', $pointVenteId)
                        ->orWhere('emplacement_destination_id', $pointVenteId);
                });
            }

            $transferts = $query->paginate(10)->withQueryString();
            /**
             * =========================
             * Données filtres UI
             * =========================
             */
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {

                $paysId = $user->hasRole(config('appconstants.role.superadmin'))
                    ? $filters['pays_id']
                    : $paysCurrent;

                if ($paysId) {
                    // Filtrer par pays sélectionné
                    $pointsVente = PointVente::whereHas(
                        'marchand.proprietaire',
                        fn ($q) => $q->where('pays_id', $paysId)
                    )
                        ->whereNull('parent_points_vente_id')
                        ->orderBy('nom')
                        ->get();
                } else {
                    // Superadmin sans pays sélectionné : charger tous les points de vente
                    $pointsVente = PointVente::whereNull('parent_points_vente_id')
                                        ->orderBy('nom')->get();
                }

                $pointsVenteSource = $pointsVente;
                $pointsVenteDestination = $pointsVente;
            } elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;

                $pointsVente = PointVente::where('marchand_id', $marchand->id)
                    ->whereNull('parent_points_vente_id')
                    ->orderBy('nom')
                    ->get();

                $pointsVenteSource = $pointsVente;
                $pointsVenteDestination = $pointsVente;
            }else {
                $employe = $user->employePrincipal;

                if (!$employe) {
                    return back()->with('error', trans('employe_not_rattach'));
                }

                $pointVenteEmploye = $employe->pointVente;

                // Pour les filtres, on montre TOUS les points de vente du marchand
                // afin de pouvoir filtrer les transferts entrants et sortants
                $pointsVente = PointVente::where(
                    'marchand_id',
                    $pointVenteEmploye->marchand_id
                )
                    ->whereNull('parent_points_vente_id')
                    ->orderBy('nom')
                    ->get();

                $pointsVenteSource = $pointsVente;
                $pointsVenteDestination = $pointsVente;
            }
            $pointsVenteSource = $pointsVenteSource->map(fn ($pv) => [
                'value' => $pv->id,
                'label' => $pv->nom,
            ]);

            $pointsVenteDestination = $pointsVenteDestination->map(fn ($pv) => [
                'value' => $pv->id,
                'label' => $pv->nom,
            ]);





            $statuts = [
                ['value' => TransfertStock::STATUT_EN_COURS, 'label' => trans('modules.business.transfertStock.statuts.en_cours')],
                ['value' => TransfertStock::STATUT_PARTIEL, 'label' => trans('modules.business.transfertStock.statuts.partiel')],
                ['value' => TransfertStock::STATUT_CONFIRME, 'label' => trans('modules.business.transfertStock.statuts.confirme')],
                ['value' => TransfertStock::STATUT_ANNULE, 'label' => trans('modules.business.transfertStock.statuts.annule')],
            ];

            // Déterminer le layout à utiliser
            $useDashboardLayout = $user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
                config('appconstants.role.marchand'),
            ]);

            return Inertia::render('GestionStock::Transferts/Index', [
                'transferts' => $transferts,
                'pays' => Pays::select('id', 'libelle')->get(),
                'paysCurrent' => $paysCurrent,
                'filters' => $filters,
                'pointsVenteSource' => $pointsVenteSource,
                'pointsVenteDestination' => $pointsVenteDestination,
                'statuts' => $statuts,
                'useDashboardLayout' => $useDashboardLayout,
                'pageTitle' => trans('modules.business.transfertStock.title'),
            ]);


        } catch (\Throwable $e) {
            log_error('TransfertStock', 'index', $e->getMessage());
            return redirect()
                ->route('home')
                ->with('error', 'Erreur lors du chargement des transferts de stock');
        }
    }


    /**
     * Affiche le formulaire de création d'un transfert
     *
     * WORKFLOW: Demande de réapprovisionnement
     * - Le manager demande des articles DEPUIS un autre point de vente
     * - SOURCE = point de vente qui A le stock (à sélectionner)
     * - DESTINATION = point de vente du demandeur (fixe)
     */
    public function create()
    {
        try {
            $user = auth()->user();

            /**
             * =========================
             * CONTRÔLE D'ACCÈS STRICT
             * =========================
             */
            // CAS 1 : MARCHAND (PROPRIÉTAIRE)
            if ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;

                if (!$marchand) {
                    return back()->with('error', trans('marchand_not_rattach'));
                }

                // Source = SES points de vente (tous sélectionnables)
                $pointsVenteSource = PointVente::where('marchand_id', $marchand->id)
                    ->where('statut', config('appconstants.pointvente_statut.actif'))
                    ->whereNull('parent_points_vente_id')
                    ->orderBy('nom')
                    ->get();

                // Destination = SES points de vente (tous sélectionnables)
                $pointsVenteDestination = $pointsVenteSource;

                // Point de vente destination par défaut (premier de la liste ou null)
                $defaultDestination = null;
            }

            // CAS 2 : EMPLOYÉ NON CAISSIER (manager, superviseur…)
            elseif ($user->employePrincipal && $user->employePrincipal->type_employe !== config('appconstants.type_employe.caissier')) {

                $employe = $user->employePrincipal;

                // SOURC = SON point de vente (fixe - celui qui REÇOIT)
                $pointsVenteSource = PointVente::where('id', $employe->points_vente_id)
                    ->whereNull('parent_points_vente_id')
                    ->where('statut', config('appconstants.pointvente_statut.actif'))
                    ->get();

                // DESTINATION = autres points de vente du MÊME marchand (à sélectionner - celui qui ENVOIE)
                $pointsVenteDestination = PointVente::where('marchand_id', $employe->pointVente->marchand_id)
                    ->whereNull('parent_points_vente_id')
                    ->where('statut', config('appconstants.pointvente_statut.actif'))
                    ->where('id', '!=', $employe->points_vente_id) // exclusion du PV destination
                    ->orderBy('nom')
                    ->get();

                // Point de vente destination par défaut (le PV du manager)
                $defaultSource = $employe->points_vente_id;
            }

            // CAS INTERDIT : caissier / admin / superadmin / autres
            else {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * FORMATAGE POUR LES SELECTS
             * =========================
             */
            $pointsVenteSourceSelect = $pointsVenteSource->map(fn ($pv) => [
                'value' => $pv->id,
                'label' => $pv->nom,
            ]);

            $pointsVenteDestinationSelect = $pointsVenteDestination->map(fn ($pv) => [
                'value' => $pv->id,
                'label' => $pv->nom,
            ]);

            /**
             * =========================
             * ARTICLES DISPONIBLES
             * Les articles sont ceux des points de vente SOURCE
             * =========================
             */
            $articles = Article::whereIn('points_vente_id', $pointsVenteDestination->pluck('id'))
                ->with('pointVente:id,nom')
                ->orderBy('nom')
                ->get()
                ->map(fn ($a) => [
                    'value' => $a->id,
                    'label' => "({$a->marque}) {$a->nom} ",
                    'stock' => $a->quantite_stock,
                    'point_vente_id' => $a->points_vente_id,
                    'point_vente_nom' => $a->pointVente->nom ?? '',
                ]);

            /**
             * =========================
             * RÉFÉRENCE TRANSFERT
             * =========================
             */
            $reference = 'TRF-' . date('Ymd') . '-' . strtoupper(Generator::generateRandomString(6));

            // Déterminer le layout à utiliser
            $useDashboardLayout = $user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
                config('appconstants.role.marchand'),
            ]);

            return Inertia::render('GestionStock::Transferts/Create', [
                'pointsVenteSource' => $pointsVenteSourceSelect,
                'pointsVenteDestination' => $pointsVenteDestinationSelect,
                'defaultSource' => $defaultSource ?? null,
                'articles' => $articles,
                'reference' => $reference,
                'useDashboardLayout' => $useDashboardLayout,
                'pageTitle' => trans('modules.business.transfertStock.create'),
            ]);

        } catch (\Throwable $e) {
            log_error('TransfertStock', 'create', $e->getMessage());
            return redirect()
                ->route('transfert-stock.index')
                ->with('error', 'Erreur lors du chargement du formulaire de création');
        }
    }


    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * CONTRÔLE DES RÔLES
             * =========================
             */
            if ($user->hasRole(config('appconstants.role.caissier'))) {
                return back()->with('error', trans('unauthorized_action'));
            }

            $employe = $user->employePrincipal;
            $marchand = $user->marchand ?? ($employe?->pointVente?->marchand ?? null);

            if (!$marchand) {
                return back()->with('error', trans('marchand_not_rattach'));
            }

            /**
             * =========================
             * VALIDATION
             * =========================
             */
            $validated = $request->validate([
                'emplacement_source_id' => 'required|exists:points_vente,id',
                'emplacement_destination_id' => 'required|exists:points_vente,id',
                'lignes' => 'required|array|min:1',
                'lignes.*.article_id' => 'required|exists:articles,id',
                'lignes.*.quantite_demandee' => 'required|integer|min:1',
                'commentaire' => 'nullable|string|max:255',
            ]);

            if ($validated['emplacement_source_id'] === $validated['emplacement_destination_id']) {
                return back()->with('error', trans('source_destination_same'));
            }

            /**
             * =========================
             * CHARGEMENT DES POINTS
             * =========================
             */
            $source = PointVente::where('id', $validated['emplacement_source_id'])
                ->where('marchand_id', $marchand->id)
                ->lockForUpdate()
                ->firstOrFail();

            $destination = PointVente::where('id', $validated['emplacement_destination_id'])
                ->where('marchand_id', $marchand->id)
                ->firstOrFail();

            /**
             * =========================
             * CRÉATION DU TRANSFERT
             * =========================
             */
            $transfert = TransfertStock::create([
                'reference' => Generator::generateTransfertStockReference(
                    $marchand->id,
                    $destination->id
                ),
                'emplacement_source_id' => $source->id,
                'emplacement_destination_id' => $destination->id,
                'statut' => TransfertStock::STATUT_EN_COURS,
                'date_demande' => now(),
                'demande_par' => $employe?->id,
                'commentaire' => $validated['commentaire'] ?? null,
            ]);

            /**
             * =========================
             * TRAITEMENT DES LIGNES
             * =========================
             */
            foreach ($validated['lignes'] as $ligne) {

                $article = Article::where('id', $ligne['article_id'])
                    ->where('points_vente_id', $destination->id)
                    ->lockForUpdate()
                    ->first();

                if (!$article) {
                    return back()->with('error', trans('article_not_source'));
                }

                if ($article->quantite_stock < $ligne['quantite_demandee']) {
                    throw new \Exception(
                        "Stock insuffisant pour {$article->sku}"
                    );
                }

                TransfertStockLigne::create([
                    'transfert_stock_id' => $transfert->id,
                    'article_id' => $article->id,
                    'quantite_demandee' => $ligne['quantite_demandee'],
                    'quantite_approuvee' => 0,
                ]);
            }

            DB::commit();

            /**
             * =========================
             * Notification au point de vente DESTINATION
             * =========================
             */
            try {
                // Trouver TOUS les employés managers du point de vente DESTINATION (qui doit valider)
                $employesDestination = Employe::where('points_vente_id', $destination->id)
                    ->whereHas('user', function($q) {
                        $q->whereHas('roles', function($rq) {
                            $rq->where('name', config('appconstants.role.manager'));
                        });
                    })
                    ->with('user')
                    ->get();

                foreach ($employesDestination as $employeDestination) {
                    // Ne pas notifier le demandeur lui-même
                    if ($employeDestination->users_id && $employeDestination->users_id !== $user->id) {
                        NotificationService::sendNotification(
                            $employeDestination->users_id,
                            'Nouvelle demande de transfert de stock',
                            "{$destination->nom} demande un transfert ({$transfert->reference})",
                            [
                                'type' => 'transfert_stock',
                                'action' => 'nouvelle_demande',
                                'transfert_id' => $transfert->id,
                                'reference' => $transfert->reference,
                                'statut' => $transfert->statut,
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                // Silently catch notification errors
            }

            return redirect()
                ->route('transfert-stock.index')
                ->with('success', trans('transfert_created_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('TransfertStock', 'store', $e->getMessage());
            return back()->with('error', trans('transfert_create_error'))->withInput();
        }
    }

    public function show(int $id)
    {
        try {
            $user = auth()->user();

            /**
             * =========================
             * Charger le transfert
             * =========================
             */
            $transfert = TransfertStock::with([
                'emplacementSource',
                'emplacementDestination',
                'demandePar.user',
                'confirmePar.user',
                'lignes.article',
            ])->findOrFail($id);

            /**
             * =========================
             * VISIBILITÉ / ACCÈS
             * =========================
             * WORKFLOW: Demande de réapprovisionnement
             * - DESTINATION = celui qui DEMANDE (le demandeur)
             * - SOURCE = celui qui VALIDE (celui qui a le stock)
             *
             * Le demandeur NE PEUT PAS valider sa propre demande!
             * Seul le responsable du point SOURCE peut valider.
             */
            $peutVoir = false;
            $peutValider = false;

            $employe = $user->employePrincipal;
            $marchand = $user->marchand;

            // ADMIN / SUPERADMIN → accès total
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {
                $peutVoir = true;
                $peutValider = true;
            }

            // MARCHAND → seulement ses points de vente
            elseif ($marchand) {

                $pointsVenteIds = PointVente::where('marchand_id', $marchand->id)
                    ->whereNull('parent_points_vente_id')
                    ->pluck('id');

                if (
                    $pointsVenteIds->contains($transfert->emplacement_source_id) ||
                    $pointsVenteIds->contains($transfert->emplacement_destination_id)
                ) {
                    $peutVoir = true;

                    // Peut valider uniquement si propriétaire du point SOURCE (celui qui envoie)
                    if ($pointsVenteIds->contains($transfert->emplacement_destination_id)) {
                        $peutValider = true;
                    }
                }
            }

            // EMPLOYÉ (manager / caissier)
            elseif ($employe) {

                $pointVenteId = $employe->points_vente_id;

                if (
                    $pointVenteId === $transfert->emplacement_source_id ||
                    $pointVenteId === $transfert->emplacement_destination_id
                ) {
                    $peutVoir = true;
                }

                // Seul le MANAGER du point SOURCE peut valider (celui qui a le stock)
                // Le demandeur (destination) NE PEUT PAS valider sa propre demande
                if (
                    $employe->type_employe === config('appconstants.type_employe.manager') &&
                    $pointVenteId === $transfert->emplacement_destination_id
                ) {
                    $peutValider = true;
                }
            }

            if (!$peutVoir) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Préparation données lignes
             * =========================
             */
            $lignes = $transfert->lignes->map(function ($ligne) {
                return [
                    'id' => $ligne->id,
                    'article' => [
                        'id' => $ligne->article->id,
                        'nom' => $ligne->article->nom,
                        'sku' => $ligne->article->sku,
                    ],
                    'stock_disponible' => $ligne->article->quantite_stock ?? 0,
                    'quantite_demandee' => $ligne->quantite_demandee,
                    'quantite_approuvee' => $ligne->quantite_approuvee,
                ];
            });

            /**
             * =========================
             * Rendu Inertia
             * =========================
             */
            // Déterminer le layout à utiliser
            $useDashboardLayout = $user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
                config('appconstants.role.marchand'),
            ]);

            return Inertia::render('GestionStock::Transferts/Show', [
                'transfert' => [
                    'id' => $transfert->id,
                    'reference' => $transfert->reference,
                    'statut' => $transfert->statut,
                    'date_demande' => $transfert->date_demande,
                    'date_confirmation' => $transfert->date_confirmation,
                    'commentaire' => $transfert->commentaire,
                    'commentaire_confirmation' => $transfert->commentaire_confirmation,
                    'emplacement_source' => [
                        'id' => $transfert->emplacementSource->id,
                        'nom' => $transfert->emplacementSource->nom,
                    ],
                    'emplacement_destination' => [
                        'id' => $transfert->emplacementDestination->id,
                        'nom' => $transfert->emplacementDestination->nom,
                    ],
                    'demande_par' => $transfert->demandePar?->user?->fullName() ?? '',
                    'demande_par_user_id' => $transfert->demandePar?->user?->id,
                    'confirme_par' => $transfert->confirmePar?->user?->fullName()  ?? '',
                    'lignes' => $lignes,
                ],
                'canValidate' => $peutValider,
                'useDashboardLayout' => $useDashboardLayout,
                'statuts' => [
                    TransfertStock::STATUT_EN_COURS,
                    TransfertStock::STATUT_PARTIEL,
                    TransfertStock::STATUT_CONFIRME,
                    TransfertStock::STATUT_ANNULE,
                ],
                'pageTitle' => trans('modules.business.transfertStock.show'),
            ]);

        } catch (\Throwable $e) {
            log_error('TransfertStock', 'show', $e->getMessage());
            return redirect()
                ->route('transfert-stock.index')
                ->with('error', 'Erreur lors de l’affichage du transfert');
        }
    }

    public function edit(int $id)
    {
        try {
            $user = auth()->user();

            /**
             * =========================
             * Charger le transfert
             * =========================
             */
            $transfert = TransfertStock::with([
                'emplacementSource',
                'emplacementDestination',
                'lignes.article.pointVente',
                'demandePar.user',
            ])->findOrFail($id);

            /**
             * =========================
             * Statut modifiable ?
             * =========================
             */
            if ($transfert->statut !== TransfertStock::STATUT_EN_COURS) {
                return back()->with('error', trans('transfert_not_editable'));
            }

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */

            // ADMIN / SUPERADMIN → OK
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {
                // accès total
            }

            // PROPRIÉTAIRE (marchand)
            elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;

                if (!$marchand) {
                    return back()->with('error', trans('marchand_not_rattach'));
                }

                if (
                    $transfert->emplacementSource->marchand_id !== $marchand->id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            // EMPLOYÉ (hors caissier)
            else {
                $employe = $user->employePrincipal;

                if (!$employe) {
                    return back()->with('error', trans('employe_not_rattach'));
                }

                if ($employe->type_employe === config('appconstants.type_employe.caissier')) {
                    return back()->with('error', trans('unauthorized_action'));
                }

                if ($transfert->emplacement_source_id !== $employe->points_vente_id) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            /**
             * =========================
             * Points de vente DESTINATION
             * (même marchand, sauf source)
             * =========================
             */
            $pointsVenteDestination = PointVente::where(
                'marchand_id',
                $transfert->emplacementSource->marchand_id
            )
                ->whereNull('parent_points_vente_id')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->where('id', '!=', $transfert->emplacement_source_id)
                ->orderBy('nom')
                ->get()
                ->map(fn ($pv) => [
                    'value' => $pv->id,
                    'label' => $pv->nom,
                ]);

            /**
             * =========================
             * Données lignes (UI)
             * =========================
             */
            $lignes = $transfert->lignes->map(function ($ligne) {
                return [
                    'id' => $ligne->id,
                    'article_id' => $ligne->article_id,
                    'article_nom' => $ligne->article->nom ?? '',
                    'sku' => $ligne->article->sku ?? '',
                    'marque' => $ligne->article->marque ?? '',
                    'stock_disponible' => $ligne->article->quantite_stock ?? 0,
                    'quantite_demandee' => $ligne->quantite_demandee,
                ];
            });

            // Déterminer le layout à utiliser
            $useDashboardLayout = $user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
                config('appconstants.role.marchand'),
            ]);

            return Inertia::render('GestionStock::Transferts/Edit', [
                'transfert' => [
                    'id' => $transfert->id,
                    'reference' => $transfert->reference,
                    'statut' => $transfert->statut,
                    'date_demande' => $transfert->date_demande,
                    'emplacement_source' => [
                        'id' => $transfert->emplacementSource->id,
                        'nom' => $transfert->emplacementSource->nom,
                    ],
                    'emplacement_destination_id' => $transfert->emplacement_destination_id,
                ],
                'lignes' => $lignes,
                'pointsVenteDestination' => $pointsVenteDestination,
                'useDashboardLayout' => $useDashboardLayout,
                'pageTitle' => trans('modules.business.transfertStock.edit'),
            ]);

        } catch (\Throwable $e) {
            log_error('TransfertStock', 'edit', $e->getMessage());
            return redirect()
                ->route('transfert-stock.index')
                ->with('error', trans('Erreur'));
        }
    }

    public function update(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Charger le transfert
             * =========================
             */
            $transfert = TransfertStock::with([
                'lignes',
                'emplacementSource',
            ])->lockForUpdate()->findOrFail($id);

            /**
             * =========================
             * Statut modifiable ?
             * =========================
             */
            if ($transfert->statut !== TransfertStock::STATUT_EN_COURS) {
                return back()->with('error', trans('transfert_not_editable'));
            }

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */

            // ADMIN / SUPERADMIN → OK
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {
                // accès total
            }

            // PROPRIÉTAIRE (marchand)
            elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;

                if (
                    !$marchand ||
                    $transfert->emplacementSource->marchand_id !== $marchand->id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            // EMPLOYÉ (hors caissier)
            else {
                $employe = $user->employePrincipal;

                if (!$employe) {
                    return back()->with('error', trans('employe_not_rattach'));
                }

                if ($employe->type_employe === config('appconstants.type_employe.caissier')) {
                    return back()->with('error', trans('unauthorized_action'));
                }

                if ($transfert->emplacement_source_id !== $employe->points_vente_id) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'emplacement_destination_id' => 'required|exists:points_vente,id',
                'lignes' => 'required|array|min:1',
                'lignes.*.id' => 'required|exists:transferts_stock_lignes,id',
                'lignes.*.quantite_demandee' => 'required|integer|min:1',
            ]);

            /**
             * =========================
             * Destination valide ?
             * (même marchand, ≠ source)
             * =========================
             */
            if ($validated['emplacement_destination_id'] == $transfert->emplacement_source_id) {
                return back()->with('error', trans('invalid_destination'));
            }

            $destination = PointVente::findOrFail($validated['emplacement_destination_id']);

            if (
                $destination->marchand_id !== $transfert->emplacementSource->marchand_id
            ) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Mise à jour du transfert
             * =========================
             */
            $transfert->update([
                'emplacement_destination_id' => $validated['emplacement_destination_id'],
            ]);

            /**
             * =========================
             * Mise à jour des lignes
             * =========================
             */
            foreach ($validated['lignes'] as $ligneData) {

                $ligne = $transfert->lignes->firstWhere('id', $ligneData['id']);

                if (!$ligne) {
                    return back()->with('error', trans('ligne_not_found'));
                }

                // Mise à jour quantité demandée
                $ligne->update([
                    'quantite_demandee' => $ligneData['quantite_demandee'],
                ]);
            }

            DB::commit();

            return redirect()
                ->route('transfert-stock.index')
                ->with('success', trans('transfert_updated_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('TransfertStock', 'update', $e->getMessage());
            return back()->with('error', trans('Erreur'))->withInput();
        }
    }

    public function validateTransfert(Request $request, int $transfertId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Charger le transfert
             * =========================
             */
            $transfert = TransfertStock::with([
                'lignes.article',
                'emplacementSource',
                'emplacementDestination',
            ])->lockForUpdate()->findOrFail($transfertId);

            /**
             * =========================
             * Vérifier statut
             * =========================
             */
            if (!in_array($transfert->statut, [
                TransfertStock::STATUT_EN_COURS,
                TransfertStock::STATUT_PARTIEL,
            ])) {
                return back()->with('error', trans('transfert_not_validable'));
            }

            /**
             * =========================
             * Vérification des droits
             * =========================
             * WORKFLOW: Demande de réapprovisionnement
             * - SOURCE = celui qui VALIDE (celui qui a le stock et décide d'envoyer)
             * - DESTINATION = celui qui a fait la demande (ne peut pas valider)
             */
            $peutValider = false;
            $employe = $user->employePrincipal;
            $marchand = $user->marchand;

            // ADMIN / SUPERADMIN
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {
                $peutValider = true;
            }

            // MANAGER DU POINT SOURCE (celui qui a le stock)
            elseif ($employe && $employe->type_employe === config('appconstants.type_employe.manager')) {
                if ($employe->points_vente_id === $transfert->emplacement_destination_id) {
                    $peutValider = true;
                }
            }

            // MARCHAND PROPRIÉTAIRE DU POINT SOURCE
            elseif ($marchand) {
                $isOwner = PointVente::where('id', $transfert->emplacement_destination_id)
                    ->where('marchand_id', $marchand->id)
                    ->exists();

                if ($isOwner) {
                    $peutValider = true;
                }
            }

            if (!$peutValider) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Validation entrée
             * =========================
             */
            $validated = $request->validate([
                'lignes' => 'required|array|min:1',
                'lignes.*.ligne_id' => 'required|exists:transferts_stock_lignes,id',
                'lignes.*.quantite_approuvee' => 'required|integer|min:0',
                'commentaire' => 'nullable|string|max:255',
            ]);

            $totalApprouve = 0;
            $partiel = false;

            /**
             * =========================
             * Traitement des lignes
             * =========================
             */
            foreach ($validated['lignes'] as $input) {

                // Utiliser find() pour obtenir un modèle Eloquent frais
                $ligne = TransfertStockLigne::where('id', $input['ligne_id'])
                    ->where('transfert_stock_id', $transfert->id)
                    ->first();

                if (!$ligne) {
                    return back()->with('error', trans('ligne_not_found'));
                }

                $quantiteApprouvee = (int) $input['quantite_approuvee'];

                if ($quantiteApprouvee > $ligne->quantite_demandee) {
                    return back()->with('error', trans('quantite_invalide'));
                }

                if ($quantiteApprouvee < $ligne->quantite_demandee) {
                    $partiel = true;
                }

                if ($quantiteApprouvee === 0) {
                    // Mettre à jour la ligne avec quantité 0 (refusée)
                    TransfertStockLigne::where('id', $ligne->id)->update([
                        'quantite_approuvee' => 0,
                        'statut' => TransfertStockLigne::STATUT_ANNULE,
                    ]);
                    continue;
                }elseif ($partiel){
                    TransfertStockLigne::where('id', $ligne->id)->update([
                        'quantite_approuvee' => $quantiteApprouvee,
                        'statut' => TransfertStockLigne::STATUT_PARTIEL,
                    ]);
                }else{
                    TransfertStockLigne::where('id', $ligne->id)->update([
                        'quantite_approuvee' => $quantiteApprouvee,
                        'statut' => TransfertStockLigne::STATUT_EN_ATTENTE,
                    ]);
                }

                $totalApprouve += $quantiteApprouvee;
            }

            /**
             * =========================
             * Mise à jour transfert
             * =========================
             */
            $transfert->update([
                'statut' => $totalApprouve === 0
                    ? TransfertStock::STATUT_ANNULE
                    : ($partiel
                        ? TransfertStock::STATUT_PARTIEL
                        : TransfertStock::STATUT_CONFIRME),
                'date_confirmation' => now(),
                'confirme_par' => $employe?->id,
            ]);

            DB::commit();

            /**
             * =========================
             * Notification au demandeur
             * =========================
             */
            try {
                $demandeur = $transfert->demandePar;

                if ($demandeur && $demandeur->users_id) {
                    $statutLabel = $totalApprouve === 0
                        ? 'refusé'
                        : ($partiel ? 'partiellement validé' : 'validé');

                    $emoji = $totalApprouve === 0 ? '❌' : ($partiel ? '⚠️' : '✅');

                    NotificationService::sendNotification(
                        $demandeur->users_id,
                        $emoji . ' Transfert ' . $statutLabel,
                        "Demande {$transfert->reference} " . ($totalApprouve === 0 ? 'refusée' : ($partiel ? 'validée partiellement' : 'approuvée')) . " par {$transfert->emplacementDestination->nom}",
                        [
                            'type' => 'transfert_stock',
                            'action' => 'validation',
                            'transfert_id' => $transfert->id,
                            'reference' => $transfert->reference,
                            'statut' => $transfert->statut,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Erreur notification validation: ' . $e->getMessage());
            }

            return back()->with('success', trans('transfert_validated_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('TransfertStock', 'validateTransfert', $e->getMessage());
            return back()->with('error', trans('transfert_validation_error'));
        }
    }

    public function receptionTransfert(Request $request, int $transfertId)
    {
        DB::beginTransaction();
        try {
            $user = auth()->user();
            $employe = $user->employePrincipal;
            if (!$employe) {
                return back()->with('error', trans('unauthorized_action'));
            }
            if ($user->hasRole(config('appconstants.role.caissier'))){
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Charger transfert
             * =========================
             */
            $transfert = TransfertStock::with([
                'lignes.article',
                'emplacementSource',
                'emplacementDestination',
            ])->lockForUpdate()->findOrFail($transfertId);

            /**
             * =========================
             * Vérifier statut
             * =========================
             */
            if (in_array($transfert->statut, [
                TransfertStock::STATUT_RECEPTIONNE,
                TransfertStock::STATUT_EN_COURS,
            ])) {
                return back()->with('error', trans('transfert_not_receivable'));
            }

            /**
             * =========================
             * DROITS : POINT A (DEMANDEUR)
             * =========================
             */
            if (
                !$employe ||
                $employe->points_vente_id !== $transfert->emplacement_source_id
            ) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Réception + mouvements stock
             * =========================
             */
            foreach ($transfert->lignes as $ligne) {

                if ($ligne->quantite_approuvee <= 0) {
                    continue;
                }

                /**
                 * Article au POINT B (source du stock)
                 */
                $articleSource = Article::where('id', $ligne->article_id)
                    ->where('points_vente_id', $transfert->emplacement_destination_id)
                    ->lockForUpdate()
                    ->first();

                if (!$articleSource) {
                    return back()->with(
                        'error',
                        "Article {$ligne->article->sku} introuvable au point source"
                    );
                }

                if ($articleSource->quantite_stock < $ligne->quantite_approuvee) {
                    return back()->with(
                        'error',
                        "Stock insuffisant pour {$articleSource->sku}"
                    );
                }

                /**
                 * SORTIE STOCK POINT B
                 */
                StockMovementHelper::apply(
                    article: $articleSource,
                    quantite: -$ligne->quantite_approuvee,
                    typeMouvement: MouvementStock::TYPE_TRANSFERT,
                    emplacementSourceId: $transfert->emplacement_destination_id,
                    emplacementDestinationId: null,
                    employe: $employe,
                    reference: $transfert->reference,
                    commentaire: 'Transfert stock - sortie (réception)'
                );

                /**
                 * Article au POINT A (réception)
                 */
                $articleDestination = Article::where('sku', $articleSource->sku)
                    ->where('points_vente_id', $transfert->emplacement_source_id)
                    ->lockForUpdate()
                    ->first();

                if (!$articleDestination) {
                    $articleDestination = $articleSource->replicate();
                    $articleDestination->points_vente_id = $transfert->emplacement_source_id;
                    $articleDestination->quantite_stock = 0;
                    $articleDestination->save();
                }

                /**
                 * ENTRÉE STOCK POINT A
                 */
                StockMovementHelper::apply(
                    article: $articleDestination,
                    quantite: $ligne->quantite_approuvee,
                    typeMouvement: MouvementStock::TYPE_TRANSFERT,
                    emplacementSourceId: null,
                    emplacementDestinationId: $transfert->emplacement_source_id,
                    employe: $employe,
                    reference: $transfert->reference,
                    commentaire: 'Transfert stock - entrée (réception)'
                );
            }
            /**
             * =========================
             * Clôture transfert
             * =========================
             */
            $transfert->update([
                'statut' => TransfertStock::STATUT_RECEPTIONNE,
                'date_reception' => now(),
                'recu_par' => $employe->id,
            ]);
            DB::commit();

            /**
             * =========================
             * Notification au validateur
             * =========================
             */
            try {
                $confirmateur = $transfert->confirmePar;

                if ($confirmateur && $confirmateur->users_id) {
                    NotificationService::sendNotification(
                        $confirmateur->users_id,
                        '📦 Transfert réceptionné',
                        "Demande {$transfert->reference} reçue par {$transfert->emplacementSource->nom}",
                        [
                            'type' => 'transfert_stock',
                            'action' => 'reception',
                            'transfert_id' => $transfert->id,
                            'reference' => $transfert->reference,
                            'statut' => $transfert->statut,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                log_error('TransfertStock', 'sendNotification', $e->getMessage());
            }

            return back()->with('success', trans('transfert_reception_success'));
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('TransfertStock', 'receptionTransfert', $e->getMessage());
            return back()->with('error', trans('transfert_reception_error'));
        }
    }


}
