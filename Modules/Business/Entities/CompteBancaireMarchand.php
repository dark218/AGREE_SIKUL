<?php

namespace Modules\Business\Entities;

use App\Models\BaseModel;
use Database\Factories\CompteBancaireMarchandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Banque;

class CompteBancaireMarchand extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = "comptes_bancaires_marchand";

    protected $fillable = [
        "marchand_id",
        "banque_id",
        "nom_compte",
        "numero_compte",
        "iban",
        "bic_swift",
        "is_principal",
        "is_active",
        "meta_json",
    ];

    protected $casts = [
        'is_principal' => 'boolean',
        'is_active' => 'boolean',
        'meta_json' => 'array',
    ];

    protected static function newFactory()
    {
        return CompteBancaireMarchandFactory::new();
    }

    public function marchand(): BelongsTo
    {
        return $this->belongsTo(Marchand::class, 'marchand_id');
    }

    public function banque(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'banque_id');
    }

    public function toggleActive()
    {
        $this->update(['is_active' => !$this->is_active]);
        return $this;
    }
}