<?php

namespace App\Services;

use App\Models\User;
use Modules\Business\Entities\Employe;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Business\Entities\PointVente;
use Modules\GestionStock\Entities\TransfertStock;
use Modules\Pos\Entities\SessionCaisse;
use Modules\Pos\Entities\VentePos;

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
     * Génère un code employé unique.
     *
     * @param  string  $raisonSociale   Raison sociale du marchand
     * @param  string  $paysIso        ISO de pays (ex: CI, SN, FR) ou indicatif stocké
     * @param  int|null $marchandId     Pour scoper l'unicité (optionnel)
     * @return string
     */
    public static function generateEmployeeCode(
        string $raisonSociale,
        string $paysIso,
        ?int $marchandId = null
    ): string {

        // =========================
        // 1. Acronyme société (3 lettres)
        // =========================
        $clean = Str::of($raisonSociale)
            ->upper()
            ->replaceMatches('/[^A-Z0-9 ]/u', ' ')
            ->squish();

        $words = explode(' ', $clean);
        $acronym = collect($words)
            ->filter(fn($w) => strlen($w) > 0)
            ->map(fn($w) => Str::substr($w, 0, 1))
            ->implode('');


        $acronym = Str::upper(Str::substr($acronym, 0, 3));


        if (strlen($acronym) < 3) {
            $acronym = str_pad($acronym, 3, 'X');
        }

        // =========================
        // 2. Pays ISO (CI, SN, FR…)
        // =========================
//        dd($paysIso);
        $paysIso = Str::upper(Str::substr($paysIso, 0, 2)) ?: 'XX';


        // =========================
        // 3. Base code
        // =========================
        $base = "EMP-{$paysIso}-{$acronym}";


        if ($marchandId) {
            $base .= "-M{$marchandId}";
        }

        // =========================
        // 4. Séquence incrémentale
        // =========================
        $last = Employe::where('code_employe', 'like', $base . '%')
            ->orderBy('code_employe', 'desc')
            ->first();

        $nextNumber = 1;

        if ($last) {
            preg_match('/(\d+)$/', $last->code_employe, $m);
            $nextNumber = isset($m[1]) ? ((int)$m[1] + 1) : 1;
        }

        return sprintf('%s-%05d', $base, $nextNumber);
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
    public static function generateVenteReference(
        string $paysIso,
        int $pointVenteId,
        int $caisseId,
        \DateTimeInterface $date = null
    ): string {

        $date = $date ?? now();

        // =========================
        // Segments fixes
        // =========================
        $prefix = 'VTE';
        $pays   = Str::upper(substr($paysIso, 0, 2));
        $pv     = sprintf('PV%02d', $pointVenteId);
        $caisse = sprintf('CA%02d', $caisseId);
        $datePart = $date->format('Ymd');

        // =========================
        // Séquence journalière
        // =========================
        $base = "{$prefix}-{$pays}-{$pv}-{$caisse}-{$datePart}";

        $last = VentePos::where('reference', 'like', $base . '%')
            ->orderBy('reference', 'desc')
            ->first();

        $next = 1;

        if ($last) {
            preg_match('/(\d+)$/', $last->reference, $m);
            $next = isset($m[1]) ? ((int)$m[1] + 1) : 1;
        }

        return sprintf('%s-%06d', $base, $next);
    }
    public static function generateSessionCaisseReference(
        string $paysIso,
        int $pointVenteId,
        int $caisseId,
        \DateTimeInterface $date = null
    ): string {

        $date = $date ?? now();

        // =========================
        // Segments fixes
        // =========================
        $prefix = 'SC';
        $pays   = Str::upper(substr($paysIso, 0, 2));
        $pv     = sprintf('PV%02d', $pointVenteId);
        $caisse = sprintf('CA%02d', $caisseId);
        $datePart = $date->format('Ymd');

        $base = "{$prefix}-{$pays}-{$pv}-{$caisse}-{$datePart}";

        // =========================
        // Séquence journalière
        // =========================
        $last = SessionCaisse::where('reference', 'like', $base . '%')
            ->orderBy('reference', 'desc')
            ->first();

        $next = 1;

        if ($last) {
            preg_match('/(\d+)$/', $last->reference, $m);
            $next = isset($m[1]) ? ((int)$m[1] + 1) : 1;
        }

        return sprintf('%s-%03d', $base, $next);
    }

    public static function generateTransfertStockReference(
        int $marchandId,
        int $destinationPointVenteId
    ): string {
        // Récupérer le point de vente destination
        $pointVente = PointVente::findOrFail($destinationPointVenteId);

        // Code pays (fallback XX)
        $paysCode = Str::upper(
            $pointVente->marchand?->proprietaire?->pays?->iso ?? 'XX'
        );

        // Code point de vente (ex: PV03)
        $pvCode = 'PV' . str_pad($destinationPointVenteId, 2, '0', STR_PAD_LEFT);

        // Date YYMM
        $datePart = now()->format('ym');

        // Base de la référence
        $base = "TRF-{$paysCode}-{$pvCode}-{$datePart}";

        // Compteur mensuel par marchand + destination
        $count = TransfertStock::where('reference', 'LIKE', "{$base}%")
            ->whereHas('emplacementDestination', function ($q) use ($marchandId) {
                $q->where('marchand_id', $marchandId);
            })
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return "{$base}-{$sequence}";
    }
}
