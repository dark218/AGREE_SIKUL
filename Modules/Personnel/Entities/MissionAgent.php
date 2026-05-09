<?php

namespace Modules\Personnel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Parametrage\Entities\Zone;

class MissionAgent extends BaseModel
{
    use HasFactory;

    protected $table = 'missions_agents';

    // Statuts possibles pour une mission
    const STATUT_ASSIGNED = 'assigned';
    const STATUT_EN_COURS = 'en_cours';
    const STATUT_TERMINEE = 'terminee';
    const STATUT_EN_RETARD = 'en_retard';
    const STATUT_ANNULEE = 'annulee';

    // Liste des statuts disponibles
    const STATUTS = [
        self::STATUT_ASSIGNED,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINEE,
        self::STATUT_EN_RETARD,
        self::STATUT_ANNULEE,
    ];

    protected $fillable = [
        'agent_id',
        'zone_id',
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'objectif_json',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'objectif_json' => 'array',
        'meta_json' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relation avec l'agent (utilisateur)
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relation avec la zone (optionnelle)
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * Scope pour les missions actives
     */
    public function scopeActives($query)
    {
        return $query->whereIn('statut', [self::STATUT_ASSIGNED, self::STATUT_EN_COURS]);
    }

    /**
     * Scope pour les missions en cours
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut', self::STATUT_EN_COURS);
    }

    /**
     * Scope pour les missions terminées
     */
    public function scopeTerminees($query)
    {
        return $query->where('statut', self::STATUT_TERMINEE);
    }

    /**
     * Scope pour les missions en retard
     */
    public function scopeEnRetard($query)
    {
        return $query->where('statut', self::STATUT_EN_RETARD)
                    ->orWhere(function ($q) {
                        $q->where('date_fin', '<', now())
                          ->whereNotIn('statut', [self::STATUT_TERMINEE, self::STATUT_ANNULEE]);
                    });
    }

    /**
     * Scope pour les missions d'un agent
     */
    public function scopePourAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope pour les missions d'une zone
     */
    public function scopePourZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Vérifie si la mission est en cours
     */
    public function estEnCours()
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    /**
     * Vérifie si la mission est terminée
     */
    public function estTerminee()
    {
        return $this->statut === self::STATUT_TERMINEE;
    }

    /**
     * Vérifie si la mission est en retard
     */
    public function estEnRetard()
    {
        return $this->statut === self::STATUT_EN_RETARD ||
               ($this->date_fin && $this->date_fin->isPast() && !$this->estTerminee());
    }

    /**
     * Marque la mission comme en cours
     */
    public function demarrer()
    {
        $this->statut = self::STATUT_EN_COURS;
        $this->save();
    }

    /**
     * Marque la mission comme terminée
     */
    public function terminer()
    {
        $this->statut = self::STATUT_TERMINEE;
        $this->save();
    }

    /**
     * Marque la mission comme annulée
     */
    public function annuler()
    {
        $this->statut = self::STATUT_ANNULEE;
        $this->save();
    }
}
