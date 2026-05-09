<?php

namespace Modules\Rapport\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Parametrage\Entities\Ecole;

class StatistiquesEcoleController extends Controller
{
    public function index(Request $request)
    {
        // Stats par école depuis la table apprenants (pas inscriptions)
        $apprenantStats = DB::table('apprenants')
            ->whereNull('apprenants.deleted_at')
            ->whereNotNull('apprenants.classe_id')
            ->join('classes', 'apprenants.classe_id', '=', 'classes.id')
            ->select(
                'classes.ecole_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN apprenants.sexe = 'F' THEN 1 ELSE 0 END) as filles")
            )
            ->groupBy('classes.ecole_id')
            ->get()
            ->keyBy('ecole_id');

        // Enseignants — total global (pas de ecole_id dans la table enseignants)
        $totalEnseignants = DB::table('enseignants')->whereNull('deleted_at')->count();

        // Build par école
        $ecoles = Ecole::whereNull('deleted_at')->withCount('classes')->get();
        $statistiques = $ecoles->map(function ($ecole) use ($apprenantStats, $totalEnseignants) {
            $stats = $apprenantStats->get($ecole->id);
            $total = (int)($stats->total ?? 0);
            $filles = (int)($stats->filles ?? 0);
            return [
                'id' => $ecole->id,
                'nom' => $ecole->nom,
                'nombre_inscrits' => $total,
                'nombre_filles' => $filles,
                'nombre_garcons' => $total - $filles,
                'nombre_enseignants' => $totalEnseignants,
                'nombre_classes' => $ecole->classes_count,
                'taux_filles' => $total > 0 ? round(($filles / $total) * 100, 1) : 0,
            ];
        })->values()->toArray();

        $totaux = [
            'total_inscrits' => array_sum(array_column($statistiques, 'nombre_inscrits')),
            'total_filles' => array_sum(array_column($statistiques, 'nombre_filles')),
            'total_garcons' => array_sum(array_column($statistiques, 'nombre_garcons')),
            'total_enseignants' => array_sum(array_column($statistiques, 'nombre_enseignants')),
            'total_ecoles' => count($statistiques),
            'total_classes' => array_sum(array_column($statistiques, 'nombre_classes')),
        ];
        $totaux['taux_filles_global'] = $totaux['total_inscrits'] > 0
            ? round(($totaux['total_filles'] / $totaux['total_inscrits']) * 100, 1)
            : 0;

        return Inertia::render('Rapport::StatistiquesEcole/Index', [
            'statistiques' => $statistiques,
            'totaux' => $totaux,
        ]);
    }
}
