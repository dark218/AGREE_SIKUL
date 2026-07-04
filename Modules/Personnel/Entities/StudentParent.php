<?php

namespace Modules\Personnel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Institution;
use Modules\Parametrage\Entities\Campus;

class StudentParent extends BaseModel
{
    use HasFactory;

    protected $table = 'parents';

    protected $fillable = [
        // Compte utilisateur associé (auto-créé à la saisie du profil parent)
        'user_id',
        // Informations de l'apprenant
        'apprenant_id', 'classe_id', 'ecole_id', 'institution_id', 'campus_id',
        // Père
        'pere_nom', 'pere_prenoms', 'pere_nom_complet', 'pere_profession', 'pere_organisation_travail',
        'pere_ville_travail', 'pere_pays_travail', 'pere_adresse_residence', 'pere_quartier', 'pere_commune',
        'pere_departement', 'pere_region', 'pere_code_postal', 'pere_boite_postal',
        'pere_telephone_1', 'pere_telephone_2', 'pere_whatsapp_1', 'pere_whatsapp_2',
        'pere_email_1', 'pere_email_2',
        // Mère
        'mere_nom', 'mere_prenoms', 'mere_nom_complet', 'mere_profession', 'mere_organisation_travail',
        'mere_ville_travail', 'mere_pays_travail', 'mere_adresse_residence', 'mere_quartier', 'mere_commune',
        'mere_departement', 'mere_region', 'mere_code_postal', 'mere_boite_postal',
        'mere_telephone_1', 'mere_telephone_2', 'mere_whatsapp_1', 'mere_whatsapp_2',
        'mere_email_1', 'mere_email_2',
        // Tuteur 1
        'tuteur1_nom', 'tuteur1_prenoms', 'tuteur1_nom_complet', 'tuteur1_profession', 'tuteur1_organisation_travail',
        'tuteur1_ville_travail', 'tuteur1_pays_travail', 'tuteur1_adresse_residence', 'tuteur1_quartier', 'tuteur1_commune',
        'tuteur1_arrondissement', 'tuteur1_ville', 'tuteur1_departement', 'tuteur1_region', 'tuteur1_pays',
        'tuteur1_code_postal', 'tuteur1_boite_postal', 'tuteur1_telephone_1', 'tuteur1_telephone_2',
        'tuteur1_email', 'tuteur1_whatsapp_1', 'tuteur1_whatsapp_2',
        // Tuteur 2
        'tuteur2_nom', 'tuteur2_prenoms', 'tuteur2_nom_complet', 'tuteur2_profession', 'tuteur2_organisation_travail',
        'tuteur2_ville_travail', 'tuteur2_pays_travail', 'tuteur2_adresse_residence', 'tuteur2_quartier', 'tuteur2_commune',
        'tuteur2_arrondissement', 'tuteur2_ville', 'tuteur2_departement', 'tuteur2_region', 'tuteur2_pays',
        'tuteur2_code_postal', 'tuteur2_boite_postal', 'tuteur2_telephone_1', 'tuteur2_telephone_2',
        'tuteur2_email', 'tuteur2_whatsapp_1', 'tuteur2_whatsapp_2',
        // Audit
        'etat', 'creation_username', 'modification_username',
    ];

    protected $casts = [
        'etat' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    /**
     * @deprecated Utilisez `apprenants()` (relation N-N via pivot `apprenant_parent`).
     * Cette relation legacy est conservée pour compatibilité avec l'ancien
     * code qui référence encore `parents.apprenant_id`. À supprimer une
     * fois tout le code porté.
     */
    public function apprenant()
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    /**
     * Nouvelle relation canonique — un parent est lié à N apprenants
     * d'une même école (fratrie).
     */
    public function apprenants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Apprenant::class, 'apprenant_parent', 'parent_id', 'apprenant_id')
            ->withPivot('lien_parente', 'est_principal')
            ->withTimestamps();
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class, 'institution_id');
    }

    public function campus()
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    // Helper methods
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
