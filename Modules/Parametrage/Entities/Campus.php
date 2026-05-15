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
        // Legacy strings
        'quartier',
        'commune',
        'departement',
        'region',
        // Nouvelles FK
        'quartier_id',
        'commune_id',
        'departement_id',
        'region_id',
        'pays_id',
        'longitude',
        'latitude',
        'telephone',
        'email',
        'responsable_id',
        'statut',
        'statut_disponibilite',
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

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function quartierRef(): BelongsTo
    {
        return $this->belongsTo(Quartier::class, 'quartier_id');
    }

    public function communeRef(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_id');
    }

    public function departementRef(): BelongsTo
    {
        return $this->belongsTo(Departement::class, 'departement_id');
    }

    public function regionRef(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function getNombreEcoles(): int
    {
        return $this->ecoles()->count();
    }
}
