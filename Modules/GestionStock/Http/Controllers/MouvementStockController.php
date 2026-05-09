<?php

namespace Modules\GestionStock\Http\Controllers;

use App\Helpers\StockMovementHelper;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Modules\GestionStock\Entities\MouvementStock;

class MouvementStockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:mouvement-stock-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:mouvement-stock-create', ['only' => ['create', 'store']]);
    }

    /**
     * Affiche la liste des mouvements de stock
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            $query = MouvementStock::with([
                'article',
                'emplacementSource',
                'emplacementDestination',
                'employe.user'
            ])
            ->orderBy('created_at', 'DESC');

            // Filtres
            $filters = [
                'article_id' => $request->input('article_id'),
                'type_mouvement' => $request->input('type_mouvement'),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
                'emplacement_id' => $request->input('emplacement_id'),
            ];

            if (!empty($filters['article_id'])) {
                $query->where('article_id', $filters['article_id']);
            }

            if (!empty($filters['type_mouvement'])) {
                $query->where('type_mouvement', $filters['type_mouvement']);
            }

            if (!empty($filters['date_debut'])) {
                $query->whereDate('created_at', '>=', $filters['date_debut']);
            }

            if (!empty($filters['date_fin'])) {
                $query->whereDate('created_at', '<=', $filters['date_fin']);
            }

            if (!empty($filters['emplacement_id'])) {
                $query->where(function($q) use ($filters) {
                    $q->where('emplacement_source_id', $filters['emplacement_id'])
                      ->orWhere('emplacement_destination_id', $filters['emplacement_id']);
                });
            }

            // Pour les utilisateurs non-admins, filtrer par leurs points de vente
//            if (!$user->hasRole([config('appconstants.role.admin'),config('appconstants.role.superadmin')])) {
            if ($user->hasRole([config('appconstants.role.manager')])) {
                $pointsVenteIds = $user->employePrincipal->pointVente->pluck('id');
                $query->where(function($q) use ($pointsVenteIds) {
                    $q->whereIn('emplacement_source_id', $pointsVenteIds)
                      ->orWhereIn('emplacement_destination_id', $pointsVenteIds);
                });
                $pointsVente = PointVente::select('id', 'nom')
                    ->whereNull('parent_points_vente_id')
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);
                $articles = Article::select('id', 'nom', 'sku','marque')
                    ->whereHas('pointVente', fn($q) => $q->whereIn('id', $pointsVenteIds))
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($a) => ['value' => $a->id, 'label' => "{$a->nom} ({$a->sku})"]);
            }elseif ($user->hasRole([config('appconstants.role.marchand')])){

                $marchand = $user->marchand;

                if (!$marchand) {
                    return back()->with('error', trans('marchand_not_rattach'));
                }
                $pointsVente = PointVente::select('id', 'nom')
                    ->whereNull('parent_points_vente_id')
                    ->where('marchand_id', $marchand->id)
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);

                $pointsVenteIds = PointVente::where('marchand_id', $marchand->id)
                    ->pluck('id');

                $query->where(function ($q) use ($pointsVenteIds) {
                    $q->whereIn('emplacement_source_id', $pointsVenteIds)
                        ->orWhereIn('emplacement_destination_id', $pointsVenteIds);
                });
                // Données pour les filtres
                $articles = Article::select('id', 'nom', 'sku')
                    ->whereHas('pointVente', fn($q) => $q->whereIn('id', $pointsVenteIds))
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($a) => ['value' => $a->id, 'label' => "{$a->nom} ({$a->sku})"]);



            }else{
                $pointsVente = PointVente::select('id', 'nom')
                    ->whereNull('parent_points_vente_id')
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);
                // Données pour les filtres
                $articles = Article::select('id', 'nom', 'sku')
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($a) => ['value' => $a->id, 'label' => "{$a->nom} ({$a->sku})"]);

            }

            $mouvements = $query->paginate(10)->withQueryString();


            $typesMouvement = [
                ['value' => MouvementStock::TYPE_VENTE, 'label' => trans('modules.business.mouvementStock.types.vente')],
                ['value' => MouvementStock::TYPE_REMBOURSEMENT, 'label' => trans('modules.business.mouvementStock.types.remboursement')],
                ['value' => MouvementStock::TYPE_ENTREE_STOCK, 'label' => trans('modules.business.mouvementStock.types.entree_stock')],
                ['value' => MouvementStock::TYPE_SORTIE_STOCK, 'label' => trans('modules.business.mouvementStock.types.sortie_stock')],
                ['value' => MouvementStock::TYPE_TRANSFERT, 'label' => trans('modules.business.mouvementStock.types.transfert')],
                ['value' => MouvementStock::TYPE_INVENTAIRE, 'label' => trans('modules.business.mouvementStock.types.inventaire')],
                ['value' => MouvementStock::TYPE_CORRECTION, 'label' => trans('modules.business.mouvementStock.types.correction')],
            ];



            return Inertia::render('GestionStock::Mouvements/Index', [
                'mouvements' => $mouvements,
                'filters' => $filters,
                'articles' => $articles,
                'typesMouvement' => $typesMouvement,
                'pointsVente' => $pointsVente,
            ]);

        } catch (\Throwable $e) {
            log_error("MouvementStock", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur lors du chargement des mouvements de stock'));
        }
    }

    /**
     * Affiche le formulaire de création d'un mouvement de stock
     */
    public function create()
    {
        try {
            $user = auth()->user();

            // Récupération des articles avec stock
            $articles = Article::select('id', 'nom', 'sku', 'quantite_stock', 'points_vente_id')
                ->with('pointVente:id,nom')
                ->orderBy('nom')
                ->get()
                ->map(fn($a) => [
                    'value' => $a->id,
                    'label' => "{$a->nom} ({$a->sku}) - Stock: {$a->quantite_stock}",
                    'stock' => $a->quantite_stock,
                    'point_vente' => $a->pointVente->nom ?? 'N/A'
                ]);

            // Récupération des points de vente
            $pointsVente = [];
            if ($user->hasRole([config('appconstants.role.admin'),config('appconstants.role.superadmin')])) {
                $pointsVente = PointVente::select('id', 'nom')
                    ->orderBy('nom')
                    ->get()
                    ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);
            } else if ($user->employePrincipal) {
                $pointsVente = $user->employePrincipal->pointVente
                    ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);
            }

            // Types de mouvement disponibles
            $typesMouvement = [
                ['value' => MouvementStock::TYPE_ENTREE_STOCK, 'label' => trans('modules.business.mouvementStock.types.entree_stock')],
                ['value' => MouvementStock::TYPE_SORTIE_STOCK, 'label' => trans('modules.business.mouvementStock.types.sortie_stock')],
                ['value' => MouvementStock::TYPE_TRANSFERT, 'label' => trans('modules.business.mouvementStock.types.transfert')],
                ['value' => MouvementStock::TYPE_INVENTAIRE, 'label' => trans('modules.business.mouvementStock.types.inventaire')],
                ['value' => MouvementStock::TYPE_CORRECTION, 'label' => trans('modules.business.mouvementStock.types.correction')],
            ];

            return Inertia::render('GestionStock::Mouvements/Create', [
                'articles' => $articles,
                'pointsVente' => $pointsVente,
                'typesMouvement' => $typesMouvement,
            ]);

        } catch (\Throwable $e) {
            log_error("MouvementStock", "create", $e->getMessage());
            return redirect()->route('mouvement-stock.index')
                ->with('error', trans('Erreur lors du chargement du formulaire'));
        }
    }

    /**
     * Enregistre un nouveau mouvement de stock
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $employe = $user->employePrincipal;

            $validated = $request->validate([
                'article_id' => 'required|exists:articles,id',
                'type_mouvement' => 'required|in:' . implode(',', [
                    MouvementStock::TYPE_ENTREE_STOCK,
                    MouvementStock::TYPE_SORTIE_STOCK,
                    MouvementStock::TYPE_TRANSFERT,
                    MouvementStock::TYPE_INVENTAIRE,
                    MouvementStock::TYPE_CORRECTION,
                ]),
                'quantite' => 'required|integer|min:1',
                'emplacement_source_id' => [
                    'nullable',
                    'exists:points_vente,id',
                    function ($attribute, $value, $fail) use ($request) {
                        if ($request->type_mouvement === MouvementStock::TYPE_TRANSFERT && !$value) {
                            $fail('L\'emplacement source est requis pour un transfert.');
                        }
                    },
                ],
                'emplacement_destination_id' => [
                    'nullable',
                    'exists:points_vente,id',
                    function ($attribute, $value, $fail) use ($request) {
                        if (in_array($request->type_mouvement, [
                            MouvementStock::TYPE_ENTREE_STOCK,
                            MouvementStock::TYPE_TRANSFERT
                        ]) && !$value) {
                            $fail('L\'emplacement de destination est requis pour ce type de mouvement.');
                        }
                    },
                ],
                'commentaire' => 'nullable|string|max:1000',
            ]);

            // Vérification des permissions sur les points de vente
            if (!$user->hasRole([config('appconstants.role.admin'),config('appconstants.role.superadmin')])) {
                $pointsVenteIds = $employe->pointVente->pluck('id')->toArray();

                if ($validated['emplacement_source_id'] && !in_array($validated['emplacement_source_id'], $pointsVenteIds)) {
                    return back()->with('error', 'Vous n\'avez pas les droits sur ce point de vente source.');
                }

                if ($validated['emplacement_destination_id'] && !in_array($validated['emplacement_destination_id'], $pointsVenteIds)) {
                    return back()->with('error', 'Vous n\'avez pas les droits sur ce point de vente de destination.');
                }
            }

            // Récupération de l'article avec verrouillage
            $article = Article::lockForUpdate()->findOrFail($validated['article_id']);

            // Calcul de la quantité (négative pour les sorties)
            $quantite = $validated['quantite'];
            if ($validated['type_mouvement'] === MouvementStock::TYPE_SORTIE_STOCK) {
                $quantite = -$quantite;
            }

            // Traitement spécial pour la mise à jour d'inventaire
            if ($validated['type_mouvement'] === MouvementStock::TYPE_INVENTAIRE) {
                $quantite = $validated['quantite'] - $article->quantite_stock;
            }

            // Vérification du stock pour les sorties
            if ($quantite < 0 && abs($quantite) > $article->quantite_stock) {
                return back()->with('error', 'Stock insuffisant pour effectuer cette opération.');
            }

            // Utilisation du helper pour appliquer le mouvement de stock
            StockMovementHelper::apply(
                article: $article,
                quantite: $quantite,
                typeMouvement: $validated['type_mouvement'],
                emplacementSourceId: $validated['emplacement_source_id'] ?? null,
                emplacementDestinationId: $validated['emplacement_destination_id'] ?? null,
                employe: $employe,
                reference: 'MANU-' . date('YmdHis'),
                commentaire: $validated['commentaire'] ?? null
            );

            return redirect()
                ->route('mouvement-stock.index')
                ->with('success', 'Mouvement de stock enregistré avec succès.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("MouvementStock", "store", $e->getMessage());
            return back()
                ->with('error', 'Erreur lors de l\'enregistrement du mouvement de stock: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Affiche les détails d'un mouvement de stock
     */
    public function show($id)
    {
        try {
            $mouvement = MouvementStock::with([
                'article',
                'emplacementSource',
                'emplacementDestination',
                'employe.user'
            ])->findOrFail($id);
            // Vérification des permissions
            $user = auth()->user();
            if ($user->hasRole([config('appconstants.role.manager')])) {
                $pointsVenteIds = $user->employePrincipal->pointVente->pluck('id')->toArray();

                if (($mouvement->emplacement_source_id && !in_array($mouvement->emplacement_source_id, $pointsVenteIds)) ||
                    ($mouvement->emplacement_destination_id && !in_array($mouvement->emplacement_destination_id, $pointsVenteIds))) {
                    return redirect()->route('home')->with('error', 'Accès non autorisé à ce mouvement de stock.');
                }
            }
            return Inertia::render('GestionStock::Mouvements/Show', [
                'mouvement' => $mouvement,
            ]);

        } catch (\Throwable $e) {
            log_error("MouvementStock", "show", $e->getMessage());
            return redirect()->route('mouvement-stock.index')
                ->with('error', 'Mouvement de stock introuvable');
        }
    }
}
