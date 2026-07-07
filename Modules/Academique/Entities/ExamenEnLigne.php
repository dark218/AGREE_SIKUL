<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Support\Facades\Hash;
use Modules\Parametrage\Entities\MatiereUnite;

class ExamenEnLigne extends BaseModel
{

    protected $table = 'examens_en_ligne';
    protected $fillable = [
        'planification_examen_id',
        'classe_id',
        'matiere_id',
        'enseignant_id',
        'titre',
        'description',
        'instructions',
        'date_debut',
        'date_fin',
        'nombre_heures',
        'nombre_questions',
        'duree_minutes',
        'note_maximum',
        'note_minimum_passage',
        'melange_questions',
        'melange_reponses',
        'nombre_tentatives',
        'afficher_resultat',
        'afficher_correction',
        'retour_arriere',
        'mot_de_passe',
        'etat',
        'creation_username',
        'modification_username'
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'nombre_heures' => 'decimal:2',
        'duree_minutes' => 'decimal:2',
        'note_maximum' => 'decimal:2',
        'note_minimum_passage' => 'decimal:2',
        'melange_questions' => 'boolean',
        'melange_reponses' => 'boolean',
        'afficher_resultat' => 'boolean',
        'afficher_correction' => 'boolean',
        'retour_arriere' => 'boolean',
    ];

    /**
     * Statuts possibles du cycle de vie
     */
    const STATUT_BROUILLON = 'brouillon';
    const STATUT_PUBLIE = 'publie';
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_TERMINE = 'termine';
    const STATUT_CORRIGE = 'corrige';

    const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_PUBLIE,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_CORRIGE,
    ];

    // Mutator sécurité — hash automatique du mot de passe examen.
    // Fix §10.1 : mot_de_passe stocké en clair est une faille sécurité.
    // Idempotent : si déjà hashé ($2y$… bcrypt), pas de re-hash.
    public function setMotDePasseAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['mot_de_passe'] = null;
            return;
        }
        // Détecte un hash bcrypt existant pour éviter le double-hash sur update.
        if (is_string($value) && preg_match('/^\$2[aby]\$/', $value)) {
            $this->attributes['mot_de_passe'] = $value;
            return;
        }
        $this->attributes['mot_de_passe'] = Hash::make($value);
    }

    /**
     * Vérifie qu'un mot de passe fourni matche le hash stocké.
     * À utiliser au démarrage de l'examen côté apprenant.
     */
    public function checkMotDePasse(string $plain): bool
    {
        if (!$this->mot_de_passe) return true; // pas de mot de passe → accès libre
        return Hash::check($plain, $this->mot_de_passe);
    }

    // Relations

    public function planificationExamen()
    {
        return $this->belongsTo(PlanificationExamen::class);
    }

    public function classe()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Classe::class);
    }

    public function matiere()
    {
        return $this->belongsTo(MatiereUnite::class);
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function questions()
    {
        return $this->hasMany(QuestionExamen::class, 'examen_en_ligne_id')->orderBy('ordre');
    }

    public function tentatives()
    {
        return $this->hasMany(TentativeExamen::class, 'examen_en_ligne_id');
    }

    // Helpers statut

    public function isBrouillon(): bool
    {
        return $this->etat === self::STATUT_BROUILLON;
    }

    public function isPublie(): bool
    {
        return $this->etat === self::STATUT_PUBLIE;
    }

    public function isEnCours(): bool
    {
        return $this->etat === self::STATUT_EN_COURS;
    }

    public function isTermine(): bool
    {
        return $this->etat === self::STATUT_TERMINE;
    }

    public function isCorrige(): bool
    {
        return $this->etat === self::STATUT_CORRIGE;
    }

    // Helpers examen

    public function getNombreQuestionsReelles(): int
    {
        return $this->questions()->actif()->count();
    }

    public function getTotalPoints(): float
    {
        return (float) $this->questions()->actif()->sum('points');
    }

    public function getNombreTentatives(int $apprenantId): int
    {
        return $this->tentatives()->where('apprenant_id', $apprenantId)->count();
    }

    public function peutTenterExamen(int $apprenantId): bool
    {
        if (!in_array($this->etat, [self::STATUT_PUBLIE, self::STATUT_EN_COURS])) {
            return false;
        }
        return $this->getNombreTentatives($apprenantId) < $this->nombre_tentatives;
    }

    // Scopes

    public function scopeActif($query)
    {
        return $query->where('etat', '!=', self::STATUT_BROUILLON);
    }

    public function scopeBrouillon($query)
    {
        return $query->where('etat', self::STATUT_BROUILLON);
    }
}
