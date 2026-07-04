<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Concerns\IsReferentiel;

class StatutApprenant extends BaseModel
{
    use HasFactory, SoftDeletes, IsReferentiel;
    protected $table = 'statuts_apprenants';
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';
    protected $fillable = ['code', 'libelle', 'ordre', 'etat'];
}
