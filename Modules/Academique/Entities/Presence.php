<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presence extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'presences';

    protected $fillable = [
        'apprenant_id',
        'seance_id',
        'statut',
        'heure_arrivee',
        'remarques',
        'saisi_par',
    ];

    protected $casts = [
        'heure_arrivee' => 'datetime:H:i',
    ];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function seance(): BelongsTo
    {
        return $this->belongsTo(Seance::class, 'seance_id');
    }

    public function saisitPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisi_par');
    }

    public function scopePresent($query)
    {
        return $query->where('statut', 'present');
    }

    public function isPresent(): bool
    {
        return $this->statut === 'present';
    }
}
