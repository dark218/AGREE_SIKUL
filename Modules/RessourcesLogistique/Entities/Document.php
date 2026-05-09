<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\GestionStock\Entities\Fichier;

class Document extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'documents';

    protected $fillable = [
        'categorie_id',
        'titre',
        'description',
        'fichier_id',
        'auteur_id',
        'cibles',
        'date_publication',
    ];

    protected $casts = [
        'cibles' => 'array',
        'date_publication' => 'datetime',
    ];

    // Relations
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieDocument::class, 'categorie_id');
    }

    public function fichier(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'fichier_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'auteur_id');
    }

    // Scopes
    public function scopeByCategorie($query, int $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }

    public function scopePublie($query)
    {
        return $query->where('date_publication', '<=', now());
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_publication', 'desc');
    }

    // Méthodes métier
    public function isPublished(): bool
    {
        return $this->date_publication <= now();
    }

    public function hasFichier(): bool
    {
        return $this->fichier_id !== null;
    }

    public function getCibleName(string $cibleType): array
    {
        $cibles = $this->cibles ?? [];
        return array_filter($cibles, function ($cible) use ($cibleType) {
            return isset($cible['type']) && $cible['type'] === $cibleType;
        });
    }
}
