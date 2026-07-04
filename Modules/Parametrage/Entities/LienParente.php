<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Concerns\IsReferentiel;

class LienParente extends BaseModel
{
    use HasFactory, SoftDeletes, IsReferentiel;
    protected $table = 'liens_parente';
    protected $defaultOrderBy = 'ordre';
    protected $defaultOrderDir = 'asc';
}
