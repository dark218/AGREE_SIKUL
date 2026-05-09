<?php

namespace Modules\GestionStock\Http\Controllers;

use App\Helpers\StockMovementHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Modules\GestionStock\Entities\Inventaire;
use Modules\GestionStock\Entities\MouvementStock;
use Modules\Parametrage\Entities\Pays;

class InventaireController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:inventaire-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:inventaire-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:inventaire-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:inventaire-validate', ['only' => ['validateInventaire', 'cancel']]);
    }

    /* =====================================================
     * LISTE DES INVENTAIRES
     * ===================================================== */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $paysCurrent = $user->pays_id;

            $query = Inventaire::with([
                'emplacement',
                'creePar.user',
                'validePar.user',
                'lignes.article',
            ])->orderByDesc('created_at');

            $filters = [
                'pays_id' => $request->input('pays_id'),
                'statut' => $request->input('statut'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
                'emplacement_id' => $request->input('emplacement_id'),
            ];

            if ($filters['statut']) {
                $query->where('statut', $filters['statut']);
            }

            if ($filters['date_debut']) {
                $query->whereDate('date_inventaire', '>=', $filters['date_debut']);
            }

            if ($filters['date_fin']) {
                $query->whereDate('date_inventaire', '<=', $filters['date_fin']);
            }

            if ($filters['emplacement_id']) {
                $query->where('emplacement_id', $filters['emplacement_id']);
            }

            /* ===============================
             * VISIBILITÉ SELON RÔLE
             * =============================== */
            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {

                $paysId = $user->hasRole(config('appconstants.role.superadmin'))
                    ? $filters['pays_id']
                    : $paysCurrent;

                if ($paysId) {
                    $pointsVenteIds = PointVente::whereHas(
                        'marchand.proprietaire',
                        fn ($q) => $q->where('pays_id', $paysId)
                    )->pluck('id');

                    $query->whereIn('emplacement_id', $pointsVenteIds);
                }

            } elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $marchand = $user->marchand;
                if (!$marchand) {
                    return back()->with('error', trans('marchand_not_rattach'));
                }

                $query->whereHas('emplacement', fn ($q) =>
                $q->where('marchand_id', $marchand->id)
                );

            } else {

                $employe = $user->employePrincipal;
                if (!$employe) {
                    return back()->with('error', trans('employe_not_rattach'));
                }

                $query->where('emplacement_id', $employe->points_vente_id);
            }

            $inventaires = $query->paginate(10)->withQueryString();

            /* ===============================
             * FILTRES UI
             * =============================== */
            $pointsVenteOptions = collect();

            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {

                $paysId = $user->hasRole(config('appconstants.role.superadmin'))
                    ? $filters['pays_id']
                    : $paysCurrent;

                if ($paysId) {
                    $pointsVenteOptions = PointVente::whereHas(
                        'marchand.proprietaire',
                        fn ($q) => $q->where('pays_id', $paysId)
                    )
                        ->whereNull('parent_points_vente_id')
                        ->orderBy('nom')
                        ->get();
                }

            } elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $pointsVenteOptions = PointVente::where(
                    'marchand_id',
                    $user->marchand->id
                )->orderBy('nom')->get();
            }

            $useDashboardLayout = $user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
                config('appconstants.role.marchand'),
            ]);

            return Inertia::render('GestionStock::Inventaire/Index', [
                'inventaires' => $inventaires,
                'pays' => Pays::select('id', 'libelle')->get(),
                'paysCurrent' => $paysCurrent,
                'filters' => $filters,
                'pointsVenteOptions' => $pointsVenteOptions->map(fn ($pv) => [
                    'value' => $pv->id,
                    'label' => $pv->nom,
                ]),
                'statuts' => [
                    ['value' => Inventaire::STATUT_BROUILLON, 'label' => 'Brouillon'],
                    ['value' => Inventaire::STATUT_VALIDE, 'label' => 'Validé'],
                    ['value' => Inventaire::STATUT_ANNULE, 'label' => 'Annulé'],
                ],
                'useDashboardLayout' => $useDashboardLayout,
                'pageTitle' => trans('modules.business.inventaire.title'),
            ]);

        } catch (\Throwable $e) {
            log_error('Inventaire', 'index', $e->getMessage());
            return back()->with('error', 'Erreur lors du chargement des inventaires');
        }
    }

    /* =====================================================
     * FORMULAIRE CRÉATION
     * ===================================================== */
    public function create()
    {
        try {
            $user = auth()->user();
            $pointsVente = collect();

            if ($user->hasRole([
                config('appconstants.role.admin'),
                config('appconstants.role.superadmin'),
            ])) {

                $pointsVente = PointVente::whereNull('parent_points_vente_id')
                    ->where('statut', config('appconstants.pointvente_statut.actif'))
                    ->orderBy('nom')
                    ->get();

            } elseif ($user->hasRole(config('appconstants.role.marchand'))) {

                $pointsVente = PointVente::where(
                    'marchand_id',
                    $user->marchand->id
                )->orderBy('nom')->get();

            } elseif ($employe = $user->employePrincipal) {

                $pointsVente = PointVente::where(
                    'id',
                    $employe->points_vente_id
                )->get();
            }
            $articles = Article::whereIn('points_vente_id', $pointsVente->pluck('id'))
                ->with('pointVente:id,nom')
                ->orderBy('nom')
                ->get()
                ->map(fn ($a) => [
                    'value' => $a->id,
                    'label' => "({$a->marque}) {$a->nom} ",
                    'sku' => $a->sku,
                    'stock' => $a->quantite_stock,
                    'point_vente_id' => $a->points_vente_id,
                    'point_vente_nom' => $a->pointVente->nom ?? '',
                ]);

            return Inertia::render('GestionStock::Inventaire/Create', [
                'pointsVente' => $pointsVente->map(fn ($pv) => [
                    'value' => $pv->id,
                    'label' => $pv->nom,
                ]),
                'articles' => $articles,
                'pageTitle' => trans('modules.business.inventaire.create'),
            ]);

        } catch (\Throwable $e) {
            log_error('Inventaire', 'create', $e->getMessage());
            return back()->with('error',trans('error_loading_form'));
        }
    }

    /* =====================================================
     * ENREGISTREMENT INVENTAIRE
     * ===================================================== */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $validated = $request->validate([
                'emplacement_id' => 'required|exists:points_vente,id',
                'date_inventaire' => 'required|date',
                'commentaire' => 'nullable|string|max:1000',
                'lignes' => 'required|array|min:1',
                'lignes.*.article_id' => 'required|exists:articles,id',
                'lignes.*.quantite_comptabilisee' => 'required|integer|min:0',
                'lignes.*.commentaire' => 'nullable|string|max:255',
            ]);

            $exists = Inventaire::where('emplacement_id', $validated['emplacement_id'])
                ->whereDate('date_inventaire', $validated['date_inventaire'])
                ->where('statut', '!=', Inventaire::STATUT_ANNULE)
                ->whereNull('deleted_at')
                ->exists();

            if ($exists) {
                return back()->with('error', trans('inventaire_deja_existant_pour_cette_date'));
            }

            if (collect($validated['lignes'])->pluck('article_id')->duplicates()->isNotEmpty()) {
                return back()->with('error', trans('article_doublon'));
            }

            $emplacement = PointVente::findOrFail($validated['emplacement_id']);
            if (!$this->canAccessEmplacement($user, $emplacement)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            $inventaire = Inventaire::create([
                'emplacement_id' => $emplacement->id,
                'date_inventaire' => $validated['date_inventaire'],
                'commentaire' => $validated['commentaire'],
                'cree_par' => $user->employePrincipal?->id,
                'statut' => Inventaire::STATUT_BROUILLON,
            ]);

            foreach ($validated['lignes'] as $ligne) {

                $article = Article::where('id', $ligne['article_id'])
                    ->where('points_vente_id', $emplacement->id)
                    ->lockForUpdate()
                    ->first();

                if (!$article) {
                    return back()->with('error', trans('article_not_found'));
                }

                $inventaire->lignes()->create([
                    'article_id' => $article->id,
                    'quantite_systeme' => $article->quantite_stock,
                    'quantite_comptabilisee' => $ligne['quantite_comptabilisee'],
                    'ecart' => $ligne['quantite_comptabilisee'] - $article->quantite_stock,
                    'commentaire' => $ligne['commentaire'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('inventaire.index')
                ->with('success', trans('inventaire_success'));

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('Inventaire', 'store', $e->getMessage());
            return back()->with('error', trans('inventaire_error'));
        }
    }
    public function show(int $id)
    {
        try {
            $inventaire = Inventaire::with([
                'emplacement',
                'creePar.user',
                'validePar.user',
                'lignes.article',
            ])->findOrFail($id);

            if (!$this->canAccessInventaire(auth()->user(), $inventaire)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            return Inertia::render('GestionStock::Inventaire/Show', [
                'inventaire' => [
                    'id' => $inventaire->id,
                    'date_inventaire' => $inventaire->date_inventaire,
                    'statut' => $inventaire->statut,
                    'commentaire' => $inventaire->commentaire,
                    'date_validation' => $inventaire->date_validation,
                    'emplacement' => [
                        'id' => $inventaire->emplacement->id,
                        'nom' => $inventaire->emplacement->nom,
                    ],
                    'cree_par' => $inventaire->creePar?->user?->fullName() ?? '',
                    'valide_par' => $inventaire->validePar?->user?->fullName() ?? '',
                    'lignes' => $inventaire->lignes->map(fn ($ligne) => [
                        'id' => $ligne->id,
                        'article' => [
                            'id' => $ligne->article->id,
                            'marque' => $ligne->article->marque,
                            'nom' => $ligne->article->nom,
                            'sku' => $ligne->article->sku,
                        ],
                        'quantite_systeme' => $ligne->quantite_systeme,
                        'quantite_comptabilisee' => $ligne->quantite_comptabilisee,
                        'ecart' => $ligne->ecart,
                        'commentaire' => $ligne->commentaire,
                    ]),
                ],
                'pageTitle' => trans('modules.business.intentaire.show'),
            ]);

        } catch (\Throwable $e) {
            log_error('Inventaire', 'show', $e->getMessage());
            return back()->with('error', trans('error_loading_form'));
        }
    }
    public function edit(int $id)
    {
        try {
            $inventaire = Inventaire::with([
                'emplacement',
                'lignes.article',
            ])->findOrFail($id);

            if ($inventaire->statut !== Inventaire::STATUT_BROUILLON) {
                return back()->with('error', trans('inventaire_not_editable'));
            }

            if (!$this->canAccessInventaire(auth()->user(), $inventaire)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            return Inertia::render('GestionStock::Inventaire/Edit', [
                'inventaire' => [
                    'id' => $inventaire->id,
                    'date_inventaire' => $inventaire->date_inventaire,
                    'commentaire' => $inventaire->commentaire,
                    'emplacement' => [
                        'id' => $inventaire->emplacement->id,
                        'nom' => $inventaire->emplacement->nom,
                    ],
                ],
                'pageTitle' => trans('modules.business.intentaire.edit'),
                'lignes' => $inventaire->lignes->map(fn ($ligne) => [
                    'id' => $ligne->id,
                    'article_id' => $ligne->article_id,
                    'article_nom' => $ligne->article->nom,
                    'article_marque' => $ligne->article->marque,
                    'sku' => $ligne->article->sku,
                    'quantite_systeme' => $ligne->quantite_systeme,
                    'quantite_comptabilisee' => $ligne->quantite_comptabilisee,
                    'commentaire' => $ligne->commentaire,
                ]),
            ]);

        } catch (\Throwable $e) {
            log_error('Inventaire', 'edit', $e->getMessage());
            return back()->with('error', trans('error_loading_form'));
        }
    }


    public function update(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $inventaire = Inventaire::with('lignes')->lockForUpdate()->findOrFail($id);

            if ($inventaire->statut !== Inventaire::STATUT_BROUILLON) {
                return back()->with('error', trans('inventaire_not_editable'));
            }

            if (!$this->canAccessInventaire(auth()->user(), $inventaire)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            $validated = $request->validate([
                'date_inventaire' => 'required|date',
                'commentaire' => 'nullable|string|max:1000',
                'lignes' => 'required|array|min:1',
                'lignes.*.id' => 'required|exists:inventaire_lignes,id',
                'lignes.*.quantite_comptabilisee' => 'required|integer|min:0',
                'lignes.*.commentaire' => 'nullable|string|max:255',
            ]);

            $inventaire->update([
                'date_inventaire' => $validated['date_inventaire'],
                'commentaire' => $validated['commentaire'],
            ]);

            foreach ($validated['lignes'] as $ligneInput) {

                $ligne = $inventaire->lignes->firstWhere('id', $ligneInput['id']);

                if (!$ligne) {
                    return back()->with('error', trans('ligne_not_found'));
                }

                $nouvelleQuantite = (int) $ligneInput['quantite_comptabilisee'];

                $ligne->update([
                    'quantite_comptabilisee' => $nouvelleQuantite,
                    'ecart' => $nouvelleQuantite - $ligne->quantite_systeme,
                    'commentaire' => $ligneInput['commentaire'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('inventaire.show', $inventaire->id)
                ->with('success', trans('inventaire_updated_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('Inventaire', 'update', $e->getMessage());
            return back()->with('error', trans('inventaire_error_edition'));
        }
    }
    /* =====================================================
     * VALIDATION INVENTAIRE (SANS IMPACT STOCK)
     * ===================================================== */
    public function validateInventaire(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();

            $inventaire = Inventaire::with([
                'lignes.article',
                'emplacement'
            ])->lockForUpdate()->findOrFail($id);

            /**
             * =========================
             * CONTRÔLE D’ACCÈS
             * =========================
             */
            if (!$this->canValidateInventaire($user, $inventaire)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            /**
             * =========================
             * STATUT VALIDE ?
             * =========================
             */
            if ($inventaire->statut !== Inventaire::STATUT_BROUILLON) {
                return back()->with('error', trans('inventaire_error'));
            }

            if ($inventaire->lignes->isEmpty()) {
                return back()->with('error', trans('inventaire_empty'));
            }

            /**
             * =========================
             * VALIDATION INPUT
             * =========================
             */
            $validated = $request->validate([
                'commentaire' => 'nullable|string|max:1000',
            ]);

            $employe = $user->employePrincipal;

            /**
             * =========================
             * TRAITEMENT DES LIGNES
             * =========================
             */
            foreach ($inventaire->lignes as $ligne) {

                $article = Article::where('id', $ligne->article_id)
                    ->where('points_vente_id', $inventaire->emplacement_id)
                    ->lockForUpdate()
                    ->first();

                if (!$article) {
                    return back()->with(
                        'error',
                        "Article {$ligne->article_id} introuvable pour cet emplacement"
                    );
                }

                /**
                 * Calcul écart
                 * ecart = compté - système
                 */
                $ecart = $ligne->quantite_comptabilisee - $article->quantite_stock;

                // Pas de mouvement si aucun écart
                if ($ecart === 0) {
                    continue;
                }

                /**
                 * =========================
                 * MOUVEMENT DE STOCK (AJUSTEMENT)
                 * =========================
                 */
                StockMovementHelper::apply(
                    article: $article,
                    quantite: $ecart, // + ou -
                    typeMouvement: MouvementStock::TYPE_CORRECTION,
                    emplacementSourceId: $ecart < 0 ? $inventaire->emplacement_id : null,
                    emplacementDestinationId: $ecart > 0 ? $inventaire->emplacement_id : null,
                    employe: $employe,
                    reference: 'INV-' . $inventaire->id,
                    commentaire: 'Ajustement inventaire'
                );
            }

            /**
             * =========================
             * VALIDATION INVENTAIRE
             * =========================
             */
            $inventaire->update([
                'statut' => Inventaire::STATUT_VALIDE,
                'valide_par' => $employe?->id,
                'date_validation' => now(),
                'commentaire' => $validated['commentaire'] ?? $inventaire->commentaire,
            ]);

            DB::commit();

            return redirect()
                ->route('inventaire.show', $inventaire->id)
                ->with('success', trans('validate_inventaire_success'));

        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return back()->withErrors($e->errors())->withInput();

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('Inventaire', 'validateInventaire', $e->getMessage());
            return back()->with('error', trans('validate_inventaire_error'));
        }
    }


    /* =====================================================
     * ANNULATION INVENTAIRE
     * ===================================================== */
    public function cancel(Request $request, int $id)
    {
        DB::beginTransaction();

        try {
            $inventaire = Inventaire::findOrFail($id);

            if (!$this->canCancelInventaire(auth()->user(), $inventaire)) {
                return back()->with('error', trans('unauthorized_action'));
            }

            if ($inventaire->statut !== Inventaire::STATUT_BROUILLON) {
                return back()->with('error', trans('inventaire_not_cancellable'));
            }

            $validated = $request->validate([
                'raison_annulation' => 'required|string|max:1000',
            ]);

            $inventaire->update([
                'statut' => Inventaire::STATUT_ANNULE,
                'commentaire' => trim(($inventaire->commentaire ?? '') .
                    "\n\nRaison annulation : " . $validated['raison_annulation']),
            ]);

            DB::commit();

            return back()->with('success',  trans('inventaire_cancel_success'));

        } catch (\Throwable $e) {
            DB::rollBack();
            log_error('Inventaire', 'cancel', $e->getMessage());
            return back()->with('error',  trans('inventaire_cancel_error'));
        }
    }

    /* =====================================================
     * HELPERS AUTORISATION
     * ===================================================== */
    private function canAccessInventaire($user, $inventaire)
    {
        return $this->canAccessEmplacement($user, $inventaire->emplacement);
    }
    private function canAccessEmplacement($user, $emplacement): bool
    {
        if ($user->hasRole([
            config('appconstants.role.admin'),
            config('appconstants.role.superadmin'),
        ])) {
            return true;
        }

        if ($user->hasRole(config('appconstants.role.marchand'))) {
            return $user->marchand &&
                $emplacement->marchand_id === $user->marchand->id;
        }

        if ($employe = $user->employePrincipal) {
            return $employe->points_vente_id === $emplacement->id;
        }

        return false;
    }

    private function canValidateInventaire($user, $inventaire): bool
    {
        if ($user->hasRole([
            config('appconstants.role.admin'),
            config('appconstants.role.superadmin'),
        ])) {
            return true;
        }

        if ($employe = $user->employePrincipal) {
            return $employe->points_vente_id === $inventaire->emplacement_id
                && $employe->type_employe === config('appconstants.type_employe.manager');
        }

        return false;
    }

    private function canCancelInventaire($user, $inventaire): bool
    {
        return $this->canValidateInventaire($user, $inventaire);
    }
}
