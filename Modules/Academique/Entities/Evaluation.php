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

    // ==================================================================
    // §10.1 — Consolidation Evaluation/Devoir/Examen via discriminant `type`.
    //
    // Après migration des données Devoir/ExamenEnLigne/PlanificationExamen
    // vers `evaluations`, chaque enregistrement porte l'un des types ci-dessous.
    // Les scopes scopeDevoirs(), scopeExamens(), etc. permettent aux
    // controllers de continuer à afficher leurs listes spécifiques sans
    // multiplier les tables.
    // ==================================================================

    public const TYPE_INTERRO       = 'interro';
    public const TYPE_DEVOIR        = 'devoir';
    public const TYPE_EXAMEN        = 'examen';
    public const TYPE_EXAMEN_LIGNE  = 'examen_en_ligne';
    public const TYPE_PLANIFICATION = 'planification';

    public const TYPES = [
        self::TYPE_INTERRO,
        self::TYPE_DEVOIR,
        self::TYPE_EXAMEN,
        self::TYPE_EXAMEN_LIGNE,
        self::TYPE_PLANIFICATION,
    ];

    public function scopeDevoirs($query)         { return $query->where('type', self::TYPE_DEVOIR); }
    public function scopeExamens($query)         { return $query->where('type', self::TYPE_EXAMEN); }
    public function scopeExamensEnLigne($query)  { return $query->where('type', self::TYPE_EXAMEN_LIGNE); }
    public function scopePlanifications($query)  { return $query->where('type', self::TYPE_PLANIFICATION); }
    public function scopeInterros($query)        { return $query->where('type', self::TYPE_INTERRO); }
    public function scopeType($query, string $t) { return $query->where('type', $t); }

    public function isDevoir(): bool        { return $this->type === self::TYPE_DEVOIR; }
    public function isExamen(): bool        { return $this->type === self::TYPE_EXAMEN; }
    public function isExamenEnLigne(): bool { return $this->type === self::TYPE_EXAMEN_LIGNE; }
    public function isPlanification(): bool { return $this->type === self::TYPE_PLANIFICATION; }
    public function isInterro(): bool       { return $this->type === self::TYPE_INTERRO; }
}
