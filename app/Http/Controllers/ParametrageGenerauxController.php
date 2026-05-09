<?php

namespace App\Http\Controllers;

use App\Models\ParametrageGeneral;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ParametrageGenerauxController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $config = ParametrageGeneral::get();

        return Inertia::render('Administration/ParametrageGeneraux', [
            'config' => $config,
            'title' => 'Paramétrage Généraux',
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'nom_etablissement' => 'required|string|max:255',
            'slogan' => 'nullable|string|max:255',
            'sigle' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:30',
            'telephone2' => 'nullable|string|max:30',
            'whatsapp' => 'nullable|string|max:30',
            'site_web' => 'nullable|string|max:255',
            'adresse' => 'nullable|string|max:500',
            'ville' => 'nullable|string|max:100',
            'pays' => 'nullable|string|max:100',
            'code_postal' => 'nullable|string|max:20',
            'boite_postale' => 'nullable|string|max:50',
            'facebook' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
            'linkedin' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
            'youtube' => 'nullable|string|max:255',
            'numero_autorisation' => 'nullable|string|max:255',
            'registre_commerce' => 'nullable|string|max:255',
            'numero_fiscal' => 'nullable|string|max:255',
            'couleur_primaire' => 'nullable|string|max:10',
            'couleur_secondaire' => 'nullable|string|max:10',
            'couleur_accent' => 'nullable|string|max:10',
            'devise' => 'nullable|string|max:10',
            'langue_par_defaut' => 'nullable|string|max:5',
            'fuseau_horaire' => 'nullable|string|max:50',
            'annee_scolaire_courante' => 'nullable|integer',
            'logo' => 'nullable|image|max:2048',
            'favicon' => 'nullable|image|max:1024',
            'logo_login' => 'nullable|image|max:2048',
        ]);

        $config = ParametrageGeneral::get();

        // Upload logo
        if ($request->hasFile('logo') && $request->file('logo')->isValid()) {
            $file = $request->file('logo');
            $name = 'logo-' . time() . '.' . $file->getClientOriginalExtension();
            $dest = public_path('images');
            if (!file_exists($dest)) mkdir($dest, 0755, true);
            copy($file->getRealPath(), $dest . '/' . $name);
            $validated['logo_path'] = 'images/' . $name;
        }
        unset($validated['logo']);

        // Upload favicon
        if ($request->hasFile('favicon') && $request->file('favicon')->isValid()) {
            $file = $request->file('favicon');
            $name = 'favicon-' . time() . '.' . $file->getClientOriginalExtension();
            copy($file->getRealPath(), public_path('images') . '/' . $name);
            $validated['favicon_path'] = 'images/' . $name;
        }
        unset($validated['favicon']);

        // Upload logo login
        if ($request->hasFile('logo_login') && $request->file('logo_login')->isValid()) {
            $file = $request->file('logo_login');
            $name = 'logo-login-' . time() . '.' . $file->getClientOriginalExtension();
            copy($file->getRealPath(), public_path('images') . '/' . $name);
            $validated['logo_login_path'] = 'images/' . $name;
        }
        unset($validated['logo_login']);

        $config->update($validated);

        return redirect()->back()->with('success', 'Paramètres mis à jour avec succès');
    }
}
