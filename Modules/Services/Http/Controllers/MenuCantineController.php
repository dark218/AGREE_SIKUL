<?php

namespace Modules\Services\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Services\Entities\MenuCantine;
use Modules\Services\Entities\ServiceCantine;
use Carbon\Carbon;

class MenuCantineController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission.check:menu-cantine-list', ['only' => ['index']]);
        $this->middleware('permission.check:menu-cantine-create', ['only' => ['create', 'store', 'storeWeek']]);
        $this->middleware('permission.check:menu-cantine-edit', ['only' => ['edit', 'update', 'updateWeek']]);
        $this->middleware('permission.check:menu-cantine-delete', ['only' => ['destroy', 'statut']]);
    }

    public function index(Request $request)
    {
        $query = MenuCantine::query();

        if ($request->filled('service_cantine_id')) {
            $query->where('service_cantine_id', $request->service_cantine_id);
        }

        if ($request->filled('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $menus = $query->with('serviceCantine')
            ->orderBy('week_start_date', 'desc')
            ->paginate(10)
            ->through(fn($menu) => [
                'id' => $menu->id,
                'week_name' => $menu->week_name,
                'week_start_date' => $menu->week_start_date?->toDateString(),
                'week_end_date' => $menu->week_end_date?->toDateString(),
                'jour' => $menu->jour,
                'service_cantine_nom' => $menu->serviceCantine?->nom,
                'statut' => $menu->statut,
            ]);

        $servicesCantines = ServiceCantine::where('statut', 'actif')
            ->get(['id', 'nom'])
            ->toArray();

        return Inertia::render('Services::MenusCantines/Index', [
            'menus' => $menus,
            'servicesCantines' => $servicesCantines,
            'filters' => [
                'service_cantine_id' => $request->service_cantine_id,
                'week_start_date' => $request->week_start_date,
                'statut' => $request->statut,
            ],
        ]);
    }

    public function create()
    {
        $servicesCantines = ServiceCantine::where('statut', 'actif')
            ->get(['id', 'nom as libelle'])
            ->toArray();

        return Inertia::render('Services::MenusCantines/Create', [
            'servicesCantines' => $servicesCantines,
        ]);
    }

    public function store(Request $request)
    {
        // Store single menu item
        $validated = $request->validate([
            'service_cantine_id' => 'nullable|exists:service_cantines,id',
            'week_start_date' => 'required|date',
            'week_end_date' => 'required|date',
            'week_name' => 'required|string',
            'jour' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'entree' => 'nullable|string',
            'plat' => 'nullable|string',
            'dessert' => 'nullable|string',
            'remarques' => 'nullable|string',
            'statut' => 'required|in:actif,inactif',
        ]);

        $validated['creation_username'] = auth()->user()->name;
        $validated['week_number'] = Carbon::parse($validated['week_start_date'])->weekOfYear;
        $validated['year'] = Carbon::parse($validated['week_start_date'])->year;

        MenuCantine::create($validated);

        return redirect()->route('menu-cantines.index')->with('success', 'Menu créé avec succès');
    }

    public function storeWeek(Request $request)
    {
        $validated = $request->validate([
            'service_cantine_id' => 'nullable|exists:service_cantines,id',
            'week_start_date' => 'required|date',
            'week_end_date' => 'required|date',
            'week_name' => 'required|string',
            'menus' => 'required|array',
            'menus.*.jour' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'menus.*.entree' => 'nullable|string',
            'menus.*.plat' => 'nullable|string',
            'menus.*.dessert' => 'nullable|string',
            'menus.*.remarques' => 'nullable|string',
            'statut' => 'required|in:actif,inactif',
        ]);

        $weekStartDate = Carbon::parse($validated['week_start_date']);
        $weekNumber = $weekStartDate->weekOfYear;
        $year = $weekStartDate->year;

        // Delete existing menus for this week
        MenuCantine::where('service_cantine_id', $validated['service_cantine_id'])
            ->where('week_start_date', $validated['week_start_date'])
            ->delete();

        // Create menus for each day
        foreach ($validated['menus'] as $menu) {
            if (empty($menu['entree']) && empty($menu['plat']) && empty($menu['dessert'])) {
                continue;
            }

            MenuCantine::create([
                'service_cantine_id' => $validated['service_cantine_id'],
                'week_start_date' => $validated['week_start_date'],
                'week_end_date' => $validated['week_end_date'],
                'week_name' => $validated['week_name'],
                'week_number' => $weekNumber,
                'year' => $year,
                'jour' => $menu['jour'],
                'entree' => $menu['entree'],
                'plat' => $menu['plat'],
                'dessert' => $menu['dessert'],
                'remarques' => $menu['remarques'],
                'statut' => $validated['statut'],
                'creation_username' => auth()->user()->name,
            ]);
        }

        return redirect()->route('menu-cantines.index')->with('success', 'Menu de la semaine créé avec succès');
    }

    public function show(MenuCantine $menuCantine)
    {
        // Get all menus for this week
        $weekMenus = MenuCantine::where('week_start_date', $menuCantine->week_start_date)
            ->where('service_cantine_id', $menuCantine->service_cantine_id)
            ->whereNull('deleted_at')
            ->get();

        return Inertia::render('Services::MenusCantines/Show', [
            'menu' => $menuCantine,
            'weekMenus' => $weekMenus,
            'serviceCantine' => $menuCantine->serviceCantine,
        ]);
    }

    public function edit(MenuCantine $menuCantine)
    {
        $weekMenus = MenuCantine::where('week_start_date', $menuCantine->week_start_date)
            ->where('service_cantine_id', $menuCantine->service_cantine_id)
            ->whereNull('deleted_at')
            ->get();

        $servicesCantines = ServiceCantine::where('statut', 'actif')
            ->get(['id', 'nom as libelle'])
            ->toArray();

        return Inertia::render('Services::MenusCantines/Edit', [
            'menu' => $menuCantine,
            'weekMenus' => $weekMenus,
            'servicesCantines' => $servicesCantines,
        ]);
    }

    public function update(Request $request, MenuCantine $menuCantine)
    {
        $validated = $request->validate([
            'service_cantine_id' => 'nullable|exists:service_cantines,id',
            'week_start_date' => 'required|date',
            'week_end_date' => 'required|date',
            'week_name' => 'required|string',
            'jour' => 'required|in:lundi,mardi,mercredi,jeudi,vendredi,samedi',
            'entree' => 'nullable|string',
            'plat' => 'nullable|string',
            'dessert' => 'nullable|string',
            'remarques' => 'nullable|string',
            'statut' => 'required|in:actif,inactif',
        ]);

        $validated['modification_username'] = auth()->user()->name;
        $menuCantine->update($validated);

        return redirect()->route('menu-cantines.show', $menuCantine)->with('success', 'Menu modifié avec succès');
    }

    public function destroy(MenuCantine $menuCantine)
    {
        $menuCantine->delete();
        return redirect()->route('menu-cantines.index')->with('success', 'Menu supprimé avec succès');
    }

    public function statut(MenuCantine $menuCantine)
    {
        $menuCantine->update([
            'statut' => $menuCantine->statut === 'actif' ? 'inactif' : 'actif',
            'modification_username' => auth()->user()->name,
        ]);

        return redirect()->route('menu-cantines.index')->with('success', 'Statut modifié avec succès');
    }

    public function exportPdf(MenuCantine $menuCantine)
    {
        $weekMenus = MenuCantine::where('week_start_date', $menuCantine->week_start_date)
            ->where('service_cantine_id', $menuCantine->service_cantine_id)
            ->whereNull('deleted_at')
            ->get()
            ->groupBy('jour');

        $jours = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.menu_cantine', [
            'menu' => $menuCantine,
            'weekMenus' => $weekMenus,
            'jours' => $jours,
            'serviceCantine' => $menuCantine->serviceCantine,
        ]);

        $filename = 'menu-cantine-' . $menuCantine->week_name . '.pdf';
        return $pdf->download($filename);
    }
}
