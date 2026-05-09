<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Justification extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'justifications';

    protected $fillable = [
        'absence_id',
        'document',
        'motif',
        'date_justification',
        'statut',
    ];

    protected $casts = [
        'date_justification' => 'date',
    ];

    public function absence(): BelongsTo
    {
        return $this->belongsTo(AbsenceApprenant::class, 'absence_id');
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
