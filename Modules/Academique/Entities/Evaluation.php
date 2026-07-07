<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'evaluations';

    protected $fillable = [
        'code',
        'titre',
        'type',
        'classe_id',
        'matiere_id',
        'date',
        'coefficient',
        'sur',
        'statut',
    ];

    protected $casts = [
        'date' => 'date',
        'coefficient' => 'decimal:2',
        'sur' => 'decimal:2',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(MatiereUnite::class, 'matiere_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class, 'evaluation_id');
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
