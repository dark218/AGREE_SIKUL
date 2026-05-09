<?php

namespace Modules\Personnel\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Modules\Parametrage\Entities\Zone;

class AffectationAgent extends BaseModel
{
    use HasFactory;

    protected $table = 'affectations_agents';

    // Rôles possibles pour une affectation
    const ROLE_AGENT_COMMERCIAL = 'agent_commercial';
    const ROLE_SUPERVISEUR_ZONE = 'superviseur_zone';
    const ROLE_CONTROLEUR = 'controleur';
    const ROLE_SUPPORT_TERRAIN = 'support_terrain';

    // Liste des rôles disponibles
    const ROLES = [
        self::ROLE_AGENT_COMMERCIAL,
        self::ROLE_SUPERVISEUR_ZONE,
        self::ROLE_CONTROLEUR,
        self::ROLE_SUPPORT_TERRAIN,
    ];

    protected $fillable = [
        'agent_id',
        'zone_id',
        'date_affectation',
        'date_desaffectation',
        'actif',
        'role_affectation'
    ];

    protected $casts = [
        'date_affectation' => 'datetime',
        'date_desaffectation' => 'datetime',
        'actif' => 'boolean'
    ];

    /**
     * Relation avec l'agent (utilisateur)
     */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /**
     * Relation avec la zone
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    /**
     * Scope pour les affectations actives
     */
    public function scopeActives($query)
    {
        return $query->where('actif', true)
                    ->where(function ($q) {
                        $q->whereNull('date_desaffectation')
                          ->orWhere('date_desaffectation', '>', now());
                    });
    }

    /**
     * Scope pour les affectations inactives
     */
    public function scopeInactives($query)
    {
        return $query->where('actif', false)
                    ->orWhere(function ($q) {
                        $q->whereNotNull('date_desaffectation')
                          ->where('date_desaffectation', '<=', now());
                    });
    }

    /**
     * Scope pour les affectations par rôle
     */
    public function scopeParRole($query, $role)
    {
        return $query->where('role_affectation', $role);
    }

    /**
     * Scope pour les affectations d'un agent
     */
    public function scopePourAgent($query, $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * Scope pour les affectations d'une zone
     */
    public function scopePourZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    /**
     * Vérifie si l'affectation est actuellement active
     */
    public function estActive()
    {
        return $this->actif &&
               (!$this->date_desaffectation || $this->date_desaffectation->isFuture());
    }

    /**
     * Vérifie si l'affectation est expirée
     */
    public function estExpiree()
    {
        return $this->date_desaffectation && $this->date_desaffectation->isPast();
    }

    /**
     * Active l'affectation
     */
    public function activer()
    {
        $this->actif = true;
        $this->date_desaffectation = null;
        $this->save();
    }

    /**
     * Désactive l'affectation avec une date de fin
     */
    public function desactiver($dateDesaffectation = null)
    {
        $this->actif = false;
        $this->date_desaffectation = $dateDesaffectation ?? now();
        $this->save();
    }

    /**
     * Récupère la durée de l'affectation en jours
     */
    public function getDureeEnJours()
    {
        if (!$this->date_desaffectation) {
            return $this->date_affectation->diffInDays(now());
        }

        return $this->date_affectation->diffInDays($this->date_desaffectation);
    }

    /**
     * Vérifie si l'agent a le rôle spécifié dans cette affectation
     */
    public function aLeRole($role)
    {
        return $this->role_affectation === $role;
    }

    /**
     * Scope pour les agents commerciaux
     */
    public function scopeAgentsCommerciaux($query)
    {
        return $query->parRole(self::ROLE_AGENT_COMMERCIAL);
    }

    /**
     * Scope pour les superviseurs de zone
     */
    public function scopeSuperviseursZone($query)
    {
        return $query->parRole(self::ROLE_SUPERVISEUR_ZONE);
    }

    /**
     * Scope pour les contrôleurs
     */
    public function scopeControleurs($query)
    {
        return $query->parRole(self::ROLE_CONTROLEUR);
    }

    /**
     * Scope pour les supports terrain
     */
    public function scopeSupportsTerrain($query)
    {
        return $query->parRole(self::ROLE_SUPPORT_TERRAIN);
    }
}
