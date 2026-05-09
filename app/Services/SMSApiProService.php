<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Modules\Administration\Http\Controllers\ErrorLogController;

class SMSApiProService
{

    public static function sendSmsOTP($fullPhone)
    {
        try {
            $minute = env('SMS_OTP_SECONDE_TIME_OUT')/60;
            $otp = random_int(1000, 9999);
            $result = self::handle($fullPhone,
                "Votre code de vérification est : ".$otp."\nCe code est valable pendant ".$minute." minutes. Ne partagez pas ce code avec quiconque pour des raisons de sécurité.");
            if ($result) {
                return $otp;
            }else{
                return null;
            }
        }catch (GuzzleException $e){
            (new ErrorLogController())->create("SMS API", "sendSmsOTP", $e->getMessage());
            return null;
        }
    }

    private static  function handle($fullPhone, $message) {
        try {
            $fullPhone = ltrim($fullPhone, '+');
            $response = Http::withHeaders([
                'Authorization' => env('SMSPRO_API_AUTHORIZATION'),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post(env('SMSPRO_API_URL'), [
                "recipient" => $fullPhone,
                "sender_id" => env("SMSPRO_SENDER_NAME"),
                "type" => "plain",
                "message" => $message
            ]);
            if ($response->successful()) {
                return true;
            }else{
                return false;
            }
        }catch (GuzzleException $e){
            (new ErrorLogController())->create("SMS API", "sendSmsOTP", $e->getMessage());
            return null;
        }

    }
    public static function sendNewSms($fullPhone, $message) {
        $response = self::handle($fullPhone, $message);

        return $response;
    }
}
