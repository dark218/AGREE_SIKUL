<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Generator
{
    /**
     * Génère une chaîne aléatoire de la longueur spécifiée
     *
     * @param int $length Longueur de la chaîne à générer (par défaut: 6)
     * @param bool $numbersOnly Si vrai, génère uniquement des chiffres
     * @return string
     */
    public static function generateRandomString(int $length = 6, bool $numbersOnly = false): string
    {
        if ($numbersOnly) {
            return substr(str_shuffle(str_repeat('0123456789', 5)), 0, $length);
        }

        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }

        return $randomString;
    }
    public static function codeOwner(): string
    {
        return Str::random(6);
    }

    public static function QrCode(string $login): string
    {
        return Hash::make($login . rand(0, 1000));
    }

    public static function transactionId(): string
    {
        return Str::uuid()->toString();
    }
    public static function uuid(): string
    {
        return Str::uuid()->toString();
    }


    public static function codeRetrait(): int
    {
        return rand(100000, 999999);
    }
    public static function generateQr($val)
    {
        return  "https://quickchart.io/qr?text=".$val."&size=200&centerImageUrl=http%3A%2F%2Fqrpayme.niqj4716.odns.fr%2Fassets%2Fimages%2Fsmile%2Ficone-3.png";
    }

    /**
     * Génère un alias SMIL unique basé sur le nom et prénom.
     * Format: prenom.nom ou prenom.nom1, prenom.nom2, etc. si déjà existant
     *
     * @param string $nom Le nom de l'utilisateur
     * @param string $prenoms Les prénoms de l'utilisateur
     * @param int|null $excludeUserId ID de l'utilisateur à exclure (pour l'édition)
     * @return string
     */
    public static function generateAliasSmil(string $nom, string $prenoms, ?int $excludeUserId = null): string
    {
        // Nettoyer et normaliser le nom et prénom
        $cleanNom = Str::of($nom)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z]/u', '')
            ->toString();

        // Prendre le premier prénom seulement
        $firstPrenom = Str::of($prenoms)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z\s]/u', '')
            ->explode(' ')
            ->first();

        $cleanPrenom = Str::of($firstPrenom ?? '')
            ->replaceMatches('/[^a-z]/u', '')
            ->toString();

        // Si nom ou prénom vide, utiliser des valeurs par défaut
        if (empty($cleanNom)) {
            $cleanNom = 'user';
        }
        if (empty($cleanPrenom)) {
            $cleanPrenom = 'smil';
        }

        // Base de l'alias: prenom.nom
        $baseAlias = "{$cleanPrenom}.{$cleanNom}";

        // Vérifier l'unicité et incrémenter si nécessaire
        $suffix = 0;
        do {
            $candidate = $suffix === 0 ? $baseAlias : "{$baseAlias}{$suffix}";

            $query = User::where('alias_smil', $candidate);

            // Exclure l'utilisateur actuel lors de l'édition
            if ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            }

            $exists = $query->exists();
            $suffix++;
        } while ($exists);

        return $suffix === 1 ? $baseAlias : "{$baseAlias}" . ($suffix - 1);
    }

    /**
     * Vérifie si un alias SMIL est disponible.
     *
     * @param string $alias L'alias à vérifier
     * @param int|null $excludeUserId ID de l'utilisateur à exclure (pour l'édition)
     * @return bool
     */
    public static function isAliasSmilAvailable(string $alias, ?int $excludeUserId = null): bool
    {
        $query = User::where('alias_smil', $alias);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        return !$query->exists();
    }
}
