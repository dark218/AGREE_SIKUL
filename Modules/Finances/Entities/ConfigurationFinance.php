<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConfigurationFinance extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'configurations_finances';

    protected $fillable = [
        'code',
        'libelle',
        'valeur',
        'type',
        'description',
    ];

    protected $casts = [
        'valeur' => 'string',
    ];

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeInactifs($query)
    {
        return $query->where('statut', 'inactif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function getValeurFormatee(): string
    {
        return match($this->type) {
            'monetaire' => number_format((float)$this->valeur, 2, ',', ' ') . ' XOF',
            'pourcentage' => $this->valeur . '%',
            'booleen' => $this->valeur === '1' ? 'Oui' : 'Non',
            default => $this->valeur,
        };
    }
}
