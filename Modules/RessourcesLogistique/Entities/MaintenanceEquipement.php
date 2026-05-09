<?php

namespace Modules\RessourcesLogistique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceEquipement extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'maintenances_equipements';

    protected $fillable = [
        'equipement_id',
        'date_maintenance',
        'type_maintenance',
        'description',
        'cout_cents',
        'technicien_id',
    ];

    protected $casts = [
        'date_maintenance' => 'date',
        'cout_cents' => 'integer',
    ];

    // Relations
    public function equipement(): BelongsTo
    {
        return $this->belongsTo(Equipement::class, 'equipement_id');
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type_maintenance', $type);
    }

    public function scopePreventive($query)
    {
        return $query->where('type_maintenance', 'preventive');
    }

    public function scopeCurative($query)
    {
        return $query->where('type_maintenance', 'curative');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('date_maintenance', 'desc');
    }

    // Méthodes métier
    public function getCoutEnEuros(): float
    {
        return $this->cout_cents / 100;
    }

    public function isPreventive(): bool
    {
        return $this->type_maintenance === 'preventive';
    }

    public function isCurative(): bool
    {
        return $this->type_maintenance === 'curative';
    }
}
