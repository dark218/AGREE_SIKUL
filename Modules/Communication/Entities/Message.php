<?php

namespace Modules\Communication\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'messages';

    protected $fillable = [
        'expediteur_id',
        'destinataires_ids',
        'objet',
        'contenu',
        'lu_par',
        'date_envoi',
    ];

    protected $casts = [
        'destinataires_ids' => 'array',
        'lu_par' => 'array',
        'date_envoi' => 'datetime',
    ];

    protected $appends = ['etat'];

    // Relations
    public function expediteur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'expediteur_id');
    }

    // Scopes
    public function scopeLu($query)
    {
        return $query->whereNotNull('lu_par');
    }

    public function scopeNonLu($query)
    {
        return $query->whereNull('lu_par');
    }

    // Accessors
    public function getEtatAttribute()
    {
        return $this->trashed() ? 'inactif' : 'actif';
    }

    // Méthodes métier
    public function isLu(): bool
    {
        return $this->lu_par !== null && count($this->lu_par) > 0;
    }

    public function markAsRead(int $userId): void
    {
        $luPar = $this->lu_par ?? [];
        if (!in_array($userId, $luPar)) {
            $luPar[] = $userId;
            $this->update(['lu_par' => $luPar]);
        }
    }
}
