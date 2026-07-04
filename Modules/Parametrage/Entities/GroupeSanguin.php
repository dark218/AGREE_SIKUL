<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Concerns\IsReferentiel;

class GroupeSanguin extends BaseModel
{
    use HasFactory, SoftDeletes, IsReferentiel;
    protected $table = 'groupes_sanguins';
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';
    protected $fillable = ['code', 'libelle', 'ordre', 'etat'];
}
