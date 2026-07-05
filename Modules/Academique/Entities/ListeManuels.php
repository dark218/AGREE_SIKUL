<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeManuels extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'listes_manuels';

    protected $fillable = [
        'annee_scolaire_id',
        'ecole_id',
        'section_id',
        'niveau_id',
        'cycle_id',
        'type_manuel',
        'titre_manuel',
        'auteurs',
        'editeur',
        'annee_edition',
        'pays_id',
        'etat',
    ];

    protected $casts = [
        'annee_edition' => 'integer',
    ];

    /**
     * Relationship: Année Scolaire
     */
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\AnneeScolaire::class, 'annee_scolaire_id');
    }

    /**
     * Relationship: Ecole
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    /**
     * Relationship: Section
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Section::class, 'section_id');
    }

    /**
     * Relationship: Pays
     */
    public function pays(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Pays::class, 'pays_id');
    }

    /**
     * Relationship: Niveau
     */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Niveau::class, 'niveau_id');
    }

    /**
     * Relationship: Cycle d'enseignement
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\CycleEnseignement::class, 'cycle_id');
    }

    /**
     * Bloc livres
     */
    public function livres(): HasMany
    {
        return $this->hasMany(ListeManuelsLivre::class, 'liste_manuels_id')->orderBy('ordre');
    }

    /**
     * Bloc cahiers
     */
    public function cahiers(): HasMany
    {
        return $this->hasMany(ListeManuelsCahier::class, 'liste_manuels_id')->orderBy('ordre');
    }

    /**
     * Bloc autres fournitures
     */
    public function fournitures(): HasMany
    {
        return $this->hasMany(ListeManuelsFourniture::class, 'liste_manuels_id')->orderBy('ordre');
    }

    /**
     * Scope: Actif
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    /**
     * Scope: By Année Scolaire
     */
    public function scopeByAnneeScolaire($query, $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Scope: By Section
     */
    public function scopeBySection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }
}
