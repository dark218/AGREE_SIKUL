<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\EmailLoginRequest;
use App\Models\OtpSession;
use App\Models\Pays;
use App\Models\User;
use App\Services\SMSApiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OtpController extends Controller
{
    public function phoneAuth(EmailLoginRequest $request): JsonResponse
    {
        $creds = $request->validated();
        $phone = $creds['phone'];
        $countryId = $creds['pays_id'];

        $user = User::where('login', $phone)
            ->where('pays_id', $countryId)
            ->first();
        if ($user) {
            return response()->json([
                'status' => true,
                "data" => "User existe."
            ], 404);
        } else {
            DB::beginTransaction();
            $currentTime = Carbon::now();

            $pays = Pays::find($countryId);
            $receiverNumber = $pays->fullPhoneNumber($phone);
            $otpSessions = OtpSession::where('phone_number', $receiverNumber)
                ->orderBy('end_date', 'desc')
                ->get();
            if ($otpSessions->first()) {
                $otpSession = $otpSessions->first();
                // Vérifier si la session est encore active
                if ($otpSession->end_date > $currentTime) {
                    // Vérifier le nombre de tentatives
                    if ($otpSession->tentative < 3) {
                        // Envoyer un nouveau code
                        $otp = (new SMSApiService)->sendSmsOTP($receiverNumber);

                        if ($otp) {
                            $otpSession->start_date = $currentTime;
                            $otpSession->otp = $otp;
                            $otpSession->end_date = $currentTime->addMinutes(3);
                            $otpSession->tentative++;
                            $otpSession->save();
                            DB::commit();
                            return response()->json([
                                'status' => true,
                                'data' => $otpSession,
                            ], 200);
                        } else {
                            DB::rollBack();
                            return response()->json([
                                'status' => false,
                                'message' => "Désolé, nous avons rencontré une erreur lors de l'envoi du code par SMS."
                            ], $e->status ?? 500);
                        }
                    } else {
                        return response()->json([
                            'status' => false,
                            'message' => "Vous avez atteint le nombre maximum de tentatives." .
                                " Veuillez attendre 2 minutes avant de demander un nouveau code."
                        ], 400);
                    }
                } else {
                    // La session OTP a expiré, vous pouvez créer une nouvelle session ici si nécessaire
                    $otp = (new SMSApiService)->sendSmsOTP($receiverNumber);
                    if ($otp) {
                        $otpSession->start_date = $currentTime;
                        $otpSession->end_date = $currentTime->addMinutes(3);
                        $otpSession->tentative = 1;
                        $otpSession->otp = $otp;
                        $otpSession->save();
                        DB::commit();
                        return response()->json([
                            'status' => true,
                            'data' => $otpSession,
                        ], 200);
                    } else {
                        DB::rollBack();
                        return response()->json([
                            "status" => false,
                            "message" => "Une erreur est survenue pendant l'envoi du SMS. Veuillez réessayer SVP."], $e->status ?? 500);
                    }
                }
            } else {
                // Supprimez toutes les sessions OTP existantes
                if ($otpSessions->count() > 0) $otpSessions->delete();

                // Aucune session OTP existante, créez-en une nouvelle et envoyez le code
                $otp = (new SMSApiService)->sendSmsOTP($receiverNumber);
                if ($otp) {
                    $otpSession = new OtpSession();
                    $otpSession->phone_number = $receiverNumber;
                    $otpSession->start_date = $currentTime;
                    $otpSession->end_date = $currentTime->addMinutes(3);
                    $otpSession->tentative = 1;
                    $otpSession->otp = $otp;
                    $otpSession->save();
                    DB::commit();
                    return response()->json([
                        'status' => true,
                        'data' => $otpSession,
                    ]);
                } else {
                    DB::rollBack();
                    return response()->json([
                        "status" => false,
                        "message" => "Une erreur est survenue pendant l'envoi du SMS. Veuillez réessayer SVP."]);
                }
            }
        }
    }

    public function checkOTP(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|string',
        ], [
            'email.required' => 'Le champ "Email" est obligatoire.',
            'email.email' => 'Le champ "Email" doit contenir une adresse email valide.',
            'otp.required' => 'Le champ "OTP" est obligatoire.',
            'otp.string' => 'Le champ "OTP" doit être une chaîne de caractères.',
        ]);

        $otpSession = OtpSession::where('adresse', $request->email)
            ->where('end_date', '>=', Carbon::now())
            ->first();

        if (!$otpSession) {
            return response()->json([
                'status' => false,
                'message' => "Cette session de code a expiré ou n'existe pas. Veuillez réessayer."
            ], 404);
        }

        if ($otpSession->tentative >= env("SMS_OTP_ATTEMPT", 3)) {
            return response()->json([
                'status' => false,
                'message' => "Vous avez dépassé le nombre maximum de tentatives pour un OTP. Veuillez réessayer."
            ], 403);
        }

        if ($otpSession->otp === $request->otp) {
            OtpSession::where('adresse', $request->email)->delete();

            return response()->json([
                'status' => true,
                'message' => "OTP valide."
            ]);
        }

        $otpSession->increment('tentative');

        return response()->json([
            'status' => false,
            'message' => "Code invalide. Veuillez réessayer."
        ], 400);
    }

}
