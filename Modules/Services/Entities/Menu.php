<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'menus';

    protected $fillable = [
        'service_cantine_id',
        'week_start_date',
        'week_end_date',
        'week_name',
        'jour',
        'date',
        'plat_principal',
        'accompagnement',
        'dessert',
        'remarques',
        'prix_cents',
        'statut',
    ];

    protected $casts = [
        'date' => 'date',
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'prix_cents' => 'integer',
    ];

    // Relations
    public function serviceCantine(): BelongsTo
    {
        return $this->belongsTo(ServiceCantine::class, 'service_cantine_id');
    }

    // Scopes
    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeByServiceCantine($query, int $serviceId)
    {
        return $query->where('service_cantine_id', $serviceId);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    // Méthodes métier
    public function getPrixEnEuros(): float
    {
        return $this->prix_cents / 100;
    }

    public function getCompositionComplete(): string
    {
        $composition = [];
        if ($this->plat_principal) {
            $composition[] = $this->plat_principal;
        }
        if ($this->accompagnement) {
            $composition[] = $this->accompagnement;
        }
        if ($this->dessert) {
            $composition[] = $this->dessert;
        }
        return implode(' | ', $composition);
    }
}
