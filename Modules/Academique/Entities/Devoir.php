<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devoir extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'devoirs';

    protected $fillable = [
        'titre',
        'description',
        'matiere_id',
        'classe_id',
        'date_debut',
        'date_fin',
        'date_remise',
        'nombre_heures',
        'coefficient',
        'statut',
        'cours_id',
        'fichier_enonce_id',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'date_remise' => 'datetime',
        'coefficient' => 'decimal:2',
        'nombre_heures' => 'decimal:2',
    ];

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class, 'classe_id');
    }

    public function rendus(): HasMany
    {
        return $this->hasMany(RenduDevoir::class, 'devoir_id');
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
