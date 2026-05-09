<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Http;
use Modules\Administration\Http\Controllers\ErrorLogController;

class SMSApiServiceSecond
{

    public static function sendSmsOTP($fullPhone)
    {
        try {
            $fullPhone = ltrim($fullPhone, '+');
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
            log_error("SMSApiServiceSecond", "sendSmsOTP", $e->getMessage());
            return null;
        }
    }

    private static function handle($fullPhone, $message)
    {
        try {
            $fullPhone = ltrim($fullPhone, '+');
            // Récupération des identifiants de l'API Wirepick depuis le fichier .env
            $client = env('WIREPICK_CLIENT');
            $password = env('WIREPICK_PASSWORD');
            $senderId = env('WIREPICK_SENDER_ID');
            $flash = "No"; // Peut être paramétré dans .env si nécessaire

            // Encodage du message pour l'URL
            // ✅ Convertir le message en UTF-8 et encoder en URL pour préserver les accents
//            $message = mb_convert_encoding($message, 'UTF-8', 'auto');
            $encodedMessage = urlencode(mb_convert_encoding($message, 'UTF-8', 'auto'));



            // Construction de l'URL d'envoi
            $url = "http://api.wirepick.com/httpsms/send?client={$client}&password={$password}&phone={$fullPhone}&text={$encodedMessage}&from={$senderId}&Flash={$flash}";



            // Envoi de la requête GET à l'API Wirepick
            $response = Http::get($url);

            // Convertir la réponse XML en objet SimpleXMLElement
            $xmlResponse = simplexml_load_string($response->body());
            // Extraire le statut du message
            $status = (string) $xmlResponse->sms->status;


            // Vérifier si le SMS a été envoyé avec succès
            if ($status === "DLV" || $status === "ACT") {
                return true;
            } elseif ($status === "NSF") {
                (new ErrorLogController())->create("SMS API", "sendNewSms", "Échec d'envoi du SMS : Fonds insuffisants.");
                return false;
            } else {
                (new ErrorLogController())->create("SMS API", "sendNewSms", "Échec d'envoi du SMS : Statut inconnu - " . $status);
                return false;
            }
        } catch (\Exception $e) {
           log_error("SMSApiServiceSecond", "handle", $e->getMessage());
            return null;
        }
    }

    public static function sendNewSms($fullPhone, $message)
    {
        return self::handle($fullPhone, $message);
    }
}
