<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bulletin extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bulletins';

    protected $fillable = [
        'apprenant_id',
        'classe_id',
        'annee_scolaire_id',
        'periode',
        'moyenne_generale',
        'rang',
        'statut',
        'decision_conseil',
    ];

    protected $casts = [
        'moyenne_generale' => 'decimal:2',
    ];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function moyenneMatieres(): HasMany
    {
        return $this->hasMany(MoyenneMatiere::class, 'bulletin_id');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
