<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CategorieEnseignant extends BaseModel
{
    use HasFactory;

    protected $table = 'categorie_enseignants';

    protected $fillable = [
        'code',
        'libelle',
        'niveau_qualification',
        'charge_horaire_min',
        'charge_horaire_max',
        'taux_horaire_base',
        'peut_etre_titulaire',
        'anciennete_requise',
        'ecole_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'taux_horaire_base' => 'decimal:2',
        'peut_etre_titulaire' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
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
