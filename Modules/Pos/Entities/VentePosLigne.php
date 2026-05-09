<?php

namespace Modules\Pos\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\GestionStock\Entities\Article;

class VentePosLigne extends BaseModel
{

    protected $table = 'vente_pos_lignes';

    protected $fillable = [
        'ventes_pos_id',
        'article_id',
        'type_ligne',
        'libelle',
        'quantite',
        'prix_unitaire_cents',
        'remise_cents',
        'taxe_cents',
        'total_ligne_cents',
        'devise',
        'rembourse_cents',
        'rembourse_at',
        'rembourse_by',
        'quantite_remboursee',
    ];
    public function vente(): BelongsTo
    {
        return $this->belongsTo(VentePos::class, 'ventes_pos_id');
    }
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }
    public function rembourseur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rembourse_by');
    }
    public function isArticle(): bool
    {
        return $this->type_ligne === 'article';
    }

    public function isRemise(): bool
    {
        return $this->type_ligne === 'remise';
    }

    public function isFrais(): bool
    {
        return $this->type_ligne === 'frais';
    }
}
