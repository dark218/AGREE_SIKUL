<?php

namespace Modules\Services\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Services\Entities\Menu;
use Carbon\Carbon;

class MenuController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:menus-list', ['only' => ['index', 'show']]);
        $this->middleware('permission.check:menus-create', ['only' => ['create', 'store']]);
        $this->middleware('permission.check:menus-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission.check:menus-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        try {
            // Get first menu of each week group
            $query = Menu::query()
                ->selectRaw('MIN(id) as id, week_start_date, week_name, statut, MAX(created_at) as created_at')
                ->groupBy('week_start_date', 'week_name', 'statut');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where('week_name', 'like', "%$search%");
            }

            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }

            $menus = $query->orderBy('week_start_date', 'desc')
                ->paginate(10)
                ->withQueryString();

            return Inertia::render('Services::Menus/Index', [
                'menus' => $menus,
                'filters' => $request->only(['search', 'statut']),
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "MenuController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function create()
    {
        try {
            return Inertia::render('Services::Menus/Create');
        } catch (\Throwable $th) {
            log_error("Services", "MenuController::create", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function store(Request $request)
    {
        try {
            // Debug logging
            \Log::info('MenuController::store - Request data:', $request->all());

            $validated = $request->validate([
                'weeks' => 'required|array',
                'weeks.*.week_start_date' => 'required|date',
                'weeks.*.week_end_date' => 'nullable|date',
                'weeks.*.week_name' => 'nullable|string|max:255',
                'weeks.*.menus' => 'nullable|array',
                'weeks.*.statut' => 'required|in:actif,inactif',
            ]);

            \Log::info('MenuController::store - Validated data:', $validated);

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

            foreach ($validated['weeks'] as $weekData) {
                $startDate = Carbon::createFromFormat('Y-m-d', $weekData['week_start_date']);

                foreach ($jours as $index => $jour) {
                    $currentDate = $startDate->clone()->addDays($index);
                    $menuData = $weekData['menus'][$jour][0] ?? [];

                    // Create menu records for all days, even if empty (will display as empty in views)
                    Menu::create([
                        'week_start_date' => $weekData['week_start_date'],
                        'week_end_date' => $weekData['week_end_date'] ?? $startDate->clone()->addDays(5)->toDateString(),
                        'week_name' => $weekData['week_name'],
                        'jour' => $jour,
                        'date' => $currentDate->toDateString(),
                        'plat_principal' => $menuData['plat'] ?? null,
                        'accompagnement' => $menuData['entree'] ?? null,
                        'dessert' => $menuData['dessert'] ?? null,
                        'remarques' => $menuData['remarques'] ?? null,
                        'statut' => $weekData['statut'],
                    ]);
                }
            }

            \Log::info('MenuController::store - Created ' . count($validated['weeks']) . ' weeks with ' . (count($validated['weeks']) * 6) . ' daily menus');

            \Log::info('MenuController::store - Created ' . count($validated['weeks']) . ' weeks with ' . (count($validated['weeks']) * 6) . ' daily menus');

            return redirect()->route('menus.index')
                ->with('success', __('messages.created_successfully'));

        } catch (\Throwable $th) {
            \Log::error('MenuController::store - EXCEPTION: ' . $th->getMessage());
            \Log::error('MenuController::store - FILE: ' . $th->getFile() . ':' . $th->getLine());
            \Log::error('MenuController::store - TRACE: ' . $th->getTraceAsString());
            log_error("Services", "MenuController::store", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function show($menu)
    {
        try {
            // Manually fetch the menu instead of relying on model binding
            $menu = Menu::find($menu);

            if (!$menu) {
                \Log::error('Menu not found with ID: ' . $menuId);
                return back()->with('error', __('messages.error_occurred'));
            }

            // Load all menus for this week
            $weekMenus = Menu::where('week_start_date', $menu->week_start_date)
                ->orderBy('jour', 'asc')
                ->get()
                ->groupBy('jour');

            // Reconstruct form-like structure
            $formData = [
                'id' => $menu->id,
                'week_start_date' => $menu->week_start_date ? (is_string($menu->week_start_date) ? $menu->week_start_date : $menu->week_start_date->toDateString()) : '',
                'week_end_date' => $menu->week_end_date ? (is_string($menu->week_end_date) ? $menu->week_end_date : $menu->week_end_date->toDateString()) : '',
                'week_name' => $menu->week_name ?? '',
                'statut' => $menu->statut ?? 'actif',
                'menus' => [],
            ];

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            foreach ($jours as $jour) {
                $formData['menus'][$jour] = [];
                if (isset($weekMenus[$jour])) {
                    foreach ($weekMenus[$jour] as $dayMenu) {
                        $formData['menus'][$jour][] = [
                            'entree' => $dayMenu->accompagnement ?? '',
                            'plat' => $dayMenu->plat_principal ?? '',
                            'dessert' => $dayMenu->dessert ?? '',
                            'remarques' => $dayMenu->remarques ?? '',
                        ];
                    }
                } else {
                    $formData['menus'][$jour][] = [
                        'entree' => '',
                        'plat' => '',
                        'dessert' => '',
                        'remarques' => '',
                    ];
                }
            }

            return Inertia::render('Services::Menus/Show', [
                'item' => $formData,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "MenuController::show", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function edit($menu)
    {
        try {
            // Manually fetch the menu instead of relying on model binding
            $menu = Menu::find($menu);

            if (!$menu) {
                \Log::error('Menu not found');
                return back()->with('error', __('messages.error_occurred'));
            }

            // Load all menus for this week
            $weekMenus = Menu::where('week_start_date', $menu->week_start_date)
                ->orderBy('jour', 'asc')
                ->get()
                ->groupBy('jour');

            // Reconstruct form-like structure
            $formData = [
                'id' => $menu->id,
                'week_start_date' => $menu->week_start_date ? $menu->week_start_date->toDateString() : '',
                'week_end_date' => $menu->week_end_date ? $menu->week_end_date->toDateString() : '',
                'week_name' => $menu->week_name ?? '',
                'statut' => $menu->statut ?? 'actif',
                'menus' => [],
            ];

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            foreach ($jours as $jour) {
                $formData['menus'][$jour] = [];
                if (isset($weekMenus[$jour])) {
                    foreach ($weekMenus[$jour] as $dayMenu) {
                        $formData['menus'][$jour][] = [
                            'entree' => $dayMenu->accompagnement,
                            'plat' => $dayMenu->plat_principal,
                            'dessert' => $dayMenu->dessert,
                            'remarques' => $dayMenu->remarques ?? '',
                        ];
                    }
                } else {
                    $formData['menus'][$jour][] = [
                        'entree' => '',
                        'plat' => '',
                        'dessert' => '',
                        'remarques' => '',
                    ];
                }
            }

            return Inertia::render('Services::Menus/Edit', [
                'item' => $formData,
            ]);
        } catch (\Throwable $th) {
            log_error("Services", "MenuController::edit", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function update(Request $request, $menu)
    {
        try {
            $menu = Menu::find($menu);
            if (!$menu) {
                return back()->with('error', __('messages.error_occurred'));
            }

            $validated = $request->validate([
                'week_start_date' => 'required|date',
                'week_end_date' => 'nullable|date',
                'week_name' => 'nullable|string|max:255',
                'menus' => 'nullable|array',
                'statut' => 'required|in:actif,inactif',
            ]);

            // Delete all menus for this week and recreate
            Menu::where('week_start_date', $menu->week_start_date)->delete();

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            $startDate = Carbon::createFromFormat('Y-m-d', $validated['week_start_date']);

            foreach ($jours as $index => $jour) {
                $currentDate = $startDate->clone()->addDays($index);
                $menuData = $validated['menus'][$jour][0] ?? [];

                // Create menu records for all days, even if empty (matches store() behavior)
                Menu::create([
                    'week_start_date' => $validated['week_start_date'],
                    'week_end_date' => $validated['week_end_date'] ?? $startDate->clone()->addDays(5)->toDateString(),
                    'week_name' => $validated['week_name'],
                    'jour' => $jour,
                    'date' => $currentDate->toDateString(),
                    'plat_principal' => $menuData['plat'] ?? null,
                    'accompagnement' => $menuData['entree'] ?? null,
                    'dessert' => $menuData['dessert'] ?? null,
                    'remarques' => $menuData['remarques'] ?? null,
                    'statut' => $validated['statut'],
                ]);
            }

            return redirect()->route('menus.index')
                ->with('success', __('messages.updated_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "MenuController::update", $th->getMessage());
            return back()->withErrors(['error' => $th->getMessage()]);
        }
    }

    public function destroy($menu)
    {
        try {
            $menu = Menu::find($menu);
            if (!$menu) {
                return back()->with('error', __('messages.error_occurred'));
            }
            // Delete all menus for this week
            Menu::where('week_start_date', $menu->week_start_date)->delete();

            return back()->with('success', __('messages.deleted_successfully'));

        } catch (\Throwable $th) {
            log_error("Services", "MenuController::destroy", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function statut($menu)
    {
        try {
            $menu = Menu::find($menu);
            if (!$menu) {
                return back()->with('error', __('messages.error_occurred'));
            }
            // Toggle statut for all menus in this week
            $newStatut = $menu->statut === 'actif' ? 'inactif' : 'actif';
            Menu::where('week_start_date', $menu->week_start_date)
                ->update(['statut' => $newStatut]);

            return redirect()->route('menus.index')
                ->with('success', __('messages.status_changed'));

        } catch (\Throwable $th) {
            log_error("Services", "MenuController::statut", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }

    public function exportPdf($menu)
    {
        try {
            $menu = Menu::find($menu);
            if (!$menu) {
                return back()->with('error', __('messages.error_occurred'));
            }

            // Load all menus for this week
            $weekMenus = Menu::where('week_start_date', $menu->week_start_date)
                ->orderBy('jour', 'asc')
                ->get()
                ->groupBy('jour');

            $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('services.menus_pdf', [
                'menu' => $menu,
                'weekMenus' => $weekMenus,
                'jours' => $jours,
            ]);

            $filename = 'menu-' . $menu->week_name . '.pdf';
            return $pdf->download($filename);

        } catch (\Throwable $th) {
            log_error("Services", "MenuController::exportPdf", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
