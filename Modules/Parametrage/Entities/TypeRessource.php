<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TypeRessource extends BaseModel
{
    use HasFactory;

    protected $table = 'type_ressources';

    protected $fillable = [
        'code',
        'libelle',
        'categorie_ressource',
        'est_partageable',
        'necessite_reservation',
        'capacite_standard',
        'unite_mesure',
        'ecole_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'est_partageable' => 'boolean',
        'necessite_reservation' => 'boolean',
        'capacite_standard' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

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

    // Relations
    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }
}
