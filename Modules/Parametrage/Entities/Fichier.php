<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Fichier extends BaseModel
{
    use HasFactory;

    protected $table = 'fichiers';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'chemin_fichier',
        'type_fichier',
        'taille_fichier',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'taille_fichier' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function activate(): self
    {
        $this->update(['etat' => 'actif']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['etat' => 'inactif']);
        return $this;
    }
}
