<?php

namespace Modules\RessourcesLogistique\Http\Controllers;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PersonnelLogistiqueController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {
        try {
            return Inertia::render('RessourcesLogistique::PersonnelLogistique/Index');
        } catch (\Throwable $th) {
            log_error("Logistique", "PersonnelLogistiqueController::index", $th->getMessage());
            return back()->with('error', __('messages.error_occurred'));
        }
    }
}
