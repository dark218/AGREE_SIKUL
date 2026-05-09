<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Fichier;

class DossierApprenant extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dossiers_apprenants';

    protected $fillable = [
        'apprenant_id',
        'extrait_naissance_id',
        'certificat_residence_id',
        'carnet_sante_id',
        'dernier_bulletin_id',
        'extrait_naissance',
        'certificat_residence',
        'carnet_sante',
        'dernier_bulletin',
    ];

    // Relations
    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function extraitNaissance(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'extrait_naissance_id');
    }

    public function certificatResidence(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'certificat_residence_id');
    }

    public function carnetSante(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'carnet_sante_id');
    }

    public function dernierBulletin(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'dernier_bulletin_id');
    }

    // Méthodes métier
    public function isDossierComplet(): bool
    {
        return $this->extrait_naissance_id !== null &&
               $this->certificat_residence_id !== null &&
               $this->carnet_sante_id !== null;
    }
}
