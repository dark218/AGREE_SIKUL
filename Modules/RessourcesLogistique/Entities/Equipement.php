<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Ecole;

class Equipement extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipements';

    protected $fillable = [
        'categorie_id',
        'ecole_id',
        'nom',
        'reference',
        'date_acquisition',
        'prix_cents',
        'etat',
        'localisation',
    ];

    protected $casts = [
        'date_acquisition' => 'date',
        'prix_cents' => 'integer',
    ];

    // Relations
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieEquipement::class, 'categorie_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(MaintenanceEquipement::class, 'equipement_id');
    }

    // Scopes
    public function scopeByCategorie($query, int $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }

    public function scopeByEtat($query, string $etat)
    {
        return $query->where('etat', $etat);
    }

    public function scopeBonEtat($query)
    {
        return $query->where('etat', 'bon');
    }

    public function scopeMoyenEtat($query)
    {
        return $query->where('etat', 'moyen');
    }

    public function scopeMauvaisEtat($query)
    {
        return $query->where('etat', 'mauvais');
    }

    // Méthodes métier
    public function getPrixEnEuros(): float
    {
        return $this->prix_cents / 100;
    }

    public function isEnBonEtat(): bool
    {
        return $this->etat === 'bon';
    }

    public function getNombreMaintenances(): int
    {
        return $this->maintenances()->count();
    }

    public function getLastMaintenance(): ?MaintenanceEquipement
    {
        return $this->maintenances()
            ->orderBy('date_maintenance', 'desc')
            ->first();
    }
}
