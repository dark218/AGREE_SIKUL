<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\AnneeScolaire;

class InscriptionTransport extends BaseModel
{
    use HasFactory, SoftDeletes;

    // Table PLURIEL (schéma canonique avec point_ramassage + enum statut
    // active/suspendue/annulee). `inscriptions_transport` (singulier) est un
    // doublon obsolète créé par Modules/Services parallèle.
    protected $table = 'inscriptions_transports';

    protected $fillable = [
        'service_transport_id',
        'apprenant_id',
        'annee_scolaire_id',
        'point_ramassage',
        'statut',
    ];

    // Relations
    public function serviceTransport(): BelongsTo
    {
        return $this->belongsTo(ServiceTransport::class, 'service_transport_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'apprenant_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    // Scopes — enum DB : active, suspendue, annulee
    public function scopeActif($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopeSuspendue($query)
    {
        return $query->where('statut', 'suspendue');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'active';
    }

    public function getFormattedPointRamassage(): string
    {
        return $this->point_ramassage ?? 'Non spécifié';
    }
}
