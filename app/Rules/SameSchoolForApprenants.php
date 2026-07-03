<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Academique\Entities\Apprenant;

/**
 * Vérifie qu'une liste d'IDs d'apprenants appartient tous à la même école.
 *
 * Usage :
 *   'apprenant_ids' => ['required', 'array', 'min:1', new SameSchoolForApprenants],
 *
 * Utile pour Parent/Tuteur/Accompagnateur : un même contact ne peut être
 * rattaché qu'à des enfants d'un seul établissement (règle métier
 * AGREE SIKUL).
 */
class SameSchoolForApprenants implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value) || count($value) < 2) {
            // 0 ou 1 apprenant → aucune incohérence possible
            return;
        }

        $ecoleIds = Apprenant::whereIn('id', $value)
            ->whereNotNull('ecole_id')
            ->pluck('ecole_id')
            ->unique();

        if ($ecoleIds->count() > 1) {
            $fail('Tous les apprenants sélectionnés doivent appartenir à la même école.');
        }
    }
}
