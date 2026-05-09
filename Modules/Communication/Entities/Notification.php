<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'titre',
        'message',
        'data',
        'lu_at',
        'action_url',
    ];

    protected $casts = [
        'data' => 'array',
        'lu_at' => 'datetime',
    ];

    // Relations
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeLue($query)
    {
        return $query->whereNotNull('lu_at');
    }

    public function scopeNonLue($query)
    {
        return $query->whereNull('lu_at');
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Méthodes métier
    public function isLue(): bool
    {
        return $this->lu_at !== null;
    }

    public function mark(): void
    {
        if (!$this->isLue()) {
            $this->update(['lu_at' => now()]);
        }
    }
}
