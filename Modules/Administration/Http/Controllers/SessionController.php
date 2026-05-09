<?php

namespace Modules\Administration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Session;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:session-list', ['only' => ['index']]);
        $this->middleware('permission.check:session-delete', ['only' => ['destroyByUser']]);
    }

    /**
     * Display a listing of sessions.
     */
    public function index(Request $request): Response
    {
        try {
            $filters = [
                'user_id' => $request->input('user_id'),
            ];

            $query = Session::with('user')
                ->orderBy('last_activity', 'DESC');

            if (!empty($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            $sessions = $query->paginate(10)->withQueryString();

            // Transformer les sessions pour Vue
            $sessions->getCollection()->transform(function ($session) {
                $user = User::find($session->user_id);
                return [
                    'id' => $session->id,
                    'user_id' => $session->user_id,
                    'user_name' => $user ? $user->nom . ' ' . $user->prenoms : 'Utilisateur inconnu',
                    'user_login' => $user ? $user->login : '-',
                    'ip_address' => $session->ip_address,
                    'user_agent' => $this->parseUserAgent($session->user_agent),
                    'last_activity' => $this->formatLastActivity($session->last_activity),
                    'last_activity_timestamp' => $session->last_activity,
                    'is_current' => $session->id === session()->getId(),
                ];
            });

            // Récupérer les utilisateurs pour le filtre
            $users = User::select('id', 'nom', 'prenoms', 'login')
                ->orderBy('nom')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->nom . ' ' . $user->prenoms . ' (' . $user->login . ')',
                ]);

            return Inertia::render('Administration::Sessions/Index', [
                'sessions' => $sessions,
                'users' => $users,
                'filters' => $filters,
            ]);
        }catch (\Throwable $th) {
            log_error("Session", "index", $th->getMessage());
            return redirect()->route('home')->with('error', __('Erreur'));
        }
    }

    /**
     * Delete all sessions for a specific user.
     */
    public function destroyByUser($userId)
    {
        try {

            // Ne pas supprimer la session courante si c'est l'utilisateur connecté
            $currentSessionId = session()->getId();
            $deletedCount = Session::where('user_id', $userId)
                ->where('id', '!=', $currentSessionId)
                ->delete();


            return redirect()->route('administration.session.index')
                ->with('success', __('suppressionsucces') . " ({$deletedCount} session(s))");
        } catch (\Throwable $e) {
            log_error("Session", "destroyByUser", $e->getMessage());
            return redirect()->route('administration.session.index')
                ->with('error', __('erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Delete a specific session.
     */
    public function destroy($id)
    {
        try {
            // Empêcher la suppression de la session courante
            if ($id === session()->getId()) {
                return redirect()->route('administration.session.index')
                    ->with('error', __('Vous ne pouvez pas supprimer votre session active'));
            }

            Session::where('id', $id)->delete();

            return redirect()->route('administration.session.index')
                ->with('success', __('suppressionsucces'));
        } catch (\Throwable $e) {
            log_error("Session", "destroy", $e->getMessage());
            return redirect()->route('administration.session.index')
                ->with('error', __('erreur') . ': ' . $e->getMessage());
        }
    }

    /**
     * Parse user agent to get browser and OS info.
     */
    private function parseUserAgent(?string $userAgent): array
    {
        if (!$userAgent) {
            return ['browser' => 'Inconnu', 'os' => 'Inconnu'];
        }

        $browser = 'Inconnu';
        $os = 'Inconnu';

        // Détecter le navigateur
        if (strpos($userAgent, 'Firefox') !== false) {
            $browser = 'Firefox';
        } elseif (strpos($userAgent, 'Chrome') !== false && strpos($userAgent, 'Edg') === false) {
            $browser = 'Chrome';
        } elseif (strpos($userAgent, 'Safari') !== false && strpos($userAgent, 'Chrome') === false) {
            $browser = 'Safari';
        } elseif (strpos($userAgent, 'Edg') !== false) {
            $browser = 'Edge';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR') !== false) {
            $browser = 'Opera';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            $browser = 'Internet Explorer';
        }

        // Détecter le système d'exploitation
        if (strpos($userAgent, 'Windows') !== false) {
            $os = 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            $os = 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            $os = 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            $os = 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false || strpos($userAgent, 'iPhone') !== false || strpos($userAgent, 'iPad') !== false) {
            $os = 'iOS';
        }

        return ['browser' => $browser, 'os' => $os];
    }

    /**
     * Format last activity timestamp to human readable format.
     */
    private function formatLastActivity(?int $timestamp): string
    {

        if (!$timestamp) {
            return 'Inconnu';
        }

        $date = \Carbon\Carbon::createFromTimestamp($timestamp);
        $now = \Carbon\Carbon::now();
        $diff = $now->diffInMinutes($date);

        if ($diff < 1) {
            return 'À l\'instant';
        } elseif ($diff < 60) {
            return "Il y a {$diff} min";
        } elseif ($diff < 1440) {
            $hours = floor($diff / 60);
            return "Il y a {$hours}h";
        } else {
            return $date->format('d/m/Y H:i');
        }
    }
}
