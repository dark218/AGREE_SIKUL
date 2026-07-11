<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\RessourcesLogistique\Entities\Ouvrage;

/**
 * Bibliothèque › Inventaire — vue AGRÉGÉE en lecture seule.
 * Pour chaque ouvrage :
 *   Quantité initiale = Σ quantités des ENTRÉES
 *   Sorties/Prêts     = Σ quantités des SORTIES
 *   Stock disponible  = Quantité initiale − Sorties/Prêts
 * (Aucune table dédiée : tout est calculé — pas de redondance.)
 */
class InventaireLivresController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:inventaire-livres-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Ouvrage::query()
                ->select('ouvrages.id', 'titre', 'auteur', 'editeur', 'langue', 'categorie', 'annee_publication')
                ->selectRaw('(SELECT COALESCE(SUM(quantite),0) FROM entrees_livres
                              WHERE entrees_livres.ouvrage_id = ouvrages.id
                                AND entrees_livres.deleted_at IS NULL) as total_entrees')
                ->selectRaw('(SELECT COALESCE(SUM(quantite),0) FROM sorties_livres
                              WHERE sorties_livres.ouvrage_id = ouvrages.id
                                AND sorties_livres.deleted_at IS NULL) as total_sorties');

            if ($request->filled('search')) {
                $s = $request->input('search');
                $query->where(function ($q) use ($s) {
                    $q->where('titre', 'like', "%$s%")
                      ->orWhere('auteur', 'like', "%$s%")
                      ->orWhere('categorie', 'like', "%$s%");
                });
            }

            $inventaire = $query->orderBy('titre')->paginate(15)->withQueryString()
                ->through(function ($o) {
                    $entrees = (int) $o->total_entrees;
                    $sorties = (int) $o->total_sorties;
                    return [
                        'id'                => $o->id,
                        'titre'             => $o->titre,
                        'sujet'             => $o->categorie,
                        'langue'            => $o->langue,
                        'auteur'            => $o->auteur,
                        'editeur'           => $o->editeur,
                        'annee_publication' => $o->annee_publication,
                        'quantite_initiale' => $entrees,
                        'sorties'           => $sorties,
                        'stock_disponible'  => $entrees - $sorties,
                    ];
                });

            return Inertia::render('RessourcesLogistique::InventaireLivres/Index', [
                'inventaire' => $inventaire,
                'filters'    => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error('InventaireLivres', 'index', $th->getMessage());
            return back()->with('error', 'Erreur lors du chargement');
        }
    }
}
