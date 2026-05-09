<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;

class AutreRevenu extends BaseModel
{
    use HasFactory;

    protected $table = 'autres_revenus';

    protected $fillable = [
        'annee_scolaire_id',
        'niveau_id',
        'ecole_id',
        'campus_id',
        'uniforme',
        'tenue_mercredi',
        'tenue_sport',
        'autre1',
        'autre2',
        'autre3',
        'autre4',
        'autre5',
        'autre6',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'uniforme' => 'decimal:2',
        'tenue_mercredi' => 'decimal:2',
        'tenue_sport' => 'decimal:2',
        'autre1' => 'decimal:2',
        'autre2' => 'decimal:2',
        'autre3' => 'decimal:2',
        'autre4' => 'decimal:2',
        'autre5' => 'decimal:2',
        'autre6' => 'decimal:2',
    ];

    // Relations
    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function niveauEtude()
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
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

    // Helper methods
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
