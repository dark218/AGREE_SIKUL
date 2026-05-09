<?php

namespace Modules\Services\Entities;

use App\Models\BaseModel;

class MenuCantine extends BaseModel
{

    protected $table = 'menu_cantines';

    protected $fillable = [
        'service_cantine_id',
        'week_start_date',
        'week_end_date',
        'week_name',
        'week_number',
        'year',
        'jour',
        'entree',
        'plat',
        'dessert',
        'remarques',
        'statut',
        'creation_username',
        'modification_username',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    // Relationships
    public function serviceCantine()
    {
        return $this->belongsTo(ServiceCantine::class, 'service_cantine_id');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }

    public function isActif(): bool
    {
        return $this->statut === 'actif';
    }
}
