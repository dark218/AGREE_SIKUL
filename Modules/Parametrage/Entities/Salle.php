<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salle extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'salles';

    protected $fillable = [
        'code',
        'libelle',
        'capacite',
        'statut',
        'description',
    ];
}
