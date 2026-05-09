<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends BaseModel
{
    use HasFactory;

    protected $table = 'sections';

    protected $fillable = [
        'code',
        'libelle',
        'annee_scolaire_id',
        'ecole_id',
        'niveau_etude_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function niveauEtude(): BelongsTo
    {
        return $this->belongsTo(NiveauEtude::class, 'niveau_etude_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function natureExamens(): HasMany
    {
        return $this->hasMany(NatureExamen::class, 'section_id');
    }

    public function matieresUnites(): HasMany
    {
        return $this->hasMany(MatiereUnite::class, 'section_id');
    }

    public function groupesMatieres(): HasMany
    {
        return $this->hasMany(GroupeMatiere::class, 'section_id');
    }

    public function typeApprenants(): HasMany
    {
        return $this->hasMany(TypeApprenant::class, 'section_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    // Méthodes utiles
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
