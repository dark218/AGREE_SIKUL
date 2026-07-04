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
    // Note : $defaultOrderBy et $defaultOrderDir sont définis DIRECTEMENT
    // sur chaque entity concrète (surcharge de la valeur de BaseModel).
    // PHP interdit qu'un trait redéfinisse une propriété héritée avec une
    // valeur par défaut différente — donc on force la surcharge à la classe.

    protected $fillable = [
        'code',
        'libelle',
        'ordre',
        'etat',
    ];

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }
}
