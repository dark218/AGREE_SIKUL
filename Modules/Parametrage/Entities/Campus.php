<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;

class Campus extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'campuses';

    protected $fillable = [
        'institution_id',
        'code',
        'nom',
        'adresse',
        'ville',
        'code_postal',
        'boite_postale',
        'quartier',
        'commune',
        'departement',
        'region',
        'pays_id',
        'longitude',
        'latitude',
        'telephone',
        'email',
        'responsable_id',
        'statut',
    ];

    protected $casts = [
        'longitude' => 'float',
        'latitude' => 'float',
    ];

    // Relations
    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function ecoles(): HasMany
    {
        return $this->hasMany(Ecole::class, 'campus_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function getNombreEcoles(): int
    {
        return $this->ecoles()->count();
    }
}
