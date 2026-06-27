<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Modules\Administration\Entities\Feature;
use Modules\Administration\Entities\Module;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function rootView(Request $request): string
    {
        if ($request->routeIs('login') || $request->routeIs('register') || $request->routeIs('password.index') || $request->routeIs('password.request') || $request->routeIs('password.reset')) {
            return 'app-auth';
        }
        if ($request->routeIs('welcome')) {
            return 'app-welcome';
        }

        return 'app';
    }

    public function share(Request $request): array
    {
        // Permissions cachées 5 minutes par utilisateur
        $userPerms = [];
        $userPermNames = [];
        if (Auth::check()) {
            $userId = Auth::id();
            $userPerms = Cache::remember("user_perms_{$userId}", 300, function () {
                return Auth::user()->getAllPermissions()->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ])->toArray();
            });
            $userPermNames = array_column($userPerms, 'name');
        }

        $shared = parent::share($request);

        // Auth — sans recharger les permissions
        $shared['auth'] = [
            'user' => Auth::user() ? [
                'id' => Auth::user()->id,
                'nom' => Auth::user()->nom,
                'prenoms' => Auth::user()->prenoms,
                'email' => Auth::user()->email,
                'login' => Auth::user()->login,
                'role' => Auth::user()->role,
                'full_login' => Auth::user()->full_login,
                'photoprofile_id' => Auth::user()->photoprofile_id,
                'roles' => Cache::remember("user_roles_" . Auth::id(), 300, function () {
                    return Auth::user()->roles->map(fn($r) => [
                        'id' => $r->id,
                        'name' => $r->name,
                    ])->toArray();
                }),
                'permissions' => $userPerms,
            ] : null,
        ];

        $additionalProps = [
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
                'warning' => fn() => $request->session()->get('warning'),
                'info' => fn() => $request->session()->get('info'),
            ],
            'locale' => fn() => app()->getLocale(),
            'appConfig' => function () {
                return Cache::remember('app_config', 600, function () {
                    try {
                        $config = \DB::table('parametrage_generaux')->first();
                        return $config ? [
                            'nom' => $config->nom_etablissement,
                            'sigle' => $config->sigle,
                            'logo' => $config->logo_path ? '/' . $config->logo_path : null,
                            'favicon' => $config->favicon_path ? '/' . $config->favicon_path : null,
                            'logo_login' => $config->logo_login_path ? '/' . $config->logo_login_path : null,
                        ] : null;
                    } catch (\Exception $e) { return null; }
                });
            },
            'userimages' => function () use ($request) {
                $user = $request->user();
                if (!$user || !$user->photoprofile_id) return null;
                return Cache::remember("user_photo_{$user->id}", 600, function () use ($user) {
                    $fichier = \DB::table('fichier')->where('id', $user->photoprofile_id)->first();
                    return $fichier?->nom;
                });
            },
            'notifications' => function () use ($request) {
                if (!$request->user()) return [];
                return Cache::remember("user_notifs_{$request->user()->id}", 60, function () use ($request) {
                    return $request->user()
                        ->notifications()
                        ->orderBy('created_at', 'desc')
                        ->take(10)
                        ->get();
                });
            },
            'unreadNotificationsCount' => function () use ($request) {
                if (!$request->user()) return 0;
                return Cache::remember("user_notifs_unread_{$request->user()->id}", 60, function () use ($request) {
                    return $request->user()
                        ->notifications()
                        ->where('is_read', false)
                        ->count();
                });
            },
            'menu_url' => fn() => $this->getMenu($request),
            'title' => fn() => $this->getTitle($request),
            'navbars' => function () use ($request) {
                if (Auth::check()) {
                    return $this->getModulesMenu($request);
                }
                return [];
            },
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'basic_settings' => fn() => $this->getBasicSettings(),
            'userPermissions' => $userPerms,
        ];

        $shared['auth'] = $shared['auth'] ?? [];
        return array_merge($shared, $additionalProps + ['auth' => $shared['auth']]);
    }

    /**
     * Modules menu — caché 5 minutes par utilisateur
     */
    private function getModulesMenu(Request $request): array
    {
        if (!Auth::check()) return [];

        $user = Auth::user();
        $isSuperAdmin = $user->hasRole('super_admin');
        $cacheKey = 'menu_' . ($isSuperAdmin ? 'admin' : 'user_' . $user->id);

        return Cache::remember($cacheKey, 300, function () use ($user, $isSuperAdmin) {
            return Module::actif()
                ->with([
                    'fonctionnalitesActives' => function ($query) {
                        $query->orderBy('ordre')->with('permissions');
                    }
                ])
                ->orderBy('ordre')
                ->get()
                ->filter(fn($module) => $isSuperAdmin || $module->peutVoir($user))
                ->map(fn($module) => [
                    'id' => $module->id,
                    'libelle' => $module->libelle,
                    'libelle_en' => $module->libelle_en,
                    'code' => $module->code ?? '',
                    'icone' => $module->icone,
                    'couleur' => $module->couleur ?? '',
                    'route_prefix' => $module->route_prefix ?? '',
                    'feature' => $module
                        ->fonctionnalitesActives
                        ->filter(fn($f) => $isSuperAdmin || $f->peutVoir($user))
                        ->map(fn($f) => [
                            'id' => $f->id,
                            'libelle' => $f->libelle,
                            'libelle_en' => $f->libelle_en,
                            'code' => $f->code ?? '',
                            'icone' => $f->icone,
                            'controller' => $f->controller ?? '',
                            'action' => $f->action ?? '',
                            'url_complete' => $f->url_complete ?? '',
                            'menu_url' => $f->menu_url,
                            'permissions_requises' => $f->permissions_requises ?? '',
                        ])
                        ->values()
                        ->toArray(),
                ])
                ->values()
                ->toArray();
        });
    }

    private function getMenu(Request $request): string
    {
        $path = $request->path();
        if ($path === '/' || $path === 'home') return 'home';
        $segments = explode('/', trim($path, '/'));
        return $segments[0] ?? '';
    }

    private function getTitle(Request $request): string
    {
        $path = $request->path();
        $locale = app()->getLocale();

        if ($path === '/' || $path === 'home') {
            return $locale === 'en' ? 'Home' : 'Accueil';
        }

        $route = $request->route();
        $routeName = $route ? $route->getName() : null; // ex: parametrage.cycles_enseignement.index

        // Cache par route/path complet (et non plus par 1er segment) + locale :
        // sinon toutes les pages d'un même module partagent le même titre.
        $cacheKey = 'title_' . ($routeName ?: $path) . '_' . $locale;

        return Cache::remember($cacheKey, 600, function () use ($path, $routeName, $locale) {
            // Construit une liste de clés candidates, de la plus précise à la plus large.
            $candidates = [];

            if ($routeName) {
                $parts = explode('.', $routeName);
                $actions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'statut', 'activate'];
                if (in_array(end($parts), $actions, true)) {
                    array_pop($parts);
                }
                $key = end($parts); // ex: cycles_enseignement
                if ($key) {
                    $candidates[] = $key;
                }
            }

            $segments = array_values(array_filter(explode('/', trim($path, '/'))));
            if (!empty($segments)) {
                $candidates[] = end($segments); // dernier segment d'URL
            }
            $candidates[] = $path; // chemin complet

            // Normalise pour absorber les variations - / _ et pluriels (-s)
            $norm = fn($s) => preg_replace('/s$/', '', str_replace(['-', '_'], '', strtolower((string) $s)));

            // 1) Correspondance exacte sur menu_url
            $feature = Feature::whereIn('menu_url', array_unique($candidates))->first();

            // 2) Correspondance "souple" (normalisée) si rien trouvé
            if (!$feature) {
                $normCandidates = array_map($norm, $candidates);
                $feature = Feature::all()->first(
                    fn($f) => in_array($norm($f->menu_url), $normCandidates, true)
                );
            }

            if ($feature) {
                return $locale === 'fr'
                    ? $feature->libelle
                    : ($feature->libelle_en ?: $feature->libelle);
            }

            // 3) Repli : on humanise la clé canonique (route name) ou le dernier segment.
            $base = $candidates[0] ?? (end($segments) ?: $path);
            return ucwords(str_replace(['-', '_'], ' ', $base));
        });
    }

    protected function getBasicSettings(): array
    {
        return [
            'base_color' => '#3498DB',
            'app_name' => config('app.name', 'AGREE SIKUL'),
        ];
    }
}
