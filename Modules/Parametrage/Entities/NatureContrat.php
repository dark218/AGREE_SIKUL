<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class NatureContrat extends BaseModel
{
    use HasFactory;

    protected $table = 'natures_contrats';

    protected $fillable = [
        'code',
        'libelle',
        'duree_mois',
        'est_renouvelable',
        'type_personnel',
        'regime_travail',
        'periodicite_paiement',
        'ecole_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'duree_mois' => 'integer',
        'est_renouvelable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole()
    {
        return $this->belongsTo(\Modules\Ecole\Entities\Ecole::class, 'ecole_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    // Méthodes utiles
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function activate(): self
    {
        $this->update(['etat' => 'actif']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['etat' => 'inactif']);
        return $this;
    }
}
