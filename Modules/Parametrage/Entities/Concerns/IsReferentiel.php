<?php

namespace Modules\Parametrage\Entities\Concerns;

/**
 * Trait mutualisé pour tous les référentiels Paramétrage simples
 * (code, libelle, ordre, etat). Évite de dupliquer 8 fois les
 * mêmes propriétés/scopes.
 *
 * Chaque entity qui l'utilise doit juste définir `$table`.
 */
trait IsReferentiel
{
    // ⚠️ PHP interdit qu'un trait redéfinisse une propriété héritée
    // avec une valeur par défaut différente. Toutes les propriétés
    // ($defaultOrderBy, $defaultOrderDir, $fillable) sont donc
    // définies DIRECTEMENT sur chaque entity concrète pour ne pas
    // entrer en collision avec BaseModel.
    //
    // Ce trait n'expose plus qu'un comportement partagé (scope actif).

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
