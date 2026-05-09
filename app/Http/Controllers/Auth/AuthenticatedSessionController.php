<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Modules\Parametrage\Entities\Pays;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use App\Models\Session as UserSession;

class AuthenticatedSessionController extends Controller
{
    /**
     * Afficher la page de connexion
     */
    public function create(): Response
    {
        // Charger les pays et formater pour le frontend
        $pays = Pays::all()->map(function($country) {
            return [
                'code' => $country->phone_code,  // Utiliser le code téléphonique
                'libelle' => $country->libelle,
                'code_2_chars' => $country->code_2_chars,
                'length' => 10  // Longueur par défaut
            ];
        });

        return Inertia::render('Auth/Login', [
            'pays' => $pays,
            'status' => session('status'),
            'firebaseConfig' => [
                'apiKey' => config('services.firebase.api_key'),
                'authDomain' => config('services.firebase.auth_domain'),
                'projectId' => config('services.firebase.project_id'),
                'messagingSenderId' => config('services.firebase.messaging_sender_id'),
                'appId' => config('services.firebase.app_id'),
                'vapidKey' => config('services.firebase.vapid_key'),
            ],
        ]);
    }

    /**
     * Gérer la connexion
     */
    /**
     * Mapping portail → rôles autorisés
     */
    private const PORTAL_ROLES = [
        'administration' => ['super_admin', 'directeur_general', 'directeur_campus', 'directeur_ecole'],
        'enseignant'     => ['enseignant'],
        'eleve'          => ['eleve'],
        'parent'         => ['parent'],
        'personnel'      => ['personnel_administratif', 'bibliothecaire', 'infirmier', 'agent_securite'],
        'caisse'         => ['Caissier', 'Manager'],
    ];

    public function store(Request $request): SymfonyResponse
    {
        $request->validate([
            'country_code' => 'required|string',
            'full_login'   => 'required|string',
            'password'     => 'required|string',
            'portal'       => 'required|string|in:' . implode(',', array_keys(self::PORTAL_ROLES)),
        ]);

        // Nettoyer les espaces et construire le full_login
        $country_code = trim($request->country_code);
        $phone = trim($request->full_login);

        // Combiner directement sans modification (ex: +225 + 0700000001)
        $full_login = $country_code . $phone;

        if (Auth::attempt(['full_login' => $full_login, 'password' => $request->password])) {
            $user = Auth::user();

            // Vérifier que l'utilisateur a un rôle compatible avec le portail choisi
            $portalKey    = $request->input('portal');
            $allowedRoles = self::PORTAL_ROLES[$portalKey] ?? [];

            if (!$user->hasAnyRole($allowedRoles)) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                $portalLabels = [
                    'administration' => 'Administration',
                    'enseignant'     => 'Enseignant',
                    'eleve'          => 'Élève',
                    'parent'         => 'Parent / Tuteur',
                    'personnel'      => 'Personnel',
                    'caisse'         => 'Caisse / POS',
                ];

                return back()->withErrors([
                    'portal' => 'Votre compte n\'a pas accès au portail « ' . ($portalLabels[$portalKey] ?? $portalKey) . ' ». Veuillez sélectionner le bon portail.',
                ])->onlyInput('full_login', 'country_code');
            }

            // Récupérer le token FCM AVANT la régénération de session
            $fcmToken = $request->input('fcm_token');

            // Régénération de l'ID de session (sécurité contre fixation)
            $request->session()->regenerate();

            // Écrire des infos utiles dans la session Laravel (accessibles avec session('...'))
            $request->session()->put('user_id', $user->id);
            $request->session()->put('full_login', $user->full_login ?? $full_login);
            $request->session()->put('nom', $user->name ?? $user->nom ?? null);

            // Forcer Laravel à sauvegarder la session immédiatement
            $request->session()->save();

            // MAINTENANT mettre à jour la table "sessions" avec le FCM token
            // Utiliser un petit délai pour s'assurer que Laravel a créé la ligne
            $sessionId = $request->session()->getId();

            // Attendre que la session soit créée (max 3 tentatives)
            $attempts = 0;
            $updated = 0;
            while ($attempts < 3 && $updated === 0) {
                if ($attempts > 0) {
                    usleep(100000); // Attendre 100ms
                }

                $updated = \DB::table('sessions')
                    ->where('id', $sessionId)
                    ->update([
                        'user_id' => $user->id,
                        'ip_address' => $request->ip(),
                        'user_agent' => substr($request->userAgent() ?? '', 0, 500),
                        'fcm_token' => $fcmToken,
                        'last_activity' => now()->timestamp,
                    ]);

                $attempts++;
            }

         

            // AUSSI stocker le token dans users (pour référence)
            if ($fcmToken) {
                \DB::table('users')
                    ->where('id', $user->id)
                    ->update(['fcm_token' => $fcmToken]);

            }

            // Redirection selon le rôle de l'utilisateur
            // Utiliser Inertia::location() pour les rôles POS (caissier/manager) afin de forcer
            // un rechargement complet de la page et charger le bon template Blade (app-pos)

            if ($user->hasRole(config('appconstants.role.caissier'))) {
                return Inertia::location(route('session-caisse-caissier.index'));
            }

            if ($user->hasRole(config('appconstants.role.manager'))) {
                return Inertia::location(route('session-caisse-manager.index'));
            }

            return Inertia::location(route('home'));
        }

        return back()->withErrors([
            'full_login' => 'Les identifiants fournis sont incorrects.',
        ])->onlyInput('full_login', 'country_code');
    }

    /**
     * Gérer la déconnexion
     */
    public function destroy(Request $request): SymfonyResponse
    {
        $user = Auth::user();

        if ($user) {

            // 1) Supprimer le token FCM (sécurité mobile)
            $user->fcm_token = null;
            $user->save();

            // 2) Supprimer TOUTES les sessions de cet utilisateur (multi-devices)
            UserSession::where('user_id', $user->id)->delete();
        }

        // 3) Déconnexion Laravel
        Auth::guard('web')->logout();

        // 4) Invalider la session actuelle
        $request->session()->invalidate();

        // 5) Régénérer le token CSRF
        $request->session()->regenerateToken();

        // Utiliser Inertia::location() pour forcer un rechargement complet de la page
        // Cela permet de récupérer le nouveau token CSRF après la déconnexion
        return Inertia::location(route('login'));
    }

}
