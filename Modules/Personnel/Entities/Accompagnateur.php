<?php

namespace Modules\Personnel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Accompagnateur extends BaseModel
{
    use HasFactory;

    protected $table = 'accompagnateurs';

    protected $fillable = [
        // School Information
        'ecole_id',
        'institution_id',
        'campus_id',

        // Accompagnant 1
        'accompagnant1_civilite',
        'accompagnant1_nom',
        'accompagnant1_prenoms',
        'accompagnant1_nom_complet',
        'accompagnant1_lien',
        'accompagnant1_photo_id',

        // Accompagnant 2
        'accompagnant2_civilite',
        'accompagnant2_nom',
        'accompagnant2_prenoms',
        'accompagnant2_nom_complet',
        'accompagnant2_lien',
        'accompagnant2_photo_id',

        // Accompagnant 3
        'accompagnant3_civilite',
        'accompagnant3_nom',
        'accompagnant3_prenoms',
        'accompagnant3_nom_complet',
        'accompagnant3_lien',
        'accompagnant3_photo_id',

        // Audit
        'etat',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function ecole()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Ecole', 'ecole_id');
    }

    public function institution()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Institution', 'institution_id');
    }

    public function campus()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Campus', 'campus_id');
    }

    public function accompagnant1Photo()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Fichier', 'accompagnant1_photo_id');
    }

    public function accompagnant2Photo()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Fichier', 'accompagnant2_photo_id');
    }

    public function accompagnant3Photo()
    {
        return $this->belongsTo('Modules\Parametrage\Entities\Fichier', 'accompagnant3_photo_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    // Helper methods
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
