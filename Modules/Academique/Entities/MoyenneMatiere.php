<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academique\Entities\Matiere;

class MoyenneMatiere extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'moyennes_matieres';

    protected $fillable = [
        'apprenant_id',  // Phase 1: créer les moyennes par apprenant
        'bulletin_id',   // Phase 2: associer au bulletin
        'matiere_id',
        'moyenne',
        'coefficient',
        'rang',
        'appreciation',
        'statut',
    ];

    protected $casts = [
        'moyenne' => 'float',
        'coefficient' => 'float',
        'rang' => 'integer',
    ];

    // Relations
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function bulletin(): BelongsTo
    {
        return $this->belongsTo(Bulletin::class, 'bulletin_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    // Scopes
    public function scopeExcellent($query)
    {
        return $query->where('appreciation', 'excellent');
    }

    public function scopeBien($query)
    {
        return $query->where('appreciation', 'bien');
    }

    public function scopeAssez($query)
    {
        return $query->where('appreciation', 'assez');
    }

    // Méthodes métier
    public function isExcellent(): bool
    {
        return $this->appreciation === 'excellent';
    }

    public function getMoyenne(): float
    {
        return $this->moyenne ?? 0;
    }
}
