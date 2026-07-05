<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeManuelsLivre extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'listes_manuels_livres';

    protected $fillable = [
        'liste_manuels_id',
        'titre',
        'sujet',
        'langue',
        'auteurs',
        'editeurs',
        'annee_edition',
        'ordre',
    ];

    protected $casts = [
        'annee_edition' => 'integer',
        'ordre' => 'integer',
    ];

    public function listeManuels(): BelongsTo
    {
        return $this->belongsTo(ListeManuels::class, 'liste_manuels_id');
    }
}
