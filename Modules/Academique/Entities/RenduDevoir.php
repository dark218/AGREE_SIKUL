<?php

namespace Modules\Academique\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Parametrage\Entities\Fichier;

class RenduDevoir extends BaseModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'rendus_devoirs';

    protected $fillable = [
        'devoir_id',
        'apprenant_id',
        'date_rendu',
        'fichier_id',
        'note_finale',
        'notes_enseignant',
        'statut',
    ];

    protected $casts = [
        'date_rendu' => 'datetime',
        'note_finale' => 'decimal:2',
    ];

    // Relations
    public function devoir(): BelongsTo
    {
        return $this->belongsTo(Devoir::class, 'devoir_id');
    }

    public function apprenant(): BelongsTo
    {
        return $this->belongsTo(Apprenant::class, 'apprenant_id');
    }

    public function fichier(): BelongsTo
    {
        return $this->belongsTo(Fichier::class, 'fichier_id');
    }

    // Scopes
    public function scopeRendu($query)
    {
        return $query->whereNotNull('date_rendu');
    }

    public function scopeNonRendu($query)
    {
        return $query->whereNull('date_rendu');
    }

    public function scopeNote($query)
    {
        return $query->whereNotNull('note_finale');
    }

    // Méthodes métier
    public function isRendu(): bool
    {
        return $this->date_rendu !== null;
    }

    public function isNote(): bool
    {
        return $this->note_finale !== null;
    }
}
