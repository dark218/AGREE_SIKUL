<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CategorieDocument extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'categories_documents';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'icone',
        'couleur',
        'statut',
    ];

    // Relations
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class, 'categorie_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->whereNull('deleted_at');
    }

    // Méthodes métier
    public function getNombreDocuments(): int
    {
        return $this->documents()->count();
    }

    public function getIconeCSS(): string
    {
        return $this->icone ?? 'far fa-file';
    }
}
