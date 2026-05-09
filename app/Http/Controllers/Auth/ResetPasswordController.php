<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ResetPasswordController extends Controller
{
    /**
     * Afficher le formulaire de réinitialisation
     */
    public function showResetForm(Request $request, $token): Response
    {
        // Chercher le login dans 'login' ou 'email' (pour compatibilité)
        $login = $request->query('login') ?? $request->query('email') ?? '';

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'login' => $login ? urldecode($login) : '',
            'status' => session('status'),
        ]);
    }

    /**
     * Réinitialiser le mot de passe
     */
    public function reset(Request $request)
    {
        $data = $request->all();

        // Si le login ne commence pas par '+', on le remet
        if (isset($data['login']) && !str_starts_with($data['login'], '+')) {
            $data['login'] = '+' . ltrim($data['login'], '+');
        }

        // Remettre les données corrigées dans le request
        $request->merge($data);

        $request->validate([
            'login' => 'required|exists:users,full_login',
            'token' => 'required',
            'password' => 'required|min:5|confirmed',
        ], [
            'login.required' => "L'identifiant est requis.",
            'login.exists' => "Aucun compte n'est associé à cet identifiant.",
            'token.required' => 'Le token est requis.',
            'password.required' => 'Le mot de passe est requis.',
            'password.min' => 'Le mot de passe doit contenir au moins 5 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ]);

        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->login)
            ->where('token', $request->token)
            ->first();

        if (!$reset || Carbon::parse($reset->created_at)->addMinutes(60)->isPast()) {
            return back()->withErrors([
                'token' => 'Lien de réinitialisation invalide ou expiré.'
            ]);
        }

        $user = User::where('full_login', $request->login)->first();

        if (!$user) {
            return back()->withErrors([
                'login' => 'Utilisateur non trouvé.'
            ]);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->login)->delete();

        return redirect()->route('login')->with('status', 'Votre mot de passe a été réinitialisé avec succès.');
    }
}
