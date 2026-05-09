<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class PlanificationExamenPosteRecette extends BaseModel
{
    use HasFactory;

    protected $table = 'exam_poste_recette';

    protected $fillable = [
        'exam_id',
        'recette_id',
        'montant_finance',
        'pourcentage_couverture',
        'date_facturation',
        'date_limite_paiement',
        'etat_financement',
        'notes',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'montant_finance' => 'decimal:2',
        'pourcentage_couverture' => 'decimal:2',
        'date_facturation' => 'date',
        'date_limite_paiement' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function planificationExamen(): BelongsTo
    {
        return $this->belongsTo(PlanificationExamen::class, 'exam_id');
    }

    public function posteRecette(): BelongsTo
    {
        return $this->belongsTo(\Modules\Finances\Entities\PosteRecette::class, 'recette_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(ExamFinanceAuditLog::class, 'exam_poste_recette_id');
    }

    /**
     * Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('etat_financement', 'actif');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('etat_financement', 'en-attente');
    }

    public function scopeCloture($query)
    {
        return $query->where('etat_financement', 'clôturé');
    }

    public function scopeNonFinances($query)
    {
        return $query->where(function ($q) {
            $q->where('etat_financement', '!=', 'actif')
              ->orWhereNull('montant_finance');
        });
    }

    public function scopeProchesExamen($query, $jours = 7)
    {
        return $query->whereHas('planificationExamen', function ($q) use ($jours) {
            $q->whereDate('date', '<=', now()->addDays($jours))
              ->whereDate('date', '>', now());
        });
    }

    public function scopePourMois($query, Carbon $mois)
    {
        $debut = $mois->copy()->startOfMonth();
        $fin = $mois->copy()->endOfMonth();

        return $query->whereHas('planificationExamen', function ($q) use ($debut, $fin) {
            $q->whereBetween('date', [$debut, $fin]);
        });
    }

    /**
     * Accesseurs
     */
    public function getMontantNetCalculeAttribute(): float
    {
        return (float) ($this->montant_finance ?? 0);
    }

    public function getEcartMontantAttribute(): float
    {
        // Différence entre montant facturé et montant financé
        return 0; // À implémenter avec logique métier
    }

    public function getJoursRestantsAttribute(): ?int
    {
        if (!$this->date_limite_paiement) {
            return null;
        }

        $limite = \Carbon\Carbon::parse($this->date_limite_paiement);
        if ($limite->isPast()) {
            return 0;
        }

        return now()->diffInDays($limite);
    }

    /**
     * Méthodes Métier
     */
    public function valider($raison = null): bool
    {
        if ($this->etat_financement === 'actif') {
            return true; // Déjà validé
        }

        $this->etat_financement = 'actif';
        $this->save();

        $this->logAction('validated', $raison);

        return true;
    }

    public function rejeter($raison): bool
    {
        $this->etat_financement = 'en-attente';
        $this->save();

        $this->logAction('rejected', $raison);

        return true;
    }

    public function cloturer($raison = null): bool
    {
        $this->etat_financement = 'clôturé';
        $this->save();

        $this->logAction('closed', $raison);

        return true;
    }

    public function estExpiree(): bool
    {
        if (!$this->date_limite_paiement) {
            return false;
        }

        return \Carbon\Carbon::parse($this->date_limite_paiement)->isPast();
    }

    public function estEnAttente(): bool
    {
        return $this->etat_financement === 'en-attente';
    }

    public function estActif(): bool
    {
        return $this->etat_financement === 'actif';
    }

    public function estCloture(): bool
    {
        return $this->etat_financement === 'clôturé';
    }

    public function estCoherent(): bool
    {
        // Vérifier cohérence des dates
        if (!$this->date_facturation || !$this->planificationExamen?->date) {
            return false;
        }

        // Date facturation doit être avant exam
        if ($this->date_facturation >= $this->planificationExamen->date) {
            return false;
        }

        // Si date limite paiement existe, elle doit être >= date facturation
        if ($this->date_limite_paiement && $this->date_limite_paiement < $this->date_facturation) {
            return false;
        }

        return true;
    }

    public function logAction($action, $raison = null): void
    {
        ExamFinanceAuditLog::create([
            'exam_poste_recette_id' => $this->id,
            'auteur_id' => auth()?->user()?->id,
            'action' => $action,
            'nouvelle_valeur_etat' => $this->etat_financement,
            'raison_changement' => $raison,
        ]);
    }
}
