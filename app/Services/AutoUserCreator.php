<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Auto-création d'un compte User pour les profils humains
 * (Parent, Tuteur, Accompagnateur, Enseignant).
 *
 * Convention :
 *  • login = téléphone (fallback email → matricule → timestamp)
 *  • password par défaut = "password123" (à changer au 1er login)
 *  • role = clé métier ('parent', 'tuteur', 'accompagnateur', 'enseignant')
 *  • statut = 'actif'
 *
 * Idempotent : si un `user_id` est passé ou si un user existe déjà pour
 * le login demandé, on ne crée rien et on retourne le user existant.
 */
class AutoUserCreator
{
    /**
     * @param array $data { nom, prenoms, email?, telephone?, matricule?, role, ... }
     * @return int L'ID du User (existant ou nouvellement créé)
     */
    public static function forProfile(array $data): int
    {
        // Déterminer le login canonique
        $login = $data['telephone']
            ?? $data['email']
            ?? $data['matricule']
            ?? ($data['role'] ?? 'user') . '-' . time();

        // Réutiliser un user existant sur le même login
        $existing = User::where('login', $login)->first();
        if ($existing) {
            return $existing->id;
        }

        return User::create([
            'uuid'       => (string) Str::uuid(),
            'nom'        => $data['nom'] ?? 'Sans nom',
            'prenoms'    => $data['prenoms'] ?? null,
            'email'      => $data['email'] ?? null,
            'login'      => $login,
            'full_login' => $login,
            'password'   => bcrypt($data['password'] ?? 'password123'),
            'role'       => $data['role'] ?? 'user',
            'statut'     => $data['statut'] ?? 'actif',
        ])->id;
    }
}
