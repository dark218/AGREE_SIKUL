<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnneeScolaire extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'annees_scolaires';

    protected $fillable = [
        'code',
        'libelle',
        'date_debut',
        'date_fin',
        'duree',
        'pays_id',
        'est_courante',
        'statut',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'est_courante' => 'boolean',
    ];

    // Relations
    public function pays()
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Pays::class, 'pays_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', '!=', 'terminee');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeCourante($query)
    {
        return $query->where('est_courante', true)->first();
    }

    // Méthodes métier
    public function estCourante(): bool
    {
        return $this->est_courante === true;
    }

    public function estTerminee(): bool
    {
        return $this->statut === 'terminee';
    }

    public function estEnCours(): bool
    {
        return $this->statut === 'en_cours';
    }

    public static function courante()
    {
        return self::where('est_courante', true)->first();
    }

    // Observer: Une seule année scolaire courante à la fois
    protected static function booted()
    {
        static::saving(function ($model) {
            if ($model->est_courante && !$model->exists) {
                // Désactiver les autres années courantes
                self::where('est_courante', true)->update(['est_courante' => false]);
            }
        });
    }
}
