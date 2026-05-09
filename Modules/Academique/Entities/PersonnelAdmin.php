<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class PersonnelAdmin extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'personnel_admin';

    protected $fillable = [
        'nom',
        'prenoms',
        'email',
        'telephone',
        'poste',
        'date_embauche',
        'statut',
    ];

    protected $casts = [
        'date_embauche' => 'date',
    ];

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
