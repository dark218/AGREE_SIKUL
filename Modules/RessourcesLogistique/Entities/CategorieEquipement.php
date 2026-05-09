<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorieEquipement extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories_equipements';

    protected $fillable = [
        'libelle',
        'description',
    ];

    // Relations
    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class, 'categorie_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreEquipements(): int
    {
        return $this->equipements()->count();
    }

    public function getValeurTotalEquipements(): int
    {
        return $this->equipements()
            ->sum('prix_cents') ?? 0;
    }

    public function getValeurTotalEnEuros(): float
    {
        return $this->getValeurTotalEquipements() / 100;
    }
}
