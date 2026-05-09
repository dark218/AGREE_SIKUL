<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;

class Traduction extends BaseModel
{

    protected $table = 'traductions';

    protected $fillable = [
        'code_fr',
        'intitule_fr',
        'code_en',
        'intitule_en',
        'groupe',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
