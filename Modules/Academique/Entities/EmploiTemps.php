<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Section;
use Modules\Parametrage\Entities\CycleEnseignement;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\Campus;
use Modules\Academique\Entities\Matiere;
use Modules\Academique\Entities\Enseignant;

class EmploiTemps extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'emplois_temps';

    protected $fillable = [
        'classe_id',
        'annee_scolaire_id',
        'week_start_date',
        'week_end_date',
        'week_number',
        'week_name',
        'year',
        'section_id',
        'cycle_id',
        'ecole_id',
        'campus_id',
        'jour',
        'matiere_id',
        'enseignant_id',
        'duree',
        'date_debut',
        'date_fin',
        'est_valide',
        'statut',
        'creation_username',
        'modification_username',
        'deletion_username',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin' => 'datetime',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'est_valide' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-calculate week fields when saving
        static::creating(function ($model) {
            $model->calculateWeekFields();
        });

        static::updating(function ($model) {
            $model->calculateWeekFields();
        });
    }

    // Calculate week_start_date, week_number, and year from date_debut
    public function calculateWeekFields()
    {
        if ($this->date_debut) {
            $date = is_string($this->date_debut) ? new \DateTime($this->date_debut) : $this->date_debut;

            // Get Monday of the week (ISO 8601 - week starts on Monday)
            $mondayDate = clone $date;
            $dayOfWeek = (int)$mondayDate->format('N'); // 1=Monday, 7=Sunday
            if ($dayOfWeek !== 1) {
                $mondayDate->modify(sprintf('-%d days', $dayOfWeek - 1));
            }

            $this->week_start_date = $mondayDate->format('Y-m-d');
            $this->week_number = (int)$date->format('W'); // ISO 8601 week number
            $this->year = (int)$date->format('Y');
        }
    }

    // Relations
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CycleEnseignement::class, 'cycle_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'matiere_id');
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeValide($query)
    {
        return $query->where('est_valide', true);
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function isValide(): bool
    {
        return $this->est_valide === true;
    }
}
