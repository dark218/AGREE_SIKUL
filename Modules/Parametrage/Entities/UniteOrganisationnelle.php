<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniteOrganisationnelle extends BaseModel
{
    use HasFactory;

    protected $table = 'unites_organisationnelles';

    protected $fillable = [
        'code',
        'libelle',
        'type_unite',
        'responsable_id',
        'budget_annuel',
        'effectif_max',
        'niveau_hierarchique',
        'ecole_id',
        'unite_mere_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'budget_annuel' => 'decimal:2',
        'effectif_max' => 'integer',
        'niveau_hierarchique' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Ecole\Entities\Ecole::class, 'ecole_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'responsable_id');
    }

    // Relations - Self-referencing
    public function mereUnite(): BelongsTo
    {
        return $this->belongsTo(UniteOrganisationnelle::class, 'unite_mere_id');
    }

    public function sousUnites(): HasMany
    {
        return $this->hasMany(UniteOrganisationnelle::class, 'unite_mere_id');
    }

    public function fonctions(): HasMany
    {
        return $this->hasMany(Fonction::class, 'unite_organisationnelle_id');
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

    public function isRootUnit(): bool
    {
        return $this->unite_mere_id === null;
    }
}
