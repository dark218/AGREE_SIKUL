<?php

namespace Modules\Pos\Http\Controllers;

use App\Helpers\StockMovementHelper;
use App\Http\Controllers\Controller;
use App\Services\Generator;
use App\Services\MoneyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Business\Entities\Employe;
use Modules\GestionStock\Entities\Article;
use Modules\GestionStock\Entities\MouvementStock;
use Modules\Parametrage\Entities\Pays;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;
use Modules\Pos\Entities\VentePosLigne;
use Modules\Wallet\Entities\Transactions;
use Modules\Wallet\Entities\Wallet;
use Modules\Wallet\Entities\WalletMouvement;

class VentePosController extends Controller
{
    public function __construct()
    {
        // Consultation
        $this->middleware('permission.check:ventepos-list')
            ->only(['index', 'show']);

        // Création de vente (avant paiement)
        $this->middleware('permission.check:ventepos-create')
            ->only(['create', 'store']);
        // Validation du paiement (impact caisse)
        $this->middleware('permission.check:ventepos-validate')
            ->only(['validatePaiement']);

        // Modification avant paiement
        $this->middleware('permission.check:ventepos-edit')
            ->only(['edit', 'update', 'removeLine']);

        // Annulation
        $this->middleware('permission.check:ventepos-cancel')
            ->only(['cancelVente']);

        // Remboursements (manager uniquement)
        $this->middleware('permission.check:ventepos-refund')
            ->only(['refundVente']);

        $this->middleware('permission.check:ventepos-refund-line')
            ->only(['refundVenteByLines']);

        $this->middleware('permission.check:ventepos-refund-quantite')
            ->only(['refundVenteByQuantite']);

        $this->middleware('permission.check:ventepos-refund-mixte')
            ->only(['refundVenteMixte']);

        $this->middleware('permission.check:ventepos-refund-mixte-quantite')
            ->only(['refundVenteMixteQuantite']);
    }

    /**
     * Recherche d'articles pour l'interface POS (AJAX)
     */
    public function searchArticles(Request $request)
    {
        try {
            $user = auth()->user();
            $query = $request->input('q', '');
            $sessionId = $request->input('session_id');

            if (empty($query) || strlen($query) < 2) {
                return response()->json([]);
            }

            // Récupérer le point de vente via la session
            $session = SessionCaisse::with('caisse')->find($sessionId);
            if (!$session) {
                return response()->json([]);
            }

            $pointVenteId = $session->caisse->points_vente_id;

            $articles = Article::where('points_vente_id', $pointVenteId)
                ->where(function ($q) use ($query) {
                    $q->where('sku', 'LIKE', "%{$query}%")
                      ->orWhere('nom', 'LIKE', "%{$query}%");
                })
                ->where('quantite_stock', '>', 0)
                ->limit(20)
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'sku' => $article->sku,
                        'marque' => $article->marque,
                        'nom' => $article->nom,
                        'prix' => $article->prix_cents,
                        'prix_display' => (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise)),
                        'devise' => $article->devise,
                        'stock' => $article->quantite_stock,
                        'taxes' => $article->taxes_json ?? [],
                    ];
                });

            return response()->json($articles);

        } catch (\Throwable $e) {
            log_error('VentePos', 'searchArticles', $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $sessionId = $request->input('session_id');
            $statut    = $request->input('statut');
            $pointVenteId  = $request->input('point_vente_id');
            $employeId     = $request->input('employe_id');
            $modePaiement  = $request->input('mode_paiement');

            $modePaiements = [
                ['value' => 'espece', 'label' => 'Espèces'],
                ['value' => 'electronique', 'label' => 'Électronique'],
                ['value' => 'mixte', 'label' => 'Mixte'],
            ];


            $query = VentePos::with([
                'sessionCaisse.caisse.pointVente',
                'employe.user',
                'lignes.article',
            ])->orderBy('created_at', 'DESC');

            /**
             * =========================
             * CAS CAISSIER
             * =========================
             */
            if ($user->hasRole(config('appconstants.role.caissier'))) {

                $employe = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe) {
                    return back()->with('error', trans('caissier_feature'));
                }
                // Sessions du caissier
                $sessions = SessionCaisse::where('caissier_id', $employe->id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($s) => [
                        'value' => $s->id,
                        'label' => 'Session ' . $s->reference . ' - ' . ucfirst($s->statut),
                    ]);

                // Le caissier ne filtre QUE lui-même
                $employes = collect([
                    [
                        'value' => $employe->id,
                        'label' => $employe->user->prenoms . ' ' . $employe->user->nom,
                    ]
                ]);

                // Point de vente unique
                $pointsVente = collect([
                    [
                        'value' => $employe->points_vente_id,
                        'label' => $employe->pointVente->nom,
                    ]
                ]);

                // Le caissier ne voit que ses ventes
                $query->where('employe_id', $employe->id);
            }

            /**
             * =========================
             * CAS MANAGER
             * =========================
             */
            if ($user->hasRole(config('appconstants.role.manager'))) {

                $employeManager = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (!$employeManager || !$employeManager->points_vente_id) {
                    return back()->with('error', trans('manager_feature'));
                }
                $pointVenteId = $employeManager->points_vente_id;

                // Sessions du point de vente
                $sessions = SessionCaisse::whereHas('caissier', function ($q) use ($pointVenteId) {
                    $q->where('points_vente_id', $pointVenteId);
                })
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($s) => [
                        'value' => $s->id,
                        'label' => 'Session ' . $s->id . ' - ' . ucfirst($s->statut),
                    ]);

                // Caissiers du point de vente
                $employes = Employe::with('user')
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->where('points_vente_id', $pointVenteId)
                    ->get()
                    ->map(fn ($e) => [
                        'value' => $e->id,
                        'label' => $e->user->prenoms . ' ' . $e->user->nom,
                    ]);

                // Point de vente unique
                $pointsVente = collect([
                    [
                        'value' => $pointVenteId,
                        'label' => $employeManager->pointVente->nom,
                    ]
                ]);
                // Le manager voit toutes les ventes du point de vente
                $query->whereHas('sessionCaisse.caissier', function ($q) use ($employeManager) {
                    $q->where('points_vente_id', $employeManager->points_vente_id);
                });
            }

            /**
             * =========================
             * FILTRES
             * =========================
             */
            if (!empty($sessionId)) {
                $query->where('sessions_caisse_id', $sessionId);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            if (!empty($statut)) {
                $query->where('statut', $statut);
            }

            if (!empty($modePaiement)) {
                $query->where('mode_paiement', $modePaiement);
            }

            if (!empty($employeId)) {
                $query->where('employe_id', $employeId);
            }

            if (!empty($pointVenteId)) {
                $query->whereHas('sessionCaisse.caissier', function ($q) use ($pointVenteId) {
                    $q->where('points_vente_id', $pointVenteId);
                });
            }

            $ventes = $query->paginate(10)->withQueryString();

            $ventes->getCollection()->transform(function ($vente) {
                return [
                    'id' => $vente->id,
                    'reference' => $vente->reference ?? 'N/A',
                    'total' => $vente->total_cents !== null && $vente->devise ? (float) str_replace(' ', '', MoneyService::toDisplay($vente->total_cents, $vente->devise)) : 0,
                    'mode_paiement' => $vente->mode_paiement,
                    'statut' => $vente->statut,
                    'caissier' => trim(($vente->employe?->user?->nom ?? '') . ' ' . ($vente->employe?->user?->prenoms ?? '')) ?: 'N/A',
                    'session_id' => $vente->sessions_caisse_id,
                    'date' => $vente->created_at?->format('d/m/Y H:i') ?? 'N/A',
                    'devise' => $vente->devise ?? 'XOF',
                    'point_vente' => $vente->sessionCaisse?->caisse?->pointVente?->nom ?? '',
                    'lignes' => $vente->lignes->map(function ($ligne) use ($vente) {
                        return [
                            'id' => $ligne->id,
                            'libelle' => $ligne->libelle ?? 'Article',
                            'quantite' => $ligne->quantite,
                            'prix_unitaire' => (float) str_replace(' ', '', MoneyService::toDisplay($ligne->prix_unitaire_cents ?? 0, $vente->devise ?? 'XOF')),
                            'total_ligne' => (float) str_replace(' ', '', MoneyService::toDisplay($ligne->total_ligne_cents ?? 0, $vente->devise ?? 'XOF')),
                        ];
                    })->toArray(),
                ];
            });

            return Inertia::render('Pos::VentePos/Index', [
                'ventes' => $ventes,
                'sessions' => $sessions ?? [],
                'employes' => $employes ?? [],
                'pointsVente' => $pointsVente ?? [],
                'modePaiements' => $modePaiements,
                'filters' => [
                    'session_id' => $sessionId,
                    'statut' => $statut,
                    'mode_paiement' => $modePaiement,
                    'employe_id' => $employeId,
                    'point_vente_id' => $pointVenteId,
                ],
            ]);

        } catch (\Throwable $e) {
            log_error('VentePos', 'index', $e->getMessage());
            return back()->with('error', trans('Erreur'));
        }
    }
    public function create()
    {
        try {
            $user = auth()->user();
            if ($user->pays_id == null) {
                return back()->with('error', trans('pays_required'));
            }

            // =========================
            // Modes de paiement (enum)
            // =========================
            $modePaiements = [
                ['value' => 'espece', 'label' => 'Espèces'],
                ['value' => 'electronique', 'label' => 'Électronique'],
                ['value' => 'mixte', 'label' => 'Mixte'],
            ];

            /**
             * =========================
             * CAS CAISSIER
             * =========================
             */
            if ($user->hasRole(config('appconstants.role.caissier'))) {

                $employe = Employe::with(['user', 'pointVente'])
                    ->where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe) {
                    return back()->with('error', trans('caissier_feature'));
                }

                // Récupérer la session OUVERTE du caissier
                $sessionOuverte = SessionCaisse::where('caissier_id', $employe->id)
                    ->where('statut', config('appconstants.session_caisse_statut.ouverte'))
                    ->first();

                if (!$sessionOuverte) {
                    return redirect()
                        ->route('session-caisse-caissier.index')
                        ->with('error', trans('no_active_session'));
                }

                // Sessions du caissier (uniquement la session ouverte pour la création de vente)
                $sessions = collect([
                    [
                        'value' => $sessionOuverte->id,
                        'label' => 'Session ' . $sessionOuverte->id . ' - ' . ucfirst($sessionOuverte->statut),
                    ]
                ]);

                // Session sélectionnée par défaut
                $selectedSessionId = $sessionOuverte->id;

                // Caissier unique
                $employes = collect([
                    [
                        'value' => $employe->id,
                        'label' => $employe->user->prenoms . ' ' . $employe->user->nom,
                    ]
                ]);

                // Point de vente unique
                $pointsVente = collect([
                    [
                        'value' => $employe->points_vente_id,
                        'label' => $employe->pointVente->nom ?? 'N/A',
                    ]
                ]);
            }

            /**
             * =========================
             * CAS MANAGER
             * =========================
             */
            if ($user->hasRole(config('appconstants.role.manager'))) {

                $employeManager = Employe::with('pointVente')
                    ->where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (!$employeManager || !$employeManager->points_vente_id) {
                    return back()->with('error', trans('manager_feature'));
                }

                $pointVenteId = $employeManager->points_vente_id;

                // Sessions du point de vente
                $sessions = SessionCaisse::whereHas('caissier', function ($q) use ($pointVenteId) {
                    $q->where('points_vente_id', $pointVenteId);
                })
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn ($s) => [
                        'value' => $s->id,
                        'label' => 'Session ' . $s->id . ' - ' . ucfirst($s->statut),
                    ]);

                // Caissiers du point de vente
                $employes = Employe::with('user')
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->where('points_vente_id', $pointVenteId)
                    ->get()
                    ->map(fn ($e) => [
                        'value' => $e->id,
                        'label' => $e->user->prenoms . ' ' . $e->user->nom,
                    ]);

                // Point de vente unique
                $pointsVente = collect([
                    [
                        'value' => $pointVenteId,
                        'label' => $employeManager->pointVente->nom,
                    ]
                ]);
            }
            $devisesAutorisees = $user->pays
                ->paysDevises()
                ->with('devise')
                ->get()
                ->pluck('devise.code')
                ->toArray();

            /**
             * =========================
             * Charger les articles du point de vente
             * =========================
             */
            $pointVenteIdForArticles = isset($pointsVente) && $pointsVente->isNotEmpty()
                ? $pointsVente->first()['value']
                : null;
            $articlesPaginated = null;
            $categories = [];

            if ($pointVenteIdForArticles) {
                $articlesQuery = Article::where('points_vente_id', $pointVenteIdForArticles)
                    ->where('quantite_stock', '>', 0);

                $articlesPaginated = $articlesQuery
                    ->orderBy('nom', 'asc')
                    ->paginate(12)
                    ->withQueryString();

                // Transformer les articles pour l'affichage
                $articlesPaginated->getCollection()->transform(function ($article) {
                    return [
                        'id' => $article->id,
                        'sku' => $article->sku,
                        'marque'=> $article->marque,
                        'nom' => $article->nom,
                        'description' => $article->description,
                        'prix' => $article->prix_cents,
                        'prix_display' => (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise)),
                        'devise' => $article->devise,
                        'stock' => $article->quantite_stock,
                        'taxes' => $article->taxes_json ?? [],
                    ];
                });
            }

            return Inertia::render('Pos::VentePos/Create', [
                'sessions'      => $sessions ?? [],
                'employes'      => $employes ?? [],
                'pointsVente'   => $pointsVente ?? [],
                'modePaiements' => $modePaiements,
                'devises' => $devisesAutorisees,
                'selectedSessionId' => $selectedSessionId ?? null,
                'articles' => $articlesPaginated,
            ]);


        } catch (\Throwable $e) {
            log_error('VentePos', 'create', $e->getMessage());
            return back()->with('error', trans('error_loading_form'));
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            $pays = Pays::find($user->pays_id);

            /**
             * =========================
             *  Identifier le caissier
             * =========================
             */
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return back()->with('error', trans('caissier_feature'));
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'session_id' => 'required|exists:sessions_caisse,id',
                'mode_paiement' => 'required|in:espece,electronique,mixte',
                'lignes' => 'required|array|min:1',
                'lignes.*.sku' => 'required|string',
                'lignes.*.libelle' => 'required|string|max:255',
                'lignes.*.quantite' => 'required|integer|min:1',
                'lignes.*.prix_unitaire' => 'required|numeric|min:0',
                'lignes.*.remise' => 'nullable|numeric|min:0',
                'lignes.*.taxe' => 'nullable|numeric|min:0',
            ]);

            /**
             * =========================
             * Vérifier la session
             * =========================
             */
            $session = SessionCaisse::with('caisse')
                ->findOrFail($validated['session_id']);

            if ($session->statut !== config('appconstants.session_caisse_statut.ouverte')) {
                return back()->with('error', trans('session_not_open'));
            }

            if ($session->caissier_id !== $employe->id) {
                return back()->with('error', trans('session_not_owner'));
            }

            $devise = $session->devise;
            $pointVenteId = $session->caisse->points_vente_id;
            $paysIso = $pays->iso??"XX";
            $caisseId = $session->caisse_id;
            $reference = Generator::generateVenteReference($paysIso,$pointVenteId,$caisseId);

            /**
             * =========================
             * Vérification du stock
             * =========================
             */
            foreach ($validated['lignes'] as $ligne) {

                $article = Article::where('sku', $ligne['sku'])
                    ->where('points_vente_id', $pointVenteId)
                    ->lockForUpdate()
                    ->first();
                if (!$article) {
                    throw ValidationException::withMessages([
                        'lignes' => [
                            "Article {$ligne['sku']} introuvable pour ce point de vente"
                        ]
                    ]);
                }

                if ($article->quantite_stock < $ligne['quantite']) {
                    throw ValidationException::withMessages([
                        'lignes' => [
                            "Stock insuffisant pour {$article->nom} (disponible : {$article->quantite_stock})"
                        ]
                    ]);
                }
            }

            /**
             * =========================
             * Calcul des montants
             * =========================
             */
            $totalCents = 0;
            $totalEspece = 0;
            $totalElectronique = 0;

            foreach ($validated['lignes'] as $ligne) {

                $prixUnitaireCents = MoneyService::toDatabase(
                    $ligne['prix_unitaire'],
                    $devise
                );

                $remiseCents = MoneyService::toDatabase(
                    $ligne['remise'] ?? 0,
                    $devise
                );

                $taxeCents = MoneyService::toDatabase(
                    $ligne['taxe'] ?? 0,
                    $devise
                );

                $totalLigne = (
                    ($prixUnitaireCents * $ligne['quantite'])
                    - $remiseCents
                    + $taxeCents
                );

                $totalCents += $totalLigne;
            }

            /**
             * =========================
             *  Répartition paiement
             * =========================
             */
            if ($validated['mode_paiement'] === config('appconstants.mode_paiement.espece')) {
                $totalEspece = $totalCents;
            } elseif ($validated['mode_paiement'] === config('appconstants.mode_paiement.electronique')) {
                $totalElectronique = $totalCents;
            } else {
                /**
                 * IMPORTANT :
                 * En mode mixte, la répartition réelle
                 * se fera au moment du validatePaiement
                 */
                $totalEspece = 0;
                $totalElectronique = 0;
            }

            /**
             * =========================
             *  Création de la vente
             * =========================
             */
            $vente = VentePos::create([
                'uuid' => Str::uuid(),
                'reference'=> $reference,
                'points_vente_id' => $session->caisse->points_vente_id,
                'sessions_caisse_id' => $session->id,
                'employe_id' => $employe->id,
                'devise' => $devise,
                'total_cents' => $totalCents,
                'montant_espece_cents' => $totalEspece,
                'montant_non_espece_cents' => $totalElectronique,
                'mode_paiement' => $validated['mode_paiement'],
                'statut' => config('appconstants.statut_vente_pos.en_attente'),
                'refund_cents' => 0,
                'total_rembourse_cents' => 0,
                'rembourse_espece_cents' => 0,
                'rembourse_non_espece_cents' => 0,
                'rembourse_libre_cents' => 0,
            ]);


            /**
             * =========================
             *  Création des lignes
             * =========================
             */
            foreach ($validated['lignes'] as $ligne) {

                // Récupérer l'article correspondant à cette ligne
                $article = Article::where('sku', $ligne['sku'])
                    ->where('points_vente_id', $pointVenteId)
                    ->first();

                $prixUnitaireCents = MoneyService::toDatabase($ligne['prix_unitaire'], $devise);
                $remiseCents = MoneyService::toDatabase($ligne['remise'] ?? 0, $devise);
                $taxeCents = MoneyService::toDatabase($ligne['taxe'] ?? 0, $devise);

                $totalLigne = (
                    ($prixUnitaireCents * $ligne['quantite'])
                    - $remiseCents
                    + $taxeCents
                );

                // Construire le libellé avec la marque si disponible
                $libelle = $ligne['libelle'];
                if (!empty($article->marque)) {
                    $libelle .= ' (' . $article->marque . ')';
                }

                VentePosLigne::create([
                    'ventes_pos_id' => $vente->id,
                    'libelle' => $libelle,
                    'quantite' => $ligne['quantite'],
                    'prix_unitaire_cents' => $prixUnitaireCents,
                    'remise_cents' => $remiseCents,
                    'taxe_cents' => $taxeCents,
                    'total_ligne_cents' => $totalLigne,
                    'article_id' => $article->id,
                    'devise' => $devise,
                    'rembourse_cents' => 0,
                    'quantite_remboursee' => 0,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('ventepos.show', $vente->id)
                ->with('success', trans('vente_created_success'));

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'store', $e->getMessage());
            return back()->with('error', trans('vente_create_error'))->withInput();
        }
    }
    public function show(int $id)
    {
        try {
            $user = auth()->user();

            // Charger la vente avec toutes ses dépendances
            $vente = VentePos::with([
                'lignes.article',
                'sessionCaisse.caisse.pointVente',
                'employe.user',
            ])->findOrFail($id);

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */

            // CAS CAISSIER
            if ($user->hasRole(config('appconstants.role.caissier'))) {

                $employe = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe || $vente->employe_id !== $employe->id) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            // CAS MANAGER
            if ($user->hasRole(config('appconstants.role.manager'))) {

                $employeManager = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (
                    !$employeManager ||
                    !$vente->sessionCaisse ||
                    $vente->sessionCaisse->caissier->points_vente_id !== $employeManager->points_vente_id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            /**
             * =========================
             * FORMATAGE POUR L’UI
             * =========================
             */

            $data = [
                'id' => $vente->id,
                'reference' => $vente->reference ?? 'N/A',
                'statut' => $vente->statut,
                'mode_paiement' => $vente->mode_paiement,
                'devise' => $vente->devise ?? 'XOF',
                'rembourse_espece_cents' => $vente->rembourse_espece_cents??0,
                'rembourse_non_espece_cents' => $vente->rembourse_non_espece_cents??0,

                'total' => (float) str_replace(' ', '', MoneyService::toDisplay(
                    $vente->total_cents ?? 0,
                    $vente->devise ?? 'XOF'
                )),

                'montant_espece' => (float) str_replace(' ', '', MoneyService::toDisplay(
                    $vente->montant_espece_cents ?? 0,
                    $vente->devise ?? 'XOF'
                )),

                'montant_non_espece' => (float) str_replace(' ', '', MoneyService::toDisplay(
                    $vente->montant_non_espece_cents ?? 0,
                    $vente->devise ?? 'XOF'
                )),

                // Montant total remboursé
                'total_rembourse_cents' => (float) str_replace(' ', '', MoneyService::toDisplay(
                    $vente->total_rembourse_cents ?? 0,
                    $vente->devise ?? 'XOF'
                )),

                'caissier' => trim(($vente->employe?->user?->nom ?? '') . ' ' . ($vente->employe?->user?->prenoms ?? '')) ?: 'N/A',
                'session_id' => $vente->sessions_caisse_id,
                'date' => $vente->created_at?->format('d/m/Y H:i') ?? 'N/A',
                'point_vente' => $vente->sessionCaisse?->caisse?->pointVente?->nom ?? 'N/A',

                // =========================
                // LIGNES
                // =========================
                'lignes' => $vente->lignes->map(function ($ligne) use ($vente) {
                    // Construire le libellé avec la marque si disponible
                    $libelle = $ligne->libelle ?? 'Article';
//                    $marque = $ligne->article?->marque;
//                    if (!empty($marque) && !str_contains($libelle, '(' . $marque . ')')) {
//                        $libelle .= ' (' . $marque . ')';
//                    }

                    return [
                        'id' => $ligne->id,
                        'libelle' => $libelle,
                        'sku' => $ligne->article?->sku ?? '',
                        'quantite' => $ligne->quantite,

                        'prix_unitaire' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->prix_unitaire_cents ?? 0,
                            $vente->devise ?? 'XOF'
                        )),

                        'remise' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->remise_cents ?? 0,
                            $vente->devise ?? 'XOF'
                        )),

                        'taxe' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->taxe_cents ?? 0,
                            $vente->devise ?? 'XOF'
                        )),

                        'total_ligne' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->total_ligne_cents ?? 0,
                            $vente->devise ?? 'XOF'
                        )),

                        // Données de remboursement par ligne
                        'rembourse_cents' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->rembourse_cents ?? 0,
                            $vente->devise ?? 'XOF'
                        )),
                        'quantite_remboursee' => $ligne->quantite_remboursee ?? 0,
                    ];
                }),
            ];
//            dd($data);

            // Déterminer si l'utilisateur est manager
            $isManager = $user->hasRole(config('appconstants.role.manager'));

            return Inertia::render('Pos::VentePos/Show', [
                'vente' => $data,
                'isManager' => $isManager,
            ]);

        } catch (\Throwable $e) {
            log_error('VentePos', 'show', $e->getMessage());
            return back()->with('error', trans('error_loading_form'));
        }

    }
    public function edit(int $id)
    {
        try {
            $user = auth()->user();
            if ($user->pays_id == null) {
                return back()->with('error', trans('pays_required'));
            }

            // Charger la vente avec lignes et session
            $vente = VentePos::with([
                'lignes.article',
                'sessionCaisse',
                'employe.user',
            ])->findOrFail($id);

            /**
             * =========================
             * CONTRÔLES MÉTIER
             * =========================
             */

            // Vente modifiable uniquement si en attente
            if ($vente->statut !== config('appconstants.statut_vente_pos.en_attente')) {
                return back()->with('error', trans('vente_not_editable'));
            }
            // La session doit être ouverte
            if (
                !$vente->sessionCaisse ||
                $vente->sessionCaisse->statut !== config('appconstants.session_caisse_statut.ouverte')
            ) {
                return back()->with('error', trans('session_not_open'));
            }

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */

            // CAS CAISSIER
            if ($user->hasRole(config('appconstants.role.caissier'))) {

                $employe = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe || $vente->employe_id !== $employe->id) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            // CAS MANAGER
            if ($user->hasRole(config('appconstants.role.manager'))) {

                $employeManager = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (
                    !$employeManager ||
                    $vente->sessionCaisse->caissier->points_vente_id !== $employeManager->points_vente_id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            /**
             * =========================
             * DONNÉES POUR L’UI
             * =========================
             */

            $data = [
                'id' => $vente->id,
                'reference' => $vente->reference,
                'devise' => $vente->devise,
                'mode_paiement' => $vente->mode_paiement,
                'statut' => $vente->statut,
                'caissier' => trim(($vente->employe?->user?->nom ?? '') . ' ' . ($vente->employe?->user?->prenoms ?? '')),
                'date' => $vente->created_at?->format('d/m/Y H:i'),

                'lignes' => $vente->lignes->map(function ($ligne) use ($vente) {
                    return [
                        'id' => $ligne->id,
                        'libelle' => $ligne->libelle,
                        'quantite' => $ligne->quantite,
                        'sku' => $ligne->article?->sku ?? '',
                        'stock' => $ligne->article?->quantite_stock ?? 999,

                        // Montants affichables (pas en cents)
                        'prix_unitaire' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->prix_unitaire_cents,
                            $vente->devise
                        )),
                        'remise' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->remise_cents,
                            $vente->devise
                        )),
                        'taxe' => (float) str_replace(' ', '', MoneyService::toDisplay(
                            $ligne->taxe_cents,
                            $vente->devise
                        )),
                    ];
                }),
            ];

            // Modes de paiement autorisés
            $modePaiements = [
                ['value' => 'espece', 'label' => 'Espèces'],
                ['value' => 'electronique', 'label' => 'Électronique'],
                ['value' => 'mixte', 'label' => 'Mixte'],
            ];
            $devisesAutorisees = $user->pays
                ->paysDevises()
                ->with('devise')
                ->get()
                ->pluck('devise.code')
                ->toArray();

            return Inertia::render('Pos::VentePos/Edit', [
                'vente' => $data,
                'modePaiements' => $modePaiements,
                'devises' => $devisesAutorisees,
            ]);

        } catch (\Throwable $e) {
            log_error('VentePos', 'edit', $e->getMessage());
            return back()->with('error', trans('error_loading_form'));
        }
    }

    public function update(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * VALIDATION
             * =========================
             */
            $validated = $request->validate([
                'mode_paiement' => 'required|in:espece,electronique,mixte',

                'lignes' => 'required|array|min:1',
                'lignes.*.id' => 'required|exists:vente_pos_lignes,id',
                'lignes.*.quantite' => 'required|integer|min:1',
                'lignes.*.prix_unitaire' => 'required|numeric|min:0',
                'lignes.*.remise' => 'nullable|numeric|min:0',
                'lignes.*.taxe' => 'nullable|numeric|min:0',
                'lignes.*.deleted' => 'nullable|boolean',
            ]);

            /**
             * =========================
             * CHARGER LA VENTE
             * =========================
             */
            $vente = VentePos::with([
                'lignes',
                'sessionCaisse.caissier',
                'employe'
            ])
                ->lockForUpdate()
                ->findOrFail($id);

            /**
             * =========================
             * CONTRÔLES MÉTIER
             * =========================
             */
            if ($vente->statut !== config('appconstants.statut_vente_pos.en_attente')) {
                return back()->with('error', trans('vente_not_editable'));
            }

            if (
                !$vente->sessionCaisse ||
                $vente->sessionCaisse->statut !== config('appconstants.session_caisse_statut.ouverte')
            ) {
                return back()->with('error', trans('session_not_open'));
            }

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */
            // CAISSIER
            if ($user->hasRole(config('appconstants.role.caissier'))) {
                $employe = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe || $vente->employe_id !== $employe->id) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            // MANAGER
            if ($user->hasRole(config('appconstants.role.manager'))) {
                $manager = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (
                    !$manager ||
                    $vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }
            }

            /**
             * =========================
             * TRAITEMENT DES LIGNES
             * =========================
             */
            $totalVente = 0;
            $hasAtLeastOneLine = false;

            foreach ($validated['lignes'] as $ligneData) {

                /** @var VentePosLigne $ligne */
                $ligne = $vente->lignes->firstWhere('id', $ligneData['id']);

                if (!$ligne) {
                    return back()->with('error', trans('ligne_not_found'));
                }

                /**
                 * =========================
                 * SUPPRESSION DE LIGNE
                 * =========================
                 */
                if (!empty($ligneData['deleted']) && $ligneData['deleted'] === true) {

                    // Sécurité : pas de suppression si déjà remboursée
                    if (
                        ($ligne->rembourse_cents ?? 0) > 0 ||
                        ($ligne->quantite_remboursee ?? 0) > 0
                    ) {
                        return back()->with('error', trans('ligne_not_found'));
                    }

                    $ligne->delete();
                    continue;
                }

                $hasAtLeastOneLine = true;

                /**
                 * =========================
                 * MODIFICATION DE LIGNE
                 * =========================
                 */
                $prixUnitaire = MoneyService::toDatabase(
                    $ligneData['prix_unitaire'],
                    $vente->devise
                );

                $remise = MoneyService::toDatabase(
                    $ligneData['remise'] ?? 0,
                    $vente->devise
                );

                $taxe = MoneyService::toDatabase(
                    $ligneData['taxe'] ?? 0,
                    $vente->devise
                );

                $totalLigne = (($prixUnitaire * $ligneData['quantite']) - $remise) + $taxe;

                if ($totalLigne < 0) {
                    return back()->with('error', trans('vente_not_ligne'));
                }

                $ligne->update([
                    'quantite' => $ligneData['quantite'],
                    'prix_unitaire_cents' => $prixUnitaire,
                    'remise_cents' => $remise,
                    'taxe_cents' => $taxe,
                    'total_ligne_cents' => $totalLigne,
                ]);

                $totalVente += $totalLigne;
            }

            /**
             * =========================
             * SÉCURITÉ : AU MOINS 1 LIGNE
             * =========================
             */
            if (!$hasAtLeastOneLine) {
                return back()->with('error', trans('vente_not_ligne'));
            }

            /**
             * =========================
             * UPDATE VENTE
             * =========================
             */
            $vente->update([
                'mode_paiement' => $validated['mode_paiement'],
                'total_cents' => $totalVente,
            ]);

            DB::commit();

            return redirect()
                ->route('ventepos.index')
                ->with('success', trans('vente_updated_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'update', $e->getMessage());
            return back()->with('error', trans('vente_update_error'))->withInput();
        }
    }

    public function removeLine(string $venteId, string $ligneId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $vente = VentePos::with(['lignes', 'sessionCaisse'])
                ->lockForUpdate()
                ->findOrFail($venteId);

            // Vérifier statut
            if ($vente->statut !== config('appconstants.statut_vente_pos.en_attente')) {
                return back()->with('error', trans('vente_not_editable'));
            }

            // Vérifier session
            if ($vente->sessionCaisse->statut !== config('appconstants.session_caisse_statut.ouverte')) {
                return back()->with('error', trans('session_not_open'));
            }

            // Autorisation caissier
            $employe = Employe::where('users_id', auth()->id())
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe || $vente->employe_id !== $employe->id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            // Ligne
            $ligne = $vente->lignes->where('id', $ligneId)->first();

            if (!$ligne) {
                return back()->with('error', trans('ligne_not_found'));
            }

            // Suppression
            $ligne->delete();

            // Recalcul total
            $total = $vente->lignes()
                ->sum('total_ligne_cents');

            $vente->update([
                'total_cents' => $total,
            ]);

            DB::commit();
            return back()->with('success', trans('ligne_removed_success'));

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'removeLine', $e->getMessage());
            return back()->with('error', trans('ligne_remove_error'));
        }
    }
    public function validatePaiement(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Identifier le caissier
             * =========================
             */
            $employe = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.caissier'))
                ->first();

            if (!$employe) {
                return back()->with('error', trans('caissier_feature'));
            }

            /**
             * =========================
             * Charger la vente
             * =========================
             */
            $vente = VentePos::with([
                'sessionCaisse',
                'lignes.article'
            ])
                ->lockForUpdate()
                ->findOrFail($venteId);

            /**
             * =========================
             * Validation paiement (selon mode)
             * =========================
             */

            $rules = [];

            switch ($vente->mode_paiement) {

                case config('appconstants.mode_paiement.espece'):
                    $rules['montant_espece'] = ['nullable', 'numeric', 'min:0.01'];
                    $rules['montant_electronique'] = ['nullable', 'numeric', 'min:0'];
                    break;

                case config('appconstants.mode_paiement.electronique'):
                    $rules['montant_electronique'] = ['nullable', 'numeric', 'min:0.01'];
                    $rules['montant_espece'] = ['nullable', 'numeric', 'min:0'];
                    break;

                case config('appconstants.mode_paiement.mixte'):
                    $rules['montant_espece'] = ['required', 'numeric', 'min:0.01'];
                    $rules['montant_electronique'] = ['required', 'numeric', 'min:0.01'];
                    break;

                default:
                    return back()->with('error', trans('modepaiement_not_found'));
            }
            $validated = $request->validate($rules);



            /**
             * =========================
             * Contrôles métier
             * =========================
             */
            if ($vente->statut !== config('appconstants.statut_vente_pos.en_attente')) {
                return back()->with('error', trans('vente_not_waiting'));
            }

            if (
                !$vente->sessionCaisse ||
                $vente->sessionCaisse->statut !== config('appconstants.session_caisse_statut.ouverte')
            ) {
                return back()->with('error', trans('session_not_open'));
            }

            if ($vente->employe_id !== $employe->id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            if ($vente->total_cents <= 0) {
                return back()->with('error', trans('invalid_amount'));
            }

            /**
             * =========================
             * Conversion montants
             * =========================
             */
            $especeCents = MoneyService::toDatabase(
                $validated['montant_espece'] ?? 0,
                $vente->devise
            );

            $electroniqueCents = MoneyService::toDatabase(
                $validated['montant_electronique'] ?? 0,
                $vente->devise
            );

            /**
             * =========================
             * Contrôle mode paiement
             * =========================
             */
            if ($vente->mode_paiement === config('appconstants.mode_paiement.mixte')) {

                if (($especeCents + $electroniqueCents) !== $vente->total_cents) {
                    return back()->with('error', trans('invalid_payment_split'));
                }

                if ($especeCents <= 0 || $electroniqueCents <= 0) {
                    return back()->with('error', trans('invalid_payment_split'));
                }
            }

            /**
             * =========================
             * Décrémentation du stock
             * =========================
             */
            foreach ($vente->lignes as $ligne) {

                // Verrouillage pessimiste de l’article
                $article = Article::where('id', $ligne->article->id)
                    ->lockForUpdate()
                    ->first();

                if (!$article) {
                    return back()->with('error', trans('article_not_found'));
                }

                // Sécurité : le prix ne doit pas changer entre saisie et paiement
                if ($article->prix_cents !== $ligne->prix_unitaire_cents) {
                    return back()->with(
                        'error',
                        "Prix modifié pour l'article {$article->sku}"
                    );
                }

                // Sécurité stock
                if ($article->quantite_stock < $ligne->quantite) {
                    return back()->with(
                        'error',
                        "Stock insuffisant pour {$article->nom}"
                    );
                }

                /**
                 * =========================
                 * Mouvement de stock (VENTE)
                 * =========================
                 */
                StockMovementHelper::apply(
                    article: $article,
                    quantite: -$ligne->quantite,
                    typeMouvement: MouvementStock::TYPE_VENTE,
                    emplacementSourceId: $vente->points_vente_id,
                    employe: $employe,
                    reference: $vente->reference,
                    commentaire: 'Vente POS – validation paiement'
                );
            }

            /**
             * =========================
             * TRANSACTION (électronique uniquement)
             * =========================
             */
//            if ($electroniqueCents > 0) {
//
//                $transaction = Transactions::create([
//                    'uuid' => Str::uuid(),
//                    'payer_id' => $user->id,
//                    'marchand_id' => $vente->pointVente->marchand_id,
//                    'points_vente_id' => $vente->points_vente_id,
//                    'source_type' => Transactions::SOURCE_VENTE_POS,
//                    'source_id' => $vente->id,
//                    'fournisseur_paiement_id' => $request->input('fournisseur_paiement_id'),//TODO
//                    'montant_cents' => $electroniqueCents,
//                    'devise' => $vente->devise,
//                ]);
//
//                $transaction->markAsReussie();
//
//                /**
//                 * =========================
//                 * WALLET MARCHAND
//                 * =========================
//                 */
//                $wallet = Wallet::where('owner_type', 'marchand')
//                    ->where('owner_id', $vente->pointVente->marchand_id)
//                    ->whereHas('paysDevise', fn ($q) =>
//                        $q->where('code', $vente->devise)
//                    )
//                    ->lockForUpdate()
//                    ->firstOrFail();
//
//                $soldeAvant = $wallet->solde_cents;
//                $wallet->increment('solde_cents', $electroniqueCents);
//
//                WalletMouvement::create([
//                    'wallet_id' => $wallet->id,
//                    'type_mouvement' => WalletMouvement::TYPE_CREDIT,
//                    'montant_cents' => $electroniqueCents,
//                    'solde_avant_cents' => $soldeAvant,
//                    'solde_apres_cents' => $wallet->solde_cents,
//                    'emplacement_id' => $vente->points_vente_id,
//                    'users_id' => $user->id,
//                    'reference' => $vente->reference,
//                    'source_type' => Transactions::SOURCE_VENTE_POS,
//                    'source_id' => $transaction->id,
//                ]);
//            }

            /**
             * =========================
             * Validation du paiement
             * =========================
             */
            if ($vente->mode_paiement === config('appconstants.mode_paiement.mixte')) {
            $vente->update([
                'montant_espece_cents'       => $especeCents,
                'montant_non_espece_cents'   => $electroniqueCents,
                'statut'                     => config('appconstants.statut_vente_pos.validee'),
                'paid_at'                    => now(),
            ]);
        }else{
            $vente->update([
                'statut'                     => config('appconstants.statut_vente_pos.validee'),
                'paid_at'                    => now(),
            ]);
        }

            /**
             * =========================
             * Mise à jour caisse
             * =========================
             */
            $vente->sessionCaisse->increment(
                'total_encaisse_cents',
                $vente->total_cents
            );

            DB::commit();

            return redirect()
                ->route('ventepos.show', $vente->id)
                ->with('success', trans('paiement_success'));

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'validatePaiement', $e->getMessage());
            return back()->with('error', trans('paiement_error'))->withInput();
        }
    }

    public function cancelVente(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $validated = $request->validate([
                'motif' => 'required|string|max:255',
            ]);

            $vente = VentePos::with(['sessionCaisse', 'employe'])
                ->lockForUpdate()
                ->findOrFail($venteId);

            /**
             * =========================
             * CAS 1 — VENTE EN ATTENTE
             * =========================
             */
            if ($vente->statut === config('appconstants.statut_vente_pos.en_attente')) {

                // Caissier uniquement
                $employe = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.caissier'))
                    ->first();

                if (!$employe || $vente->employe_id !== $employe->id) {
                    return back()->with('error', trans('unauthorized_action'));
                }

                $vente->update([
                    'statut' => config('appconstants.statut_vente_pos.annulee'),
                    'cancelled_at' => now(),
                    'cancelled_by' => $user->id,
                    'cancel_motif' => $validated['motif'],
                ]);

                DB::commit();

                return back()->with('success', trans('vente_cancelled'));
            }

            /**
             * =========================
             * CAS 2 — VENTE VALIDÉE
             * =========================
             */
            if ($vente->statut === config('appconstants.statut_vente_pos.validee')) {

                // Manager obligatoire
                $manager = Employe::where('users_id', $user->id)
                    ->where('type_employe', config('appconstants.type_employe.manager'))
                    ->first();

                if (!$manager) {
                    return back()->with('error', trans('manager_feature'));
                }

                // Scope point de vente
                if (
                    $vente->sessionCaisse->caissier->points_vente_id
                    !== $manager->points_vente_id
                ) {
                    return back()->with('error', trans('unauthorized_action'));
                }

                // Mise à jour vente
                $vente->update([
                    'statut' => config('appconstants.statut_vente_pos.remboursee'),
                    'cancelled_at' => now(),
                    'cancelled_by' => $user->id,
                    'cancel_motif' => $validated['motif'],
                ]);

                // Impact caisse (REMBOURSEMENT) - éviter valeur négative
                $session = $vente->sessionCaisse;
                $currentEncaisse = (int) ($session->total_encaisse_cents ?? 0);
                $newEncaisse = max(0, $currentEncaisse - $vente->total_cents);
                $session->update(['total_encaisse_cents' => $newEncaisse]);

                DB::commit();

                return back()->with('success', trans('vente_cancelled'));
            }

            return back()->with('error', trans('vente_not_cancellable'));

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'cancelVente', $e->getMessage());
            return back()->with('error', trans('vente_not_cancellable'));
        }
    }
    public function refundVente(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Vérifier manager
             * =========================
             */
            $manager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$manager) {
                return back()->with('error', trans('manager_feature'));
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'montant' => 'required|numeric|min:0.01',
                'motif'   => 'required|string|max:255',
            ]);



            /**
             * =========================
             * Charger la vente
             * =========================
             */
            $vente = VentePos::with('sessionCaisse.caissier')
                ->lockForUpdate()
                ->findOrFail($venteId);

            if (!in_array($vente->statut, [
                config('appconstants.statut_vente_pos.validee'),
                config('appconstants.statut_vente_pos.partielle'),
            ])) {
                return back()->with('error', trans('vente_not_refundable'));
            }

            /**
             * =========================
             * Vérifier périmètre manager
             * =========================
             */
            if ($vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Conversion montant
             * =========================
             */
            $refundCents = MoneyService::toDatabase(
                $validated['montant'],
                $vente->devise
            );

            $resteRemboursable = $vente->total_cents - ($vente->total_rembourse_cents ?? 0);

            if ($refundCents <= 0 || $refundCents > $resteRemboursable) {
                return back()->with('error', trans('invalid_refund_amount'));
            }

            /**
             * =========================
             * Répartition selon mode paiement
             * =========================
             */
            $refundEspece = 0;
            $refundElectronique = 0;

            if ($vente->mode_paiement === config('appconstants.mode_paiement.espece')) {

                $max = $vente->montant_espece_cents - ($vente->rembourse_espece_cents ?? 0);
                if ($refundCents > $max) {
                    return back()->with('error', trans('refund_exceed_cash'));
                }
                $refundEspece = $refundCents;

            } elseif ($vente->mode_paiement === config('appconstants.mode_paiement.electronique')) {

                $max = $vente->montant_non_espece_cents - ($vente->rembourse_non_espece_cents ?? 0);
                if ($refundCents > $max) {
                    return back()->with('error', trans('refund_exceed_electronic'));
                }
                $refundElectronique = $refundCents;

            } else { // MIXTE

                $reste = $refundCents;

                $maxElec = $vente->montant_non_espece_cents - ($vente->rembourse_non_espece_cents ?? 0);
                $refundElectronique = min($reste, $maxElec);
                $reste -= $refundElectronique;

                if ($reste > 0) {
                    $maxCash = $vente->montant_espece_cents - ($vente->rembourse_espece_cents ?? 0);
                    if ($reste > $maxCash) {
                        return back()->with('error', trans('refund_exceed_payment'));
                    }
                    $refundEspece = $reste;
                }
            }

            /**
             * =========================
             * Mise à jour vente
             * =========================
             */
            $nouveauTotal = ($vente->total_rembourse_cents ?? 0) + $refundCents;

            $vente->update([
                'total_rembourse_cents'       => $nouveauTotal,
                'rembourse_espece_cents'      => ($vente->rembourse_espece_cents ?? 0) + $refundEspece,
                'rembourse_non_espece_cents'  => ($vente->rembourse_non_espece_cents ?? 0) + $refundElectronique,
                'refund_at'                   => now(),
                'refund_by'                   => $user->id,
                'refund_motif'                => $validated['motif'],
                'statut' => $nouveauTotal >= $vente->total_cents
                    ? config('appconstants.statut_vente_pos.remboursee')
                    : config('appconstants.statut_vente_pos.partielle'),
            ]);

            /**
             * =========================
             * Impact caisse
             * =========================
             */
            $vente->sessionCaisse->decrement(
                'total_encaisse_cents',
                $refundCents
            );

            DB::commit();

            return back()->with('success', trans('vente_refund_success'));

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'refundVente', $e->getMessage());
            return back()->with('error', trans('vente_refund_error'));
        }
    }

    public function refundVenteByLines(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Vérifier manager
             * =========================
             */
            $manager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$manager) {
                return back()->with('error', trans('manager_feature'));
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'lignes' => 'required|array|min:1',
                'lignes.*.ligne_id' => 'required|exists:vente_pos_lignes,id',
                'lignes.*.quantite' => 'required|integer|min:1',
                'motif' => 'required|string|max:255',
            ]);

            /**
             * =========================
             * Charger vente + lignes + articles
             * =========================
             */
            $vente = VentePos::with([
                'sessionCaisse.caissier',
                'lignes.article'
            ])->lockForUpdate()->findOrFail($venteId);

            if (!in_array($vente->statut, [
                config('appconstants.statut_vente_pos.validee'),
                config('appconstants.statut_vente_pos.partielle'),
            ])) {
                return back()->with('error', trans('vente_not_refundable'));
            }

            /**
             * =========================
             * Vérifier périmètre manager
             * =========================
             */
            if ($vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            $refundTotalCents = 0;
            $refundEspece = 0;
            $refundElectronique = 0;

            /**
             * =========================
             * Traitement lignes
             * =========================
             */
            foreach ($validated['lignes'] as $input) {

                $ligne = $vente->lignes->firstWhere('id', $input['ligne_id']);

                if (!$ligne || !$ligne->article) {
                    return back()->with('error', trans('ligne_not_found'));
                }

                $quantiteRestante = $ligne->quantite - $ligne->quantite_remboursee;

                if ($input['quantite'] > $quantiteRestante) {
                    return back()->with('error', trans('invalid_refund_quantity'));
                }

                /**
                 * Calcul montant ligne
                 */
                $refundCents = $input['quantite'] * $ligne->prix_unitaire_cents;

                /**
                 * Sécurité plafond
                 */
                $maxRefundable = $ligne->total_ligne_cents - $ligne->rembourse_cents;

                if ($refundCents > $maxRefundable) {
                    return back()->with('error', trans('invalid_refund_amount'));
                }

                /**
                 * =========================
                 * Retour stock article
                 * =========================
                 */
                $ligne->article->increment(
                    'quantite_stock',
                    $input['quantite']
                );

                /**
                 * =========================
                 * Mise à jour ligne
                 * =========================
                 */
                $ligne->update([
                    'quantite_remboursee' => $ligne->quantite_remboursee + $input['quantite'],
                    'rembourse_cents' => $ligne->rembourse_cents + $refundCents,
                    'rembourse_at' => now(),
                    'rembourse_by' => $user->id,
                ]);

                /**
                 * =========================
                 * Répartition paiement
                 * =========================
                 */
                if ($vente->mode_paiement === config('appconstants.mode_paiement.espece')) {
                    $refundEspece += $refundCents;
                } elseif ($vente->mode_paiement === config('appconstants.mode_paiement.electronique')) {
                    $refundElectronique += $refundCents;
                } else {
                    // mixte → prorata
                    $ratioEspece = $vente->montant_espece_cents / $vente->total_cents;
                    $refundEspece += (int) round($refundCents * $ratioEspece);
                    $refundElectronique += $refundCents - (int) round($refundCents * $ratioEspece);
                }

                $refundTotalCents += $refundCents;
            }

            /**
             * =========================
             * Mise à jour vente
             * =========================
             */
            $vente->update([
                'total_rembourse_cents' => $vente->total_rembourse_cents + $refundTotalCents,
                'rembourse_espece_cents' => $vente->rembourse_espece_cents + $refundEspece,
                'rembourse_non_espece_cents' => $vente->rembourse_non_espece_cents + $refundElectronique,
                'refund_at' => now(),
                'refund_by' => $user->id,
                'refund_motif' => $validated['motif'],
            ]);

            /**
             * =========================
             * Déterminer statut
             * =========================
             */
            $reste = $vente->total_cents - $vente->total_rembourse_cents;

            $vente->update([
                'statut' => $reste <= 0
                    ? config('appconstants.statut_vente_pos.remboursee')
                    : config('appconstants.statut_vente_pos.partielle'),
            ]);

            /**
             * =========================
             * Impact caisse
             * =========================
             */
            $vente->sessionCaisse->decrement(
                'total_encaisse_cents',
                $refundTotalCents
            );

            DB::commit();

            return back()->with('success', trans('vente_refund_success'));

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'refundVenteByLines', $e->getMessage());
            return back()->with('error', trans('vente_refund_error'));
        }
    }

    public function refundVenteMixte(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Vérifier manager
             * =========================
             */
            $manager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$manager) {
                return back()->with('error', trans('manager_feature'));
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'espece' => 'nullable|numeric|min:0',
                'electronique' => 'nullable|numeric|min:0',
                'motif' => 'required|string|max:255',
            ]);

            /**
             * =========================
             * Charger vente
             * =========================
             */
            $vente = VentePos::with('sessionCaisse.caissier')
                ->lockForUpdate()
                ->findOrFail($venteId);


            if (!in_array($vente->statut, [
                config('appconstants.statut_vente_pos.validee'),
                config('appconstants.statut_vente_pos.partielle'),
            ])) {
                return back()->with('error', trans('vente_not_refundable'));
            }
            if ($vente->mode_paiement != config('appconstants.mode_paiement.mixte')){
                return back()->with('error', trans('vente_not_mixte'));
            }

            /**
             * =========================
             * Vérifier périmètre manager
             * =========================
             */
            if ($vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * Conversion montants
             * =========================
             */
            $refundEspece = MoneyService::toDatabase(
                $validated['espece'] ?? 0,
                $vente->devise
            );

            $refundElectronique = MoneyService::toDatabase(
                $validated['electronique'] ?? 0,
                $vente->devise
            );

            if ($refundEspece <= 0 && $refundElectronique <= 0) {
                return back()->with('error', trans('invalid_refund_amount'));
            }

            /**
             * =========================
             * Vérification plafonds
             * =========================
             */
            $maxEspece = $vente->montant_espece_cents - $vente->rembourse_espece_cents;
            $maxElectronique = $vente->montant_non_espece_cents - $vente->rembourse_non_espece_cents;

            if ($refundEspece > $maxEspece || $refundElectronique > $maxElectronique) {
                return back()->with('error', trans('refund_exceed_payment'));
            }

            $totalRefund = $refundEspece + $refundElectronique;

            /**
             * =========================
             * Mise à jour vente
             * =========================
             */
            $vente->update([
                'rembourse_espece_cents' => $vente->rembourse_espece_cents + $refundEspece,
                'rembourse_non_espece_cents' => $vente->rembourse_non_espece_cents + $refundElectronique,
                'total_rembourse_cents' => $vente->total_rembourse_cents + $totalRefund,
                'refund_at' => now(),
                'refund_by' => $user->id,
                'refund_motif' => $validated['motif'],
            ]);

            /**
             * =========================
             * Déterminer statut final
             * =========================
             */
            $reste = $vente->total_cents - $vente->total_rembourse_cents;

            $vente->update([
                'statut' => $reste <= 0
                    ? config('appconstants.statut_vente_pos.remboursee')
                    : config('appconstants.statut_vente_pos.partielle'),
            ]);

            /**
             * =========================
             * Impact caisse
             * =========================
             */
            $vente->sessionCaisse->decrement(
                'total_encaisse_cents',
                $totalRefund
            );

            DB::commit();

            return back()->with('success', trans('vente_refund_success'));

        } catch (ValidationException $e) {

            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'refundVenteMixte', $e->getMessage());
            return back()->with('error', trans('vente_refund_error'));
        }
    }
    public function refundVenteByQuantite(Request $request, int $venteId)
{
    DB::beginTransaction();

    try {
        $user = auth()->user();

        /**
         * =========================
         * Vérifier manager
         * =========================
         */
        $manager = Employe::where('users_id', $user->id)
            ->where('type_employe', config('appconstants.type_employe.manager'))
            ->first();

        if (!$manager) {
            return back()->with('error', trans('manager_feature'));
        }

        /**
         * =========================
         * Validation
         * =========================
         */
        $validated = $request->validate([
            'lignes' => 'required|array|min:1',
            'lignes.*.ligne_id' => 'required|exists:vente_pos_lignes,id',
            'lignes.*.quantite' => 'required|integer|min:1',
            'motif' => 'required|string|max:255',
        ]);

        /**
         * =========================
         * Charger vente + lignes
         * =========================
         */
        $vente = VentePos::with([
            'sessionCaisse.caissier',
            'lignes.article' // relation Article requise
        ])->lockForUpdate()->findOrFail($venteId);

        if (!in_array($vente->statut, [
            config('appconstants.statut_vente_pos.validee'),
            config('appconstants.statut_vente_pos.partielle'),
        ])) {
            return back()->with('error', trans('vente_not_refundable'));
        }

        /**
         * =========================
         * Vérifier périmètre manager
         * =========================
         */
        if ($vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id) {
            return back()->with('error', trans('unauthorized_action'));
        }

        $totalRefundCents = 0;

        /**
         * =========================
         * Traitement lignes
         * =========================
         */
        foreach ($validated['lignes'] as $input) {

            $ligne = $vente->lignes->firstWhere('id', $input['ligne_id']);

            if (!$ligne) {
                return back()->with('error', trans('ligne_not_found'));
            }

            $quantiteRestante = $ligne->quantite - ($ligne->quantite_remboursee ?? 0);

            if ($input['quantite'] > $quantiteRestante) {
                return back()->with('error', trans('invalid_refund_quantity'));
            }

            $refundCents = $input['quantite'] * $ligne->prix_unitaire_cents;

            /**
             * -------------------------
             * Mise à jour ligne
             * -------------------------
             */
            $ligne->update([
                'quantite_remboursee' => ($ligne->quantite_remboursee ?? 0) + $input['quantite'],
                'rembourse_cents' => ($ligne->rembourse_cents ?? 0) + $refundCents,
                'rembourse_at' => now(),
                'rembourse_by' => $user->id,
            ]);

            /**
             * =========================
             * Retour stock via helper
             * =========================
             */
            if ($ligne->article) {

                StockMovementHelper::apply(
                    article: $ligne->article,
                    quantite: $input['quantite'], // POSITIF = retour en stock
                    typeMouvement: MouvementStock::TYPE_REMBOURSEMENT,
                    emplacementSourceId: null,
                    emplacementDestinationId: $vente->points_vente_id,
                    employe: $manager,
                    reference: $vente->reference,
                    commentaire: 'Remboursement POS – retour article'
                );
            }

            $totalRefundCents += $refundCents;
        }

        /**
         * =========================
         * Répartition par mode paiement
         * =========================
         */
        $refundEspece = 0;
        $refundElectronique = 0;

        if ($vente->mode_paiement === config('appconstants.mode_paiement.espece')) {
            $refundEspece = $totalRefundCents;
        } elseif ($vente->mode_paiement === config('appconstants.mode_paiement.electronique')) {
            $refundElectronique = $totalRefundCents;
        } else {
            // MIXTE → prorata
            $ratioEspece = $vente->montant_espece_cents / $vente->total_cents;
            $refundEspece = (int) round($totalRefundCents * $ratioEspece);
            $refundElectronique = $totalRefundCents - $refundEspece;
        }

        /**
         * =========================
         * Mise à jour vente
         * =========================
         */
        $vente->update([
            'rembourse_espece_cents' => ($vente->rembourse_espece_cents ?? 0) + $refundEspece,
            'rembourse_non_espece_cents' => ($vente->rembourse_non_espece_cents ?? 0) + $refundElectronique,
            'total_rembourse_cents' => ($vente->total_rembourse_cents ?? 0) + $totalRefundCents,
            'refund_at' => now(),
            'refund_by' => $user->id,
            'refund_motif' => $validated['motif'],
        ]);

        /**
         * =========================
         * Déterminer statut final
         * =========================
         */
        $reste = $vente->total_cents - $vente->total_rembourse_cents;

        $vente->update([
            'statut' => $reste <= 0
                ? config('appconstants.statut_vente_pos.remboursee')
                : config('appconstants.statut_vente_pos.partielle'),
        ]);

        /**
         * =========================
         * Impact caisse
         * =========================
         */
        $vente->sessionCaisse->decrement(
            'total_encaisse_cents',
            $totalRefundCents
        );

        DB::commit();

        return back()->with('success', trans('vente_refund_success'));

    } catch (ValidationException $e) {
        DB::rollBack();
        return back()->withErrors($e->errors())->withInput();

    } catch (\Throwable $e) {
        DB::rollBack();
        log_error('VentePos', 'refundVenteByQuantite', $e->getMessage());
        return back()->with('error', trans('vente_refund_error'));
    }
}

    public function refundVenteMixteQuantite(Request $request, int $venteId)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            /**
             * =========================
             * Vérifier manager
             * =========================
             */
            $manager = Employe::where('users_id', $user->id)
                ->where('type_employe', config('appconstants.type_employe.manager'))
                ->first();

            if (!$manager) {
                return back()->with('error', trans('manager_feature'));
            }

            /**
             * =========================
             * Validation
             * =========================
             */
            $validated = $request->validate([
                'lignes' => 'nullable|array',
                'lignes.*.ligne_id' => 'required|exists:vente_pos_lignes,id',
                'lignes.*.quantite' => 'required|integer|min:1',

                'montant_libre' => 'nullable|numeric|min:0',
                'motif' => 'required|string|max:255',
            ]);

            /**
             * =========================
             * Charger vente + lignes
             * =========================
             */
            $vente = VentePos::with([
                'sessionCaisse.caissier',
                'lignes.article'
            ])->lockForUpdate()->findOrFail($venteId);

            if (!in_array($vente->statut, [
                config('appconstants.statut_vente_pos.validee'),
                config('appconstants.statut_vente_pos.partielle'),
            ])) {
                return back()->with('error', trans('vente_not_refundable'));
            }

            /**
             * =========================
             * Vérifier périmètre
             * =========================
             */
            if ($vente->sessionCaisse->caissier->points_vente_id !== $manager->points_vente_id) {
                return back()->with('error', trans('unauthorized_action'));
            }

            $totalRefundLignes = 0;

            /**
             * =========================
             * Remboursement par quantités
             * =========================
             */
            if (!empty($validated['lignes'])) {
                foreach ($validated['lignes'] as $input) {

                    $ligne = $vente->lignes->firstWhere('id', $input['ligne_id']);

                    if (!$ligne) {
                        return back()->with('error', trans('ligne_not_found'));
                    }

                    $quantiteRestante = $ligne->quantite - ($ligne->quantite_remboursee ?? 0);

                    if ($input['quantite'] > $quantiteRestante) {
                        return back()->with('error', trans('invalid_refund_quantity'));
                    }

                    $refundCents = $input['quantite'] * $ligne->prix_unitaire_cents;

                    /**
                     * Mise à jour ligne
                     */
                    $ligne->update([
                        'quantite_remboursee' => ($ligne->quantite_remboursee ?? 0) + $input['quantite'],
                        'rembourse_cents' => ($ligne->rembourse_cents ?? 0) + $refundCents,
                        'rembourse_at' => now(),
                        'rembourse_by' => $user->id,
                    ]);

                    /**
                     * =========================
                     * Retour stock via helper
                     * =========================
                     */
                    if ($ligne->article) {

                        StockMovementHelper::apply(
                            article: $ligne->article,
                            quantite: $input['quantite'], // POSITIF = retour stock
                            typeMouvement: MouvementStock::TYPE_REMBOURSEMENT,
                            emplacementSourceId: null,
                            emplacementDestinationId: $vente->points_vente_id,
                            employe: $manager,
                            reference: $vente->reference,
                            commentaire: 'Remboursement POS (mixte + quantité)'
                        );
                    }

                    $totalRefundLignes += $refundCents;
                }
            }

            /**
             * =========================
             * Remboursement libre
             * =========================
             */
            $montantLibreCents = MoneyService::toDatabase(
                $validated['montant_libre'] ?? 0,
                $vente->devise
            );

            $totalRefund = $totalRefundLignes + $montantLibreCents;

            if ($totalRefund <= 0) {
                return back()->with('error', trans('invalid_refund_amount'));
            }

            if (($vente->total_rembourse_cents + $totalRefund) > $vente->total_cents) {
                return back()->with('error', trans('refund_exceeds_total'));
            }

            /**
             * =========================
             * Répartition selon mode paiement
             * =========================
             */
            $refundEspece = 0;
            $refundElectronique = 0;

            if ($vente->mode_paiement === config('appconstants.mode_paiement.espece')) {
                $refundEspece = $totalRefund;
            } elseif ($vente->mode_paiement === config('appconstants.mode_paiement.electronique')) {
                $refundElectronique = $totalRefund;
            } else {
                // MIXTE → prorata
                $ratioEspece = $vente->montant_espece_cents / $vente->total_cents;
                $refundEspece = (int) round($totalRefund * $ratioEspece);
                $refundElectronique = $totalRefund - $refundEspece;
            }

            /**
             * =========================
             * Mise à jour vente
             * =========================
             */
            $vente->update([
                'total_rembourse_cents' => ($vente->total_rembourse_cents ?? 0) + $totalRefund,
                'rembourse_espece_cents' => ($vente->rembourse_espece_cents ?? 0) + $refundEspece,
                'rembourse_non_espece_cents' => ($vente->rembourse_non_espece_cents ?? 0) + $refundElectronique,
                'rembourse_libre_cents' => ($vente->rembourse_libre_cents ?? 0) + $montantLibreCents,
                'refund_at' => now(),
                'refund_by' => $user->id,
                'refund_motif' => $validated['motif'],
            ]);

            /**
             * =========================
             * Statut final
             * =========================
             */
            $reste = $vente->total_cents - $vente->total_rembourse_cents;

            $vente->update([
                'statut' => $reste <= 0
                    ? config('appconstants.statut_vente_pos.remboursee')
                    : config('appconstants.statut_vente_pos.partielle'),
            ]);

            /**
             * =========================
             * Impact caisse
             * =========================
             */
            $vente->sessionCaisse->decrement(
                'total_encaisse_cents',
                $totalRefund
            );

            DB::commit();

            return back()->with('success', trans('vente_refund_success'));

        } catch (ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('VentePos', 'refundVenteMixteQuantite', $e->getMessage());
            return back()->with('error', trans('vente_refund_error'));
        }
    }



}
