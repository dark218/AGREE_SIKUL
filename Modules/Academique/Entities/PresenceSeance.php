<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academique\Entities\Seance;
use Modules\Academique\Entities\Apprenant;

class PresenceSeance extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'presence_seances';

    protected $fillable = [
        'seance_id',
        'apprenant_id',
        'date_presence',
        'heure_arrivee',
        'heure_depart',
        'statut',
        'remarques',
        'saisi_par',
    ];

    protected $casts = [
        'date_presence' => 'date',
    ];

    // Relations
    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class, 'seance_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function saisitPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }

    // Scopes
    public function scopePresent($query)
    {
        return $query->where('statut', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('statut', 'absent');
    }

    public function scopeRetard($query)
    {
        return $query->where('statut', 'retard');
    }

    // Méthodes métier
    public function isPresent(): bool
    {
        return $this->statut === 'present';
    }

    public function isAbsent(): bool
    {
        return $this->statut === 'absent';
    }
}
