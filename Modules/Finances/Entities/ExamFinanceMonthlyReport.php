<?php

namespace Modules\Finances\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ExamFinanceMonthlyReport extends Model
{
    use HasFactory;

    protected $table = 'exam_finance_monthly_reports';

    protected $fillable = [
        'mois',
        'ecole_id',
        'total_examens_planifies',
        'total_examens_finances',
        'total_examens_en_attente',
        'total_examens_non_finances',
        'total_examens_clotured',
        'taux_couverture',
        'montant_total_facture',
        'montant_total_paye',
        'montant_total_impaye',
        'total_revenues',
        'total_expenses',
        'solde_net',
        'notes_mois',
        'generated_at',
        'generated_by',
    ];

    protected $casts = [
        'mois' => 'date',
        'taux_couverture' => 'decimal:2',
        'montant_total_facture' => 'decimal:2',
        'montant_total_paye' => 'decimal:2',
        'montant_total_impaye' => 'decimal:2',
        'total_revenues' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'solde_net' => 'decimal:2',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relations
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Parametrage\Entities\Ecole::class, 'ecole_id');
    }

    /**
     * Scopes
     */
    public function scopeParMois($query, Carbon $mois)
    {
        $premierJour = $mois->copy()->startOfMonth();

        return $query->where('mois', $premierJour);
    }

    public function scopeParAnnee($query, $annee)
    {
        return $query->whereYear('mois', $annee);
    }

    public function scopeParEcole($query, $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeParPeriode($query, Carbon $debut, Carbon $fin)
    {
        return $query->whereBetween('mois', [$debut->startOfMonth(), $fin->endOfMonth()]);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('mois', 'desc');
    }

    public function scopeGenere($query)
    {
        return $query->whereNotNull('generated_at');
    }

    /**
     * Accesseurs
     */
    public function getTauxExamensNonFinancesAttribute(): float
    {
        if ($this->total_examens_planifies == 0) {
            return 0;
        }

        return round(
            ($this->total_examens_non_finances / $this->total_examens_planifies) * 100,
            2
        );
    }

    public function getTauxExamensEnAttenteAttribute(): float
    {
        if ($this->total_examens_planifies == 0) {
            return 0;
        }

        return round(
            ($this->total_examens_en_attente / $this->total_examens_planifies) * 100,
            2
        );
    }

    public function getTauxExamensFinancesAttribute(): float
    {
        if ($this->total_examens_planifies == 0) {
            return 0;
        }

        return round(
            ($this->total_examens_finances / $this->total_examens_planifies) * 100,
            2
        );
    }

    public function getTauxExamensCloturesAttribute(): float
    {
        if ($this->total_examens_planifies == 0) {
            return 0;
        }

        return round(
            ($this->total_examens_clotured / $this->total_examens_planifies) * 100,
            2
        );
    }

    public function getMoisFormattedAttribute(): string
    {
        return $this->mois?->format('F Y') ?? '';
    }

    public function getSoldeFormattedAttribute(): string
    {
        $signe = $this->solde_net >= 0 ? '+' : '';

        return $signe . number_format($this->solde_net, 2, ',', ' ');
    }

    /**
     * Méthodes
     */
    public function estComplet(): bool
    {
        return $this->generated_at !== null &&
               $this->total_examens_planifies > 0 &&
               ($this->total_examens_finances + $this->total_examens_en_attente + $this->total_examens_non_finances) === $this->total_examens_planifies;
    }

    public function estDeficitaire(): bool
    {
        return $this->solde_net < 0;
    }

    public function estBienFinance(): bool
    {
        return $this->taux_couverture >= 95;
    }

    public function getDernierMoisGennere()
    {
        return static::genere()->recent()->first();
    }
}
