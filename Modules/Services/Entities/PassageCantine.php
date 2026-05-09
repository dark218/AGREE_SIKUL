<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PassageCantine extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'passages_cantine';

    protected $fillable = [
        'inscription_cantine_id',
        'date_passage',
        'heure_passage',
    ];

    protected $casts = [
        'date_passage' => 'date',
        'heure_passage' => 'time',
    ];

    // Relations
    public function inscriptionCantine(): BelongsTo
    {
        return $this->belongsTo(InscriptionCantine::class, 'inscription_cantine_id');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('date_passage', $date);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_passage', 'desc')
            ->orderBy('heure_passage', 'desc');
    }

    // Méthodes métier
    public function getApprenant()
    {
        return $this->inscriptionCantine?->apprenant;
    }

    public function getServiceCantine()
    {
        return $this->inscriptionCantine?->serviceCantine;
    }
}
