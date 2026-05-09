<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeFrais extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'types_frais';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'montant_cents',
        'obligatoire',
    ];

    protected $casts = [
        'montant_cents' => 'integer',
        'obligatoire' => 'boolean',
    ];

    // Relations
    public function frais(): HasMany
    {
        return $this->hasMany(Frais::class, 'type_frais_id');
    }

    // Scopes
    public function scopeObligatoire($query)
    {
        return $query->where('obligatoire', true);
    }

    public function scopeOptionnel($query)
    {
        return $query->where('obligatoire', false);
    }

    // Méthodes métier
    public function isObligatoire(): bool
    {
        return $this->obligatoire === true;
    }

    public function getMontantEnEuros(): float
    {
        return $this->montant_cents / 100;
    }
}
