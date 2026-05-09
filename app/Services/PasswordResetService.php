<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetService
{
    /**
     * Envoie un email personnalisé de réinitialisation de mot de passe à l'utilisateur.
     */
    public function sendResetLink(User $user): void
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => $token,
                'created_at' => Carbon::now(),
            ]
        );

        $url = route('password.reset', [
            'token' => $token,
            'email' => urlencode($user->email)
        ]);

        Mail::send('emails.reset_password', [
            'url' => $url,
            'user' => $user
        ], function ($message) use ($user) {
            $message->to($user->email);
            $message->subject('Création de votre compte - Veuillez définir votre mot de passe');
        });
    }

    public static function sendResetLinkSms(User $user): bool
    {

        try {
            // 1) Génération / upsert du token
            $token = Str::random(64);

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->full_login],
                [
                    'token' => $token,
                    'created_at' => Carbon::now(),
                ]
            );

            // 2) URL de réinitialisation (même route que pour l’email)
            $url = route('password.reset', [
                'token' => $token,
                'login' => urlencode($user->full_login),
            ]);


            // 3) Numéro cible (on passe le full international ; le service supprime déjà le '+')
            $recipient = $user->full_login ?? $user->login;

            // 4) Message SMS
            $message = "Bonjour {$user->fullName()},\n"
                . "Votre login est : {$user->login}\n"
                . "Pour définir un nouveau mot de passe, cliquez sur le lien :\n"
                . "{$url}\n"
                . "Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.";

            // 5) Envoi
            $sent = SMSApiProService::sendNewSms($recipient, $message);

            return (bool) $sent;
        } catch (\Throwable $e) {
            // log applicatif si besoin
            (new \Modules\Administration\Http\Controllers\ErrorLogController())
                ->create("Auth", "sendResetLinkSms", $e->getMessage());
            return false;
        }
    }
}
