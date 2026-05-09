<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Classe;

class Absence extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'absences';

    protected $fillable = [
        'apprenant_id',
        'classe_id',
        'week_number',
        'date_absence',
        'day_of_week',
        'time_from',
        'time_to',
        'nombre_heures',
        'motif',
        'statut',
        'etat',
    ];

    protected $casts = [
        'date_absence' => 'date',
        'time_from' => 'datetime:H:i',
        'time_to' => 'datetime:H:i',
        'nombre_heures' => 'decimal:2',
    ];

    /**
     * Relationship: Apprenant avec toutes ses données
     */
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    /**
     * Relationship: Classe
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    /**
     * Scope: Actif
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    /**
     * Scope: By Week
     */
    public function scopeByWeek($query, $week)
    {
        return $query->where('week_number', $week);
    }

    /**
     * Scope: By Apprenant
     */
    public function scopeByApprenant($query, $apprenantId)
    {
        return $query->where('apprenant_id', $apprenantId);
    }
}
