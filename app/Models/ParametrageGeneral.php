<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParametrageGeneral extends Model
{
    protected $table = 'parametrage_generaux';

    protected $fillable = [
        'nom_etablissement', 'slogan', 'sigle', 'description',
        'logo_path', 'favicon_path', 'logo_login_path',
        'email', 'telephone', 'telephone2', 'whatsapp', 'site_web',
        'adresse', 'ville', 'pays', 'code_postal', 'boite_postale',
        'facebook', 'twitter', 'linkedin', 'instagram', 'youtube',
        'numero_autorisation', 'registre_commerce', 'numero_fiscal',
        'couleur_primaire', 'couleur_secondaire', 'couleur_accent',
        'devise', 'langue_par_defaut', 'fuseau_horaire', 'annee_scolaire_courante',
    ];

    /**
     * Récupérer les paramètres (singleton — il n'y a qu'une seule ligne)
     */
    public static function get(): self
    {
        return static::first() ?? static::create(['nom_etablissement' => 'AGREE SIKUL']);
    }
}
