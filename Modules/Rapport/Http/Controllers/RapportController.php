<?php

namespace Modules\Rapport\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use App\Models\User;

class RapportController extends Controller
{
    public function index(Request $request)
    {
        // Stats globales
        $stats = [
            'apprenants' => Apprenant::whereNull('deleted_at')->count(),
            'enseignants' => Enseignant::whereNull('deleted_at')->count(),
            'classes' => Classe::whereNull('deleted_at')->count(),
            'ecoles' => Ecole::whereNull('deleted_at')->count(),
            'utilisateurs' => User::whereNull('deleted_at')->count(),
        ];

        // Apprenants par école (depuis apprenants.classe_id -> classes.ecole_id)
        $apprenantParEcole = DB::table('apprenants')
            ->whereNull('apprenants.deleted_at')
            ->whereNotNull('apprenants.classe_id')
            ->join('classes', 'apprenants.classe_id', '=', 'classes.id')
            ->join('ecoles', 'classes.ecole_id', '=', 'ecoles.id')
            ->select('ecoles.nom as ecole', DB::raw('COUNT(*) as nombre'))
            ->groupBy('ecoles.id', 'ecoles.nom')
            ->get()
            ->map(fn($e) => (array)$e)
            ->toArray();

        // Enseignants — pas de ecole_id, on met le total
        $totalEnseignants = Enseignant::whereNull('deleted_at')->count();
        $enseignantParEcole = Ecole::whereNull('deleted_at')->get()->map(fn($e) => [
            'ecole' => $e->nom,
            'nombre' => $totalEnseignants,
        ])->toArray();

        // Détails écoles
        $ecoles = Ecole::whereNull('deleted_at')
            ->withCount('classes')
            ->get()
            ->map(function ($e) {
                $apprenants = DB::table('apprenants')
                    ->whereNull('apprenants.deleted_at')
                    ->join('classes', 'apprenants.classe_id', '=', 'classes.id')
                    ->where('classes.ecole_id', $e->id)
                    ->count();

                $enseignants = DB::table('enseignants')->whereNull('deleted_at')->count();

                return [
                    'nom' => $e->nom,
                    'statut' => $e->statut ?? 'actif',
                    'apprenants' => $apprenants,
                    'enseignants' => $enseignants,
                    'classes' => $e->classes_count ?? 0,
                ];
            })
            ->toArray();

        return Inertia::render('Rapport::Index', [
            'stats' => $stats,
            'apprenantParEcole' => $apprenantParEcole,
            'enseignantParEcole' => $enseignantParEcole,
            'ecoles' => $ecoles,
        ]);
    }
}
