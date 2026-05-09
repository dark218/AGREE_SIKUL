<?php

namespace Modules\Academique\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ExamFinanceAuditLog extends Model
{
    use HasFactory;

    protected $table = 'exam_finance_audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'exam_poste_recette_id',
        'auteur_id',
        'action',
        'ancienne_valeur_etat',
        'nouvelle_valeur_etat',
        'montant_precedent',
        'montant_nouveau',
        'raison_changement',
        'donnees_supplementaires',
    ];

    protected $casts = [
        'montant_precedent' => 'decimal:2',
        'montant_nouveau' => 'decimal:2',
        'donnees_supplementaires' => 'json',
        'created_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function examPosteRecette(): BelongsTo
    {
        return $this->belongsTo(PlanificationExamenPosteRecette::class, 'exam_poste_recette_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    /**
     * Scopes
     */
    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeParAction($query, $action)
    {
        return $query->where('action', $action);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('created_at', [$debut, $fin]);
    }

    /**
     * Accesseurs
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            'created' => 'Créé',
            'updated' => 'Mis à jour',
            'status_changed' => 'État changé',
            'validated' => 'Validé',
            'rejected' => 'Rejeté',
            'closed' => 'Clôturé',
            'deleted' => 'Supprimé',
        ];

        return $labels[$this->action] ?? $this->action;
    }

    public function getChangementDetailAttribute(): array
    {
        $details = [];

        if ($this->ancienne_valeur_etat && $this->nouvelle_valeur_etat) {
            $details['etat'] = [
                'avant' => $this->ancienne_valeur_etat,
                'apres' => $this->nouvelle_valeur_etat,
            ];
        }

        if ($this->montant_precedent !== null && $this->montant_nouveau !== null) {
            $details['montant'] = [
                'avant' => $this->montant_precedent,
                'apres' => $this->montant_nouveau,
            ];
        }

        return $details;
    }
}
