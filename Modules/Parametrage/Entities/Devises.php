<?php

namespace Modules\Parametrage\Entities;

use Database\Factories\DevisesFactory;
use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Devises extends BaseModel
{
    use HasFactory;

    protected $table="devises";
    protected $fillable = [
        'code',
        'code_iso',
        'libelle',
        'symbol',
        'pays_id',
        'decimal_places',
        'exchange_rate',
        'is_default',
        'etat',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected static function newFactory()
    {
        return DevisesFactory::new();
    }

    public function pays(): BelongsTo
    {
        return $this->belongsTo(Pays::class, 'pays_id');
    }

    public function paysDevises(): HasMany
    {
        return $this->hasMany(PaysDevise::class,'devise_id');
    }

    /**
     * Scope: Get active devises only
     */
    public function scopeActif($query)
    {
        return $query->where('etat', 'actif');
    }

    /**
     * Scope: Get the default currency
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true)->first();
    }

    /**
     * Format currency amount with symbol
     */
    public function formatAmount($amount): string
    {
        $formatted = number_format($amount, $this->decimal_places ?? 2, ',', ' ');
        return $this->symbol ? $this->symbol . ' ' . $formatted : $formatted;
    }

    /**
     * Format currency display (e.g., "USD ($)")
     */
    public function getDisplayFormat(): string
    {
        $parts = [];
        if ($this->code_iso) {
            $parts[] = $this->code_iso;
        }
        if ($this->symbol) {
            $parts[] = '(' . $this->symbol . ')';
        }
        return implode(' ', $parts) ?: $this->code;
    }
}
