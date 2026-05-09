<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dossier extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dossiers';

    protected $fillable = [
        'apprenant_id',
        'numero_dossier',
        'date_creation',
        'lieu_creation',
        'statut',
    ];

    protected $casts = [
        'date_creation' => 'date',
    ];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
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
