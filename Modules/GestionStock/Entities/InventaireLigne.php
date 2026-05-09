<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventaireLigne extends BaseModel
{

    protected $table = 'inventaire_lignes';

    protected $fillable = [
        'inventaire_id',
        'article_id',
        'quantite_systeme',
        'quantite_comptabilisee',
        'ecart',
        'commentaire',
    ];

    protected $casts = [
        'quantite_systeme'       => 'integer',
        'quantite_comptabilisee' => 'integer',
        'ecart'                  => 'integer',
    ];

    /**
     * =========================
     * Relations
     * =========================
     */

    public function inventaire(): BelongsTo
    {
        return $this->belongsTo(Inventaire::class, 'inventaire_id');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * =========================
     * Helpers métier
     * =========================
     */

    public function calculerEcart(): int
    {
        return $this->quantite_comptabilisee - $this->quantite_systeme;
    }

    public function estEnSurplus(): bool
    {
        return $this->ecart > 0;
    }

    public function estEnManque(): bool
    {
        return $this->ecart < 0;
    }
}
