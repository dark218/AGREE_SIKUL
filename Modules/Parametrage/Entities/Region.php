<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends BaseModel
{
    use HasFactory;

    protected $table = 'regions';

    protected $fillable = [
        'code',
        'libelle',
        'pays_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function departements(): HasMany
    {
        return $this->hasMany(Departement::class, 'region_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    // Méthodes utiles
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
