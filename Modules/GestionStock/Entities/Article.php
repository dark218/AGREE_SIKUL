<?php

namespace Modules\GestionStock\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\ArticleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\PointVente;

class Article extends BaseModel
{
    use SoftDeletes,HasFactory;

    protected $table = 'articles';

    protected $fillable = [
        'points_vente_id',
        'sku',
        'nom',
        'description',
        'prix_cents',
        'devise',
        'marque',
        'quantite_stock',
        'seuil_alert_stock',
        'taxes_json',
        'checksum',
        'external_id',
        'source_system',
        'creation_hostname',
        'modification_hostname',
        'creation_username',
        'modification_username',
        'deletion_username',
        'deleted_by',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'taxes_json' => 'array',
        'prix_cents' => 'integer',
        'quantite_stock' => 'integer',
        'seuil_alert_stock' => 'integer',
    ];

    protected $appends = ['prix'];

    protected static function newFactory()
    {
        return ArticleFactory::new();
    }
    /**
     * Accesseur pour obtenir le prix formaté
     */
    public function getPrixAttribute()
    {
        if ($this->prix_cents === null) {
            return 0;
        }
        return $this->prix_cents;
    }

    /**
     * Relation avec le point de vente
     */
    public function pointVente()
    {
        return $this->belongsTo(PointVente::class, 'points_vente_id');
    }

    /**
     * Relation avec l'utilisateur qui a validé l'article
     */
    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    /**
     * Relation avec l'utilisateur qui a supprimé l'article
     */
    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }



    /**
     * Vérifie si le stock est faible
     */
    public function isStockLow()
    {
        return $this->quantite_stock <= $this->seuil_alert_stock;
    }

}
