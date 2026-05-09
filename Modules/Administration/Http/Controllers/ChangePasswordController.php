<?php

namespace Modules\Administration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use App\Http\Controllers\Controller;


class ChangePasswordController extends Controller
{
    /**
     * Affiche la page de changement de mot de passe
     */
    public function index()
    {
        return Inertia::render('Auth/ChangePassword', [
            'title' => 'Changer le mot de passe',
        ]);
    }

    /**
     * Met à jour le mot de passe
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'password' => [
                    'required',
                    'confirmed',
                    Password::min(8)
                        ->mixedCase()
                        ->letters()
                        ->numbers()
                        ->symbols()
                ],
                'password_confirmation' => 'required',
            ], [
                'current_password.required' => 'Le mot de passe actuel est requis.',
                'password.required' => 'Le nouveau mot de passe est requis.',
                'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
                'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
                'password_confirmation.required' => 'La confirmation du mot de passe est requise.',
            ]);

            $user = Auth::user();

            // Vérifier le mot de passe actuel
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors([
                    'current_password' => 'Le mot de passe actuel est incorrect.'
                ]);
            }
            // Mettre à jour le mot de passe
            $user->password = Hash::make($request->password);
            $user->save();

            return back()->with('success', 'Mot de passe modifié avec succès!');
        } catch (\Throwable $e) {
            log_error("ChangePassword", "changePassword", $e->getMessage());
            return back()->with('error', trans('erreurmaj'))->withInput();
        }
    }
}
