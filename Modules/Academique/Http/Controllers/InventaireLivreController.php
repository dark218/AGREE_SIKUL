<?php

namespace Modules\Academique\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Academique\Entities\Bibliotheque;

/**
 * Inventaire des livres : vue calculée à partir des entrées et sorties.
 * Quantité initiale = Σ entrées ; Sorties/Prêts = Σ sorties ;
 * Stock disponible = entrées − sorties.
 */
class InventaireLivreController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:inventaire-livres-list', ['only' => ['index']]);
    }

    public function index(Request $request)
    {
        try {
            $query = Bibliotheque::query()
                ->withSum(['entreesLivres as total_entrees' => fn ($q) => $q->where('etat', 'actif')], 'quantite')
                ->withSum(['sortiesLivres as total_sorties' => fn ($q) => $q->where('etat', 'actif')], 'quantite');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('titre_manuel', 'like', "%$search%")
                        ->orWhere('auteurs', 'like', "%$search%")
                        ->orWhere('sujet', 'like', "%$search%");
                });
            }

            $livres = $query->orderBy('titre_manuel')
                ->paginate(15)
                ->withQueryString()
                ->through(function ($b) {
                    $entrees = (int) ($b->total_entrees ?? 0);
                    $sorties = (int) ($b->total_sorties ?? 0);
                    return [
                        'id' => $b->id,
                        'titre' => $b->titre_manuel,
                        'sujet' => $b->sujet,
                        'langue' => $b->langue,
                        'auteurs' => $b->auteurs,
                        'editeurs' => $b->editeurs,
                        'annee_edition' => $b->annee_edition,
                        'quantite_initiale' => $entrees,
                        'sorties_prets' => $sorties,
                        'stock_disponible' => $entrees - $sorties,
                    ];
                });

            return Inertia::render('Academique::InventaireLivres/Index', [
                'livres' => $livres,
                'filters' => $request->only(['search']),
            ]);
        } catch (\Throwable $th) {
            log_error('Academique', 'InventaireLivreController::index', $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
