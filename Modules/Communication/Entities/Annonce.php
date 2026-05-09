<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Annonce extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'annonces';

    protected $fillable = [
        'auteur_id',
        'titre',
        'contenu',
        'date_publication',
        'date_expiration',
        'statut',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'date_publication' => 'datetime',
        'date_expiration' => 'datetime',
    ];

    protected $appends = ['etat'];

    // Relations
    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    public function commentaires(): HasMany
    {
        return $this->hasMany(CommentaireAnnonce::class, 'annonce_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'active');
    }

    public function scopePublie($query)
    {
        return $query->where('date_publication', '<=', now());
    }

    public function scopeEnCours($query)
    {
        return $query->where('date_publication', '<=', now())
            ->where(function ($q) {
                $q->whereNull('date_expiration')
                  ->orWhere('date_expiration', '>=', now());
            });
    }

    // Méthodes métier
    public function isPublished(): bool
    {
        return $this->date_publication <= now();
    }

    public function isActive(): bool
    {
        return !$this->trashed() && $this->statut === 'active';
    }

    public function isExpired(): bool
    {
        return $this->date_expiration !== null && $this->date_expiration < now();
    }

    // Accessors
    public function getEtatAttribute()
    {
        return $this->trashed() ? 'inactif' : ($this->statut ?? 'unknown');
    }
}
