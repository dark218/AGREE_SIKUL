<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\SendPushNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Http\HttpClientOptions;

class NotificationService
{
    protected static $messaging;

    public static function init()
    {
        $firebaseCredentials = base_path(config('services.firebase.credentials'));

        if (!file_exists($firebaseCredentials)) {
            throw new \Exception('Firebase credentials are missing.');
        }

        // ⚠️ DISABLE SSL VERIFICATION (DEV ONLY)
        $httpClientOptions = HttpClientOptions::default()
            ->withGuzzleConfigOption('verify', false);

        $factory = (new Factory)
            ->withServiceAccount($firebaseCredentials)
            ->withHttpClientOptions($httpClientOptions);

        self::$messaging = $factory->createMessaging();
    }


    public static function sendNotification($userId, $title, $body, $data = null): JsonResponse
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => "Cet utilisateur n'existe pas."
            ], 404);
        }

        // Enregistrement en base
        $notif = new Notification();
        $notif->title = $title;
        $notif->body = $body;
        $notif->data = $data ? json_encode($data) : null;
        $notif->user_id = $userId;
        $notif->notifiable_type = User::class;
        $notif->notifiable_id = $userId;
        $notif->type = 'App\Notifications\GeneralNotification';
        $notif->save();

        // Récupérer TOUS les tokens FCM des sessions actives de cet utilisateur
        $fcmTokens = \DB::table('sessions')
            ->where('user_id', $userId)
            ->whereNotNull('fcm_token')
            ->where('fcm_token', '!=', '')
            ->where('last_activity', '>=', now()->subDays(7)->timestamp)
            ->pluck('fcm_token')
            ->unique()
            ->filter()
            ->values();

        if ($fcmTokens->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => "Cet utilisateur n'a pas de token FCM Web enregistré. Notification sauvegardée uniquement."
            ]);
        }

        // Initialisation Firebase si nécessaire
        if (!self::$messaging) {
            self::init();
        }

        $successCount = 0;
        $failureCount = 0;

        // Envoyer à tous les tokens
        foreach ($fcmTokens as $token) {
            try {
                // Envoyer SEULEMENT data (pas notification) pour éviter les doublons
                // NotificationManager.vue gère l'affichage au premier plan
                $message = CloudMessage::withTarget('token', $token)
                    ->withData([
                        'title' => $title,
                        'body' => $body,
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        'user_id' => (string)$userId,
                        ...($data ?? []),
                    ]);

                self::$messaging->send($message);
                $successCount++;
            } catch (\Throwable $e) {
                $failureCount++;

                // Si le token est invalide (NotRegistered, InvalidRegistration), le supprimer
                if (str_contains($e->getMessage(), 'NotRegistered') || str_contains($e->getMessage(), 'InvalidRegistration')) {
                    \DB::table('sessions')
                        ->where('fcm_token', $token)
                        ->update(['fcm_token' => null]);

                    Log::warning('Token FCM invalide supprimé', [
                        'user_id' => $userId,
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage()
                    ]);
                } else {
                    Log::error('Erreur FCM push notification', [
                        'user_id' => $userId,
                        'token' => substr($token, 0, 20) . '...',
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        return response()->json([
            'status' => $successCount > 0,
            'message' => $successCount > 0
                ? "Notification envoyée avec succès à {$successCount} appareil(s)."
                : "Erreur lors de l'envoi FCM à tous les appareils."
        ]);
    }


}
