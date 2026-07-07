<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Auto-création d'un compte User pour les profils humains
 * (Parent, Tuteur, Accompagnateur, Enseignant).
 *
 * Convention métier (décision produit 2026-07-07) :
 *  • Le TÉLÉPHONE est la clé d'identification — c'est avec ce numéro que
 *    l'utilisateur se connecte. Colonnes `login` / `full_login` (uniques).
 *  • L'EMAIL n'est PLUS unique (voir migration
 *    `2026_07_07_170000_drop_users_email_unique_keep_login_unique`).
 *    Cas légitime : un père peut être aussi enseignant, tuteur d'un cousin
 *    et donner son email à plusieurs profils sans conflit.
 *  • password par défaut = "password123" (à changer au 1er login)
 *  • role = clé métier ('parent', 'tuteur', 'accompagnateur', 'enseignant')
 *  • statut = 'actif'
 *
 * Idempotent : si un user existe déjà avec le MÊME login (téléphone), on
 * réutilise son id. L'email n'est plus un critère de dédoublonnage.
 */
class AutoUserCreator
{
    /**
     * @param array $data { nom, prenoms, email?, telephone?, matricule?, role, ... }
     * @return int L'ID du User (existant ou nouvellement créé)
     */
    public static function forProfile(array $data): int
    {
        $email = $data['email'] ?? null;

        // Déterminer le login canonique — téléphone en priorité.
        $login = $data['telephone']
            ?? $email
            ?? $data['matricule']
            ?? ($data['role'] ?? 'user') . '-' . time();

        // Réutiliser un user existant sur le même login (téléphone).
        // L'email n'est plus contrainte unique → pas de lookup nécessaire.
        $existing = User::where('login', $login)->first();
        if ($existing) {
            return $existing->id;
        }

        $uuid = (string) Str::uuid();

        return User::create([
            'uuid'       => $uuid,
            'nom'        => $data['nom'] ?? 'Sans nom',
            'prenoms'    => $data['prenoms'] ?? null,
            'email'      => $email,
            'login'      => $login,
            'full_login' => $login,
            'password'   => bcrypt($data['password'] ?? 'password123'),
            'role'       => $data['role'] ?? 'user',
            'statut'     => $data['statut'] ?? 'actif',
            // Colonnes legacy SmilPay (voir migration 2026_07_04_160000_make_users_legacy_columns_nullable).
            // On les initialise à partir de l'uuid pour éviter tout NOT NULL restant.
            'qr_data'    => $uuid,
            'code_owner' => 'AGREE-' . substr($uuid, 0, 8),
        ])->id;
    }
}
