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
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';

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
