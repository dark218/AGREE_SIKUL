<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassageCantine extends BaseModel
{
    use HasFactory, SoftDeletes;

    // Table PLURIEL (schéma canonique avec heure_passage).
    // La table `passages_cantine` (singulier) est un doublon obsolète créé
    // par une migration Modules/Services parallèle — schéma différent
    // (menu_id/statut au lieu de heure_passage).
    protected $table = 'passages_cantines';

    protected $fillable = [
        'inscription_cantine_id',
        'date_passage',
        'heure_passage',
    ];

    protected $casts = [
        'date_passage' => 'date',
        // Note : 'time' n'est pas un cast Laravel natif (Laravel <11).
        // On garde `heure_passage` en string (format HH:MM:SS) — MySQL le
        // convertit correctement au INSERT.
    ];

    // Relations
    public function inscriptionCantine(): BelongsTo
    {
        return $this->belongsTo(InscriptionCantine::class, 'inscription_cantine_id');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('date_passage', $date);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_passage', 'desc')
            ->orderBy('heure_passage', 'desc');
    }

    // Méthodes métier
    public function getApprenant()
    {
        return $this->inscriptionCantine?->apprenant;
    }

    public function getServiceCantine()
    {
        return $this->inscriptionCantine?->serviceCantine;
    }
}
