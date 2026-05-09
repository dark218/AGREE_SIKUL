<?php

namespace Modules\Wallet\Services;

use Modules\Parametrage\Entities\PaysDevise;
use Modules\Wallet\Entities\Wallet;

class WalletService
{
    /**
     * Créer les wallets pour un propriétaire selon son pays
     */
    public static function createWalletsForOwner(int $ownerId, string $ownerType, int $paysId): array
    {
        $wallets = [];

        // Vérifier si le rôle peut avoir des wallets
        if (!Wallet::peutAvoirWallet($ownerType)) {
            return $wallets;
        }

        // Récupérer toutes les devises du pays
        $paysDevises = PaysDevise::where('pays_id', $paysId)->get();

        foreach ($paysDevises as $paysDevise) {
            $wallet = Wallet::create([
                'owner_id' => $ownerId,
                'owner_type' => self::getOwnerTypeConstant($ownerType),
                'pays_devise_id' => $paysDevise->id,
                'solde_cents' => 0,
                'solde_bloque_cents' => 0,
                'solde_commission_cents' => 0,
                'solde_attente_cents' => 0,
                'statut' => Wallet::STATUT_ACTIF,
            ]);

            $wallets[] = $wallet;
        }

        return $wallets;
    }

    /**
     * Obtenir la constante owner_type selon le rôle
     */
    private static function getOwnerTypeConstant(string $role): string
    {
        return match($role) {
            'agent' => Wallet::OWNER_TYPE_AGENT,
            'marchand' => Wallet::OWNER_TYPE_MARCHAND,
            'client' => Wallet::OWNER_TYPE_CLIENT,
            default => Wallet::OWNER_TYPE_UTILISATEUR,
        };
    }
}
