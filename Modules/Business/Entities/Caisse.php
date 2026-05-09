<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use Database\Factories\CaisseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Caisse extends BaseModel
{
    use SoftDeletes,HasFactory;
    protected $table = 'caisses';

    protected $fillable = [
        'points_vente_id',
        'code',
        'nom',
        'type',
        'statut',
        'parametres_json',
    ];
    protected $casts = [
        'parametres_json' => 'array',
    ];
    protected static function newFactory()
    {
        return CaisseFactory::new();
    }
    public function pointVente(): BelongsTo
    {
        return $this->belongsTo(PointVente::class, 'points_vente_id');
    }

    /**
     * Vérifie si la caisse est ouverte
     */
    public function isOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }

    /**
     * Vérifie si la caisse est fermée
     */
    public function isFermee(): bool
    {
        return $this->statut === 'fermee';
    }

    /**
     * Vérifie si la caisse est bloquée
     */
    public function isBloquee(): bool
    {
        return $this->statut === 'bloquee';
    }

    /**
     * Ouvrir la caisse
     */
    public function ouvrir(): void
    {
        $this->update([
            'statut' => 'ouverte',
        ]);
    }

    /**
     * Fermer la caisse
     */
    public function fermer(): void
    {
        $this->update([
            'statut' => 'fermee',
        ]);
    }

    /**
     * Bloquer la caisse
     */
    public function bloquer(): void
    {
        $this->update([
            'statut' => 'bloquee',
        ]);
    }



}
