<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeManuelsCahier extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'listes_manuels_cahiers';

    protected $fillable = [
        'liste_manuels_id',
        'utilite',
        'type_cahier',
        'nombre_pages',
        'quantite',
        'ordre',
    ];

    protected $casts = [
        'nombre_pages' => 'integer',
        'quantite' => 'integer',
        'ordre' => 'integer',
    ];

    public function listeManuels(): BelongsTo
    {
        return $this->belongsTo(ListeManuels::class, 'liste_manuels_id');
    }
}
