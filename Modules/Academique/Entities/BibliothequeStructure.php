<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BibliothequeStructure extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bibliotheque_structures';

    protected $fillable = [
        'code',
        'libelle',
        'localisation',
        'campus_id',
        'responsable',
        'statut_disponibilite',
        'etat',
        'creation_username',
        'modification_username',
    ];

    public function campus(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Campus::class, 'campus_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
