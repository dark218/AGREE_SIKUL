<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    /**
     * Afficher le formulaire de demande de reset
     */
    public function showLinkRequestForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Envoyer le lien de réinitialisation par email
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'email.exists' => 'Aucun compte n\'est associé à cette adresse email.',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $resetUrl = route('custompassword.reset', [
            'token' => $token, 
            'email' => urlencode($request->email)
        ]);

        // Envoi du mail
        Mail::send('emails.reset_password', ['url' => $resetUrl], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Réinitialisation de votre mot de passe');
        });

        return back()->with('status', 'Un lien de réinitialisation vous a été envoyé par mail.');
    }

    /**
     * Envoyer le lien pour mot de passe oublié
     */
    public function sendLinkForgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.required' => 'L\'adresse email est requise.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
            'email.exists' => 'Aucun compte n\'est associé à cette adresse email.',
        ]);

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => Carbon::now()]
        );

        $resetUrl = route('custompassword.reset', [
            'token' => $token, 
            'email' => urlencode($request->email)
        ]);

        // Envoi du mail
        Mail::send('emails.link_reset', ['url' => $resetUrl], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Réinitialisation de votre mot de passe');
        });

        return back()->with('status', 'Un lien de réinitialisation vous a été envoyé par mail.');
    }
}
