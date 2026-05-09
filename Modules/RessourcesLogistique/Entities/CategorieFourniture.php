<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorieFourniture extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories_fournitures';

    protected $fillable = [
        'libelle',
        'code',
        'description',
    ];

    // Relations
    public function fournitures(): HasMany
    {
        return $this->hasMany(Fourniture::class, 'categorie_fourniture_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreFournitures(): int
    {
        return $this->fournitures()->count();
    }

    public function getValeurTotalFournitures(): int
    {
        return $this->fournitures()
            ->sum('prix_unitaire_cents') ?? 0;
    }

    public function getValeurTotalEnEuros(): float
    {
        return $this->getValeurTotalFournitures() / 100;
    }
}
