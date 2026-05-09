<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TypeEvenementAgenda extends BaseModel
{
    use HasFactory;

    protected $table = 'type_evenements_agenda';

    protected $fillable = [
        'code',
        'libelle',
        'categorie_evenement',
        'couleur_affichage',
        'icone',
        'necessite_inscription',
        'capacite_max_participants',
        'duree_standard_minutes',
        'est_public',
        'ecole_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'necessite_inscription' => 'boolean',
        'est_public' => 'boolean',
        'capacite_max_participants' => 'integer',
        'duree_standard_minutes' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
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
