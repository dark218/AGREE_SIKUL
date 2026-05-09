<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\PointVente;

class Inventaire extends BaseModel
{
    use  SoftDeletes;

    protected $table = 'inventaires';

    protected $fillable = [
        'emplacement_id',
        'date_inventaire',
        'statut',
        'cree_par',
        'valide_par',
        'date_validation',
        'commentaire',
    ];


    protected $casts = [
        'date_inventaire'  => 'date',
        'date_validation'  => 'datetime',
    ];

    public const STATUT_BROUILLON  = 'brouillon';
    public const STATUT_VALIDE  = 'valide';
    public const STATUT_ANNULE  = 'annule';

    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'emplacement_id');
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(InventaireLigne::class, 'inventaire_id');
    }

    public function creePar(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'cree_par');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'valide_par');
    }

    /**
     * =========================
     * Helpers métier
     * =========================
     */

    public function estBrouillon(): bool
    {
        return $this->statut === 'brouillon';
    }

    public function estValide(): bool
    {
        return $this->statut === 'valide';
    }

    public function estAnnule(): bool
    {
        return $this->statut === 'annule';
    }

    public function totalEcart(): int
    {
        return $this->lignes->sum('ecart');
    }


}
