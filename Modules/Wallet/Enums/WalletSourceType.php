<?php

namespace Modules\Wallet\Enums;

final class WalletSourceType
{
    public const TRANSACTION   = 'transaction';
    public const REMBOURSEMENT = 'remboursement';
    public const REGLEMENT     = 'reglement';
    public const COMMISSION    = 'commission';
    public const INVENTAIRE    = 'inventaire';
    public const AJUSTEMENT    = 'ajustement';

    public static function all(): array
    {
        return [
            self::TRANSACTION,
            self::REMBOURSEMENT,
            self::REGLEMENT,
            self::COMMISSION,
            self::INVENTAIRE,
            self::AJUSTEMENT,
        ];
    }
}