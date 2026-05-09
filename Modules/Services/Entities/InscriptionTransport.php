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

    protected $table = 'inscriptions_transport';

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

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function scopeInactif($query)
    {
        return $query->where('statut', 'inactif');
    }

    // Méthodes métier
    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }

    public function getFormattedPointRamassage(): string
    {
        return $this->point_ramassage ?? 'Non spécifié';
    }
}
