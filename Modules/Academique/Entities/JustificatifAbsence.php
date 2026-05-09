<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JustificatifAbsence extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'justificatifs_absences';

    protected $fillable = [
        'absence_id',
        'absence_type',
        'commentaire',
        'valide_par',
        'valide_at',
    ];

    protected $casts = [
        'valide_at' => 'datetime',
    ];

    // Relations
    public function absence(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'absence_type', 'absence_id', null);
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    // Scopes
    public function scopeValide($query)
    {
        return $query->whereNotNull('valide_at');
    }

    public function scopeEnAttente($query)
    {
        return $query->whereNull('valide_at');
    }

    // Méthodes métier
    public function isValide(): bool
    {
        return $this->valide_at !== null;
    }

    public function isEnAttente(): bool
    {
        return $this->valide_at === null;
    }
}
