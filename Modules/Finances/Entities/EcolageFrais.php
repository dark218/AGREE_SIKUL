<?php

namespace Modules\Finances\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EcolageFrais extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'ecolage_frais';

    protected $fillable = [
        'ecolage_id',
        'poste_recette_id',
        'plan_compte_id',
        'libelle',
        'montant',
        'date_limite',
        'ordre',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_limite' => 'date',
    ];

    public function ecolage(): BelongsTo
    {
        return $this->belongsTo(Ecolage::class, 'ecolage_id');
    }

    public function posteRecette(): BelongsTo
    {
        return $this->belongsTo(PosteRecette::class, 'poste_recette_id');
    }

    public function planCompte(): BelongsTo
    {
        return $this->belongsTo(PlanCompte::class, 'plan_compte_id');
    }
}
