<?php

namespace App\Services;

use Illuminate\Support\Str;
use Modules\Pos\Entities\QrCode;

class QrCodeService
{

    /**
     * Génère un QR statique (permanent)
     */
    public static function generateStatique(
        int $pointVente,
        array $payload
    ): QrCode {
        return QrCode::create([
            'points_vente_id' => $pointVente,
            'type'           => 'statique',
            'uuid'           => Str::uuid(),
            'payload_json'   => $payload,
            'actif'          => 1,
        ]);
    }
    /**
     * Génère un QR dynamique (transactionnel)
     */
    public static function generateDynamique(
        int $pointVente,
        array $payload,
        int $montantCents,
        int $devise,
        int $ttlMinutes = 10
    ): QrCode {
        return QrCode::create([
            'points_vente_id' => $pointVente,
            'type'           => 'dynamique',
            'uuid'           => Str::uuid(),
            'payload_json'   => $payload,
            'montant_cents'  => $montantCents,
            'devise_id'         => $devise,
            'expire_at'      => now()->addMinutes($ttlMinutes),
            'used'           => false,
            'actif'          => 1,
        ]);
    }

}
