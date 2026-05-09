<?php

namespace Modules\Rapport\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Classe;

class StatistiquesClassesController extends Controller
{
    public function index(Request $request)
    {
        // Stats depuis apprenants directement
        $apprenantStats = DB::table('apprenants')
            ->whereNull('deleted_at')
            ->whereNotNull('classe_id')
            ->select(
                'classe_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN sexe = 'F' THEN 1 ELSE 0 END) as filles")
            )
            ->groupBy('classe_id')
            ->get()
            ->keyBy('classe_id');

        // Enseignants — total global
        $totalEnseignants = DB::table('enseignants')->whereNull('deleted_at')->count();

        $statistiques = Classe::with(['ecole:id,nom'])
            ->whereNull('deleted_at')
            ->paginate(10)
            ->through(function ($classe) use ($apprenantStats, $totalEnseignants) {
                $stats = $apprenantStats->get($classe->id);
                $total = (int)($stats->total ?? 0);
                $filles = (int)($stats->filles ?? 0);
                return [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'ecole' => $classe->ecole,
                    'effectif_total' => $total,
                    'nombre_filles' => $filles,
                    'nombre_garcons' => $total - $filles,
                    'nombre_enseignants' => $totalEnseignants,
                    'taux_filles' => $total > 0 ? round(($filles / $total) * 100, 1) : 0,
                ];
            });

        return Inertia::render('Rapport::StatistiquesClasses/Index', [
            'statistiques' => $statistiques,
        ]);
    }
}
