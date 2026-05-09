<?php

namespace Modules\Parametrage\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JourFerie extends BaseModel
{
    use HasFactory;

    protected $table = 'jours_feries';

    protected $fillable = [
        'code',
        'libelle',
        'jour',
        'mois',
        'annee',
        'date',
        'est_recurrent',
        'type_jour_ferie',
        'ecole_id',
        'pays_id',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'date' => 'date',
        'est_recurrent' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(\Modules\Ecole\Entities\Ecole::class, 'ecole_id');
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('etat', 'inactif');
    }

    public function scopeParDate($query, $date)
    {
        return $query->where('date', $date);
    }

    // Méthodes utiles
    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }

    public function activate(): self
    {
        $this->update(['etat' => 'actif']);
        return $this;
    }

    public function deactivate(): self
    {
        $this->update(['etat' => 'inactif']);
        return $this;
    }

    public function getFormattedDate(): string
    {
        if ($this->date) {
            return $this->date->format('d-m-Y');
        }
        return sprintf('%02d-%02d-%04d', $this->jour, $this->mois, $this->annee ?? date('Y'));
    }
}
