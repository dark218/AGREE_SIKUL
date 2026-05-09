<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ecole\Entities\Ecole;

class Bibliotheque extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'bibliotheques';

    protected $fillable = [
        'ecole_id',
        'nom',
        'adresse',
        'capacite',
        'responsable_id',
    ];

    protected $casts = [
        'capacite' => 'integer',
    ];

    // Relations
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class, 'ecole_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function ouvrages(): HasMany
    {
        return $this->hasMany(Ouvrage::class, 'bibliotheque_id');
    }

    public function emprunts(): HasMany
    {
        return $this->hasMany(Emprunt::class, 'bibliotheque_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'bibliotheque_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreOuvrages(): int
    {
        return $this->ouvrages()->count();
    }

    public function getNombreEmpruntEnCours(): int
    {
        return $this->emprunts()->where('statut', 'en_cours')->count();
    }
}
