<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rendu extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'rendus';

    protected $fillable = [
        'apprenant_id',
        'devoir_id',
        'date_remise',
        'fichier',
        'statut',
    ];

    protected $casts = [
        'date_remise' => 'date',
    ];

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function devoir(): BelongsTo
    {
        return $this->belongsTo(Devoir::class, 'devoir_id');
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
