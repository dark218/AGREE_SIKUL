<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'reservations';

    protected $fillable = [
        'exemplaire_id',
        'apprenant_id',
        'date_reservation',
        'date_expiration',
        'date_notification',
        'priorite',
        'statut',
    ];

    protected $casts = [
        'date_reservation' => 'date',
        'date_expiration' => 'date',
        'date_notification' => 'date',
    ];

    // Relations
    public function exemplaire(): BelongsTo
    {
        return $this->belongsTo(\Modules\RessourcesLogistique\Entities\Exemplaire::class, 'exemplaire_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(\Modules\Academique\Entities\Apprenant::class, 'apprenant_id');
    }

    // Scopes
    public function scopeEnAttente($query)
    {
        return $query->where('statut', 'en_attente');
    }

    public function scopeConfirmee($query)
    {
        return $query->where('statut', 'confirmee');
    }

    public function scopeAnnulee($query)
    {
        return $query->where('statut', 'annulee');
    }

    // Méthodes métier
    public function isEnAttente(): bool
    {
        return $this->statut === 'en_attente';
    }

    public function isConfirmee(): bool
    {
        return $this->statut === 'confirmee';
    }

    public function isAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }
}
