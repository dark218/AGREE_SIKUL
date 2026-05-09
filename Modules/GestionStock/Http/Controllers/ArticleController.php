<?php

namespace Modules\GestionStock\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MoneyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\Article;
use Modules\Parametrage\Entities\Devises;

class ArticleController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:article-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:article-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:article-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:article-statut', ['only' => ['statut']]);
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $marchand = $user->marchand; // null si admin / superadmin

            $query = Article::with(['pointVente'])
                ->orderBy('created_at', 'DESC');

            if ($marchand) {
                $query->whereHas('pointVente', function ($q) use ($marchand) {
                    $q->where('marchand_id', $marchand->id);
                });
            }
            // Filtres
            $filters = [
                'nom' => $request->input('nom'),
                'sku' => $request->input('sku'),
                'marque' => $request->input('marque'),
                'point_vente_id' => $request->input('point_vente_id'),
            ];

            if (!empty($filters['nom'])) {
                $query->where('nom', 'LIKE', "%{$filters['nom']}%");
            }

            if (!empty($filters['sku'])) {
                $query->where('sku', 'LIKE', "%{$filters['sku']}%");
            }
            if (!empty($filters['marque'])) {
                $query->where('marque', 'LIKE', "%{$filters['marque']}%");
            }

            if (!empty($filters['point_vente_id'])) {
                $query->where('points_vente_id', $filters['point_vente_id']);

                if ($marchand) {
                    $query->whereHas('pointVente', function ($q) use ($marchand) {
                        $q->where('marchand_id', $marchand->id);
                    });
                }
            }


            $articles = $query->paginate(10)->withQueryString();

            // Récupération des points de vente pour le filtre
            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);

            $articles->each(function ($article) {
                $article['prix_display'] = (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise));

            });
            return Inertia::render('GestionStock::Articles/Index', [
                'articles' => $articles,
                'pointsVente' => $pointsVente,
                'filters' => $filters,
            ]);
        } catch (\Throwable $e) {
            log_error("Article", "index", $e->getMessage());
            return redirect()->route('home')->with('error', trans('Erreur'));
        }
    }

    public function create()
    {
        try {
            $user = auth()->user();
            $marchand = $user->marchand;

            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);
            $devise = [];
            if ($user->pays) {
                $devise = $user->pays
                    ->paysDevises()
                    ->with('devise')
                    ->get()
                    ->map(fn($pd) => ['value' => $pd->devise->code, 'label' => $pd->devise->code])
                    ->toArray();
            }else{
                $devise = Devises::select('id','code','libelle')
                    ->orderBy('libelle')
                    ->get()
                    ->map(fn($d) => ['value' => $d->code, 'label' => $d->code.' - '.$d->libelle]);

            }

            return Inertia::render('GestionStock::Articles/Create', [
                'pointsVente' => $pointsVente,
                'devise'=>$devise,
                'defaultDevise' => 'XOF',
            ]);
        } catch (\Throwable $e) {
            log_error("Article", "create", $e->getMessage());
            return redirect()->route('article.index')->with('error', trans('Erreur lors du chargement du formulaire'));
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'sku' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('articles', 'sku')
                        ->where(fn ($q) => $q->where('points_vente_id', $request->points_vente_id)),
                ],
                'marque' => 'required|string|max:255',
                'nom' => 'required|string|max:255',
                'description' => 'nullable|string',
                'prix_cents' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
                'devise' => 'required|string|size:3',
                'quantite_stock' => 'required|integer|min:0',
                'seuil_alert_stock' => 'required|integer|min:0',
                'taxes_json' => 'nullable|json',
            ]);
            $prix = MoneyService::toDatabase($validatedData['prix_cents'], $validatedData['devise']);

            $validatedData['prix_cents'] = $prix;
            // Création de l'article
            $article = Article::create($validatedData);
            return redirect()->route('article.index')->with('success', trans('enregistrementsucces'));

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("Article", "store", $e->getMessage());
            return back()->with('error', trans('Erreur lors de la création de l\'article'))->withInput();
        }
    }

    public function show($id)
    {
        try {
            $article = Article::with(['pointVente'])->findOrFail($id);
//            $article['prix_display'] = MoneyService::toDisplay($article->prix_cents, $article->devise);
            $article['prix_display'] = (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise));

            $devise = Devises::select('id','code','libelle')
                ->orderBy('libelle')
                ->get()
                ->map(fn($d) => ['value' => $d->code, 'label' => $d->code.' - '.$d->libelle]);
            return Inertia::render('GestionStock::Articles/Show', [
                'article' => $article,
                'devise' => $devise,
            ]);
        } catch (\Throwable $e) {
            log_error("Article", "show", $e->getMessage());
            return redirect()->route('article.index')->with('error', trans('Article introuvable'));
        }
    }

    public function edit($id)
    {
        try {
            $article = Article::findOrFail($id);
            $article['prix_display'] = (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise));

            $user = auth()->user();
            $marchand = $user->marchand;

            $pointsVenteQuery = PointVente::select('id', 'nom')
                ->where('statut', config('appconstants.pointvente_statut.actif'))
                ->whereNull('parent_points_vente_id')
                ->orderBy('nom');

            if ($marchand) {
                $pointsVenteQuery->where('marchand_id', $marchand->id);
            }

            $pointsVente = $pointsVenteQuery
                ->get()
                ->map(fn($pv) => ['value' => $pv->id, 'label' => $pv->nom]);

            $devise = [];
            if ($user->pays) {
                $devise = $user->pays
                    ->paysDevises()
                    ->with('devise')
                    ->get()
                    ->map(fn($pd) => ['value' => $pd->devise->code, 'label' => $pd->devise->code])
                    ->toArray();
            }else{
                $devise = Devises::select('id','code','libelle')
                    ->orderBy('libelle')
                    ->get()
                    ->map(fn($d) => ['value' => $d->code, 'label' => $d->code.' - '.$d->libelle]);

            }

            return Inertia::render('GestionStock::Articles/Edit', [
                'article' => $article,
                'pointsVente' => $pointsVente,
                'devise'=>$devise,
                'prix' => $article['prix_display'], // Utilisation de l'accesseur
            ]);
        } catch (\Throwable $e) {
            log_error("Article", "edit", $e->getMessage());
            return redirect()->route('article.index')->with('error', trans('Erreur lors du chargement du formulaire'));
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $article = Article::findOrFail($id);

            $validatedData = $request->validate([
                'points_vente_id' => 'required|exists:points_vente,id',
                'sku' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('articles', 'sku')
                        ->where(fn ($q) => $q->where('points_vente_id', $request->points_vente_id))
                        ->ignore($id),
                ],
                'nom' => 'required|string|max:255',
                'marque' => 'required|string|max:255',
                'description' => 'nullable|string',
                'prix_cents' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,2})?$/',
                'devise' => 'required|string|size:3',
                'quantite_stock' => 'required|integer|min:0',
                'seuil_alert_stock' => 'required|integer|min:0',
                'taxes_json' => 'nullable|json'
            ]);

            $validatedData['prix_cents'] = MoneyService::toDatabase($validatedData['prix_cents'], $validatedData['devise']);

            $article->update($validatedData);

            return redirect()->route('article.index')->with('success', trans('modifsucces'));


        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            log_error("Article", "update", $e->getMessage());
            return back()->with('error', trans('Erreur lors de la mise à jour de l\'article'))->withInput();
        }
    }

    public function statut( $id)
    {
        try {
            $article = Article::withTrashed()->findOrFail($id);

            if ($article->trashed()) {
                $article->restore();
                $message = trans('restaurationsucces');
            } else {
                $article->delete();
                $message = trans('suppressionsucces');
            }

            return redirect()->route('article.index')->with('success', $message);

        } catch (\Throwable $e) {
            log_error("Article", "statut", $e->getMessage());
             return redirect()->route('article.index')->with('error', trans('Erreur'));

        }
    }

    public function findBySkuAndPointVente(Request $request): JsonResponse {
        try {

            $validated = $request->validate([
                'sku' => 'required|string|max:255',
                'points_vente_id' => 'required|exists:points_vente,id',
            ]);

            $article = Article::where('sku', $validated['sku'])
                ->where('points_vente_id', $validated['points_vente_id'])
                ->first();

            if (!$article) {
                return response()->json([
                    'success' => false,
                    'message' => 'Article introuvable pour ce point de vente',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $article->id,
                    'sku' => $article->sku,
                    'nom' => $article->nom,
                    'description' => $article->description,
                    'prix' => (float) str_replace(' ', '', MoneyService::toDisplay($article->prix_cents, $article->devise)),
                    'devise' => $article->devise,
                    'quantite_stock' => $article->quantite_stock,
                    'seuil_alert_stock' => $article->seuil_alert_stock,
                    'taxes' => $article->taxes_json,
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
            ], 422);

        } catch (\Throwable $e) {
            log_error('ArticleCatalogue', 'findBySkuAndPointVente', $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erreur serveur',
            ], 500);
        }
    }
}
