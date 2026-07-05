<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeManuelsFourniture extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'listes_manuels_fournitures';

    protected $fillable = [
        'liste_manuels_id',
        'utilite',
        'designation',
        'quantite',
        'fournisseur',
        'ordre',
    ];

    protected $casts = [
        'quantite' => 'integer',
        'ordre' => 'integer',
    ];

    public function listeManuels(): BelongsTo
    {
        return $this->belongsTo(ListeManuels::class, 'liste_manuels_id');
    }
}
