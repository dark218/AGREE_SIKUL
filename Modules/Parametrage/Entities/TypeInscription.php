<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Concerns\IsReferentiel;

class TypeInscription extends BaseModel
{
    use HasFactory, SoftDeletes, IsReferentiel;
    protected $table = 'types_inscriptions';
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';
}
