<?php

namespace Modules\Services\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Campus;
use Modules\Parametrage\Entities\Ecole;

class ServicesTransport extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'services_transports';

    protected $fillable = [
        'annee_scolaire_id',
        'ecole_id',
        'campus_id',
        'zone',
        'ligne',
        'point_depart',
        'point_1',
        'point_2',
        'point_3',
        'point_4',
        'point_5',
        'point_6',
        'point_7',
        'point_8',
        'point_9',
        'point_10',
        'tarif_mensuel',
        'tarif_trimestriel',
        'tarif_semestriel',
        'tarif_annuel',
        'date_debut',
        'date_fin',
        'etat',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'tarif_mensuel' => 'decimal:2',
        'tarif_trimestriel' => 'decimal:2',
        'tarif_semestriel' => 'decimal:2',
        'tarif_annuel' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class, 'campus_id');
    }

    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    public function isActif(): bool
    {
        return $this->etat === 'actif';
    }
}
