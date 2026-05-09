<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserLoginService
{
    /**
     * Logger la connexion de l'utilisateur
     *
     * @param User $user
     * @param array $deviceInfo
     * @return void
     */
    public static function logUserLogin(User $user, array $deviceInfo): void
    {
        try {
            // Créer une entrée dans les logs de connexion
            \DB::table('user_login_logs')->insert([
                'user_id' => $user->id,
                'device_id' => $deviceInfo['device_id'] ?? null,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'login_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info('Connexion utilisateur enregistrée', [
                'user_id' => $user->id,
                'device_type' => $deviceInfo['device_type'] ?? null,
                'ip_address' => request()->ip(),
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de l\'enregistrement de la connexion', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Nettoyer les anciennes sessions OTP
     *
     * @param string $phoneNumber
     * @return void
     */
    public static function cleanupOldSessions(string $phoneNumber): void
    {
        try {
            \DB::table('otp_sessions')
                ->where('phone_number', $phoneNumber)
                ->delete();

            Log::info('Anciennes sessions OTP nettoyées', [
                'phone_number' => $phoneNumber,
            ]);

        } catch (\Throwable $e) {
            Log::error('Erreur lors du nettoyage des sessions OTP', [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Gérer la vérification OTP
     *
     * @param int $paysId
     * @param string $login
     * @return \Illuminate\Http\JsonResponse
     */
    public static function handleOtpVerification(int $paysId, string $login): \Illuminate\Http\JsonResponse
    {
        try {
            $pays = \Modules\Parametrage\Entities\Pays::find($paysId);
            if (!$pays) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pays non trouvé'
                ], 404);
            }

            $fullPhone = $pays->fullPhoneNumber($login);
            if (!$fullPhone) {
                return response()->json([
                    'status' => false,
                    'message' => 'Numéro de téléphone invalide'
                ], 400);
            }

            // Vérifier si une session OTP existe déjà
            $existingSession = \DB::table('otp_sessions')
                ->where('phone_number', $fullPhone)
                ->where('end_date', '>', now())
                ->first();

            if ($existingSession) {
                $remainingTime = $existingSession->end_date->diffInSeconds(now());
                $waitTime = config('services.sms.resend_delay', 45);

                if ($remainingTime > $waitTime) {
                    return response()->json([
                        'status' => false,
                        'message' => "Désolé, veuillez patienter {$remainingTime} secondes pour une autre tentative."
                    ], 429);
                }

                return response()->json([
                    'status' => true,
                    'data' => $existingSession->end_date
                ], 201);
            }

            // Générer et envoyer l'OTP
            $otp = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            $endDate = now()->addMinutes(config('services.sms.otp_expiration', 5));

            \DB::table('otp_sessions')->insert([
                'phone_number' => $fullPhone,
                'otp' => $otp,
                'end_date' => $endDate,
                'tentative' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // TODO: Envoyer l'OTP par SMS
            // $this->sendOtpSms($fullPhone, $otp);

            Log::info('OTP généré', [
                'phone_number' => $fullPhone,
                'otp' => $otp,
                'end_date' => $endDate,
            ]);

            return response()->json([
                'status' => true,
                'data' => $endDate
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Erreur lors de la génération de l\'OTP', [
                'pays_id' => $paysId,
                'login' => $login,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Désolé, erreur lors de l\'envoi de l\'OTP.'
            ], 500);
        }
    }
}
