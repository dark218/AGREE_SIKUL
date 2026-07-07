<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultationInfirmerie extends BaseModel
{
    use HasFactory, SoftDeletes;

    // Table PLURIEL (schéma canonique avec infirmier_id/diagnostic/traitement).
    // La table `consultations_infirmerie` (singulier) est un doublon obsolète
    // créé par une migration Modules/Services parallèle — schéma différent.
    protected $table = 'consultations_infirmeries';

    protected $fillable = [
        'apprenant_id',
        'date_consultation',
        'motif',
        'diagnostic',
        'traitement',
        'infirmier_id',
    ];

    protected $casts = [
        'date_consultation' => 'datetime',
    ];

    // Relations
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function infirmier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'infirmier_id');
    }

    // Scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('date_consultation', 'desc');
    }

    public function scopeByApprenant($query, int $apprenantId)
    {
        return $query->where('apprenant_id', $apprenantId);
    }

    // Méthodes métier
    public function hasTraitement(): bool
    {
        return $this->traitement !== null && $this->traitement !== '';
    }

    public function hasDiagnostic(): bool
    {
        return $this->diagnostic !== null && $this->diagnostic !== '';
    }

    public function getResumeConsultation(): string
    {
        $resume = 'Motif: ' . ($this->motif ?? 'Non spécifié');
        if ($this->hasDiagnostic()) {
            $resume .= ' | Diagnostic: ' . $this->diagnostic;
        }
        if ($this->hasTraitement()) {
            $resume .= ' | Traitement: ' . $this->traitement;
        }
        return $resume;
    }
}
