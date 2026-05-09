<?php

namespace Modules\SuiviAnalyse\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModeleRapport extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'modeles_rapports';

    protected $fillable = [
        'code',
        'titre',
        'description',
        'type',
        'parametres',
    ];

    protected $casts = [
        'parametres' => 'array',
    ];

    // Relations
    public function rapports(): HasMany
    {
        return $this->hasMany(Rapport::class, 'modele_rapport_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreRapportsGeneres(): int
    {
        return $this->rapports()->count();
    }

    public function getParametres(): array
    {
        return $this->parametres ?? [];
    }

    public function hasParametres(): bool
    {
        return !empty($this->parametres);
    }
}
