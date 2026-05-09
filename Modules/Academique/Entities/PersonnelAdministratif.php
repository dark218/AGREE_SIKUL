<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelAdministratif extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'personnels_administratifs';

    protected $fillable = [
        'user_id',
        'matricule',
        'poste',
        'departement_id',
        'date_embauche',
        'type_contrat',
        'statut',
    ];

    protected $casts = [
        'date_embauche' => 'date',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Departement::class, 'departement_id');
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('statut', 'actif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
