<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

/**
 * DashboardController — version AGREE SIKUL (école).
 *
 * Remplace l'ancien dashboard commercial (Transaction, VirtualCard, Wallet, etc.)
 * par un dashboard centré sur l'établissement scolaire.
 *
 * Les compteurs sont calculés en best-effort : si une table n'existe pas encore
 * (modules en cours de mise en place), on renvoie 0 plutôt que de crasher.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $pageTitle = __('Dashboard');

        $stats = [
            'apprenants' => $this->safeCount('apprenants'),
            'enseignants' => $this->safeCount('enseignants'),
            'classes' => $this->safeCount('classes'),
            'ecoles' => $this->safeCount('ecoles'),
            'campuses' => $this->safeCount('campuses'),
            'institutions' => $this->safeCount('institutions'),
            'inscriptions_en_cours' => $this->safeCount('inscriptions', ['statut' => 'en_cours']),
            'users_actifs' => $this->safeCount('users', ['statut' => 'actif']),
        ];

        return Inertia::render('Dashboard/Index', [
            'pageTitle' => $pageTitle,
            'stats' => $stats,
        ]);
    }

    /**
     * Compte une table en safe-mode : retourne 0 si la table n'existe pas
     * ou si une erreur survient (évite que le dashboard plante en dev).
     */
    private function safeCount(string $table, array $where = []): int
    {
        try {
            if (!Schema::hasTable($table)) {
                return 0;
            }
            $query = DB::table($table);
            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }
            foreach ($where as $col => $val) {
                if (Schema::hasColumn($table, $col)) {
                    $query->where($col, $val);
                }
            }
            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Endpoint AJAX simple pour rafraîchir les compteurs sans recharger la page.
     */
    public function stats(Request $request)
    {
        return response()->json([
            'apprenants' => $this->safeCount('apprenants'),
            'enseignants' => $this->safeCount('enseignants'),
            'classes' => $this->safeCount('classes'),
            'ecoles' => $this->safeCount('ecoles'),
        ]);
    }
}
