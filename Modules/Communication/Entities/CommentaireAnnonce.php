<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommentaireAnnonce extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'commentaires_annonces';

    protected $fillable = [
        'annonce_id',
        'auteur_id',
        'contenu',
        'date_publication',
    ];

    protected $casts = [
        'date_publication' => 'datetime',
    ];

    // Relations
    public function annonce(): BelongsTo
    {
        return $this->belongsTo(Annonce::class, 'annonce_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    // Scopes
    public function scopeRecent($query)
    {
        return $query->orderBy('date_publication', 'desc');
    }

    // Méthodes métier
    public function getAuteurNom(): string
    {
        return $this->auteur?->full_name ?? 'Anonyme';
    }
}
