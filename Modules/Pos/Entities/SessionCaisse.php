<?php

namespace Modules\Pos\Entities;

use App\Models\BaseModel;
use App\Models\User;
use Database\Factories\SessionCaisseFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Business\Entities\Caisse;
use Modules\Business\Entities\Employe;
use Modules\Business\Entities\Terminal;

class SessionCaisse extends BaseModel
{
    use SoftDeletes,HasFactory;

    protected $table = 'sessions_caisse';

    protected $fillable = [
        'caisse_id',
        'caissier_id',
        'terminal_id',
        'opened_at',
        'closed_at',
        'reference',
        'fond_ouverture_cents',
        'total_encaisse_cents',
        'total_reel_cents',
        'ecart_cents',
        'statut',
        'raison_annulation',
        'devise',
        'validated_at',
        'validated_by',
        'validation_commentaire',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'fond_ouverture_cents' => 'integer',
        'total_encaisse_cents' => 'integer',
        'total_reel_cents' => 'integer',
        'ecart_cents' => 'integer',
    ];

    protected static function newFactory()
    {
        return SessionCaisseFactory::new();
    }
    /**
     * Relation avec la caisse
     */
    public function caisse(): BelongsTo
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
    }
    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }


    /**
     * Relation avec le caissier (Employe)
     */
    public function caissier(): BelongsTo
    {
        return $this->belongsTo(Employe::class, 'caissier_id');
    }

    /**
     * Relation avec le user (Caissier)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'caissier_id');
    }

    /**
     * Relation avec le terminal
     */
    public function terminal(): BelongsTo
    {
        return $this->belongsTo(Terminal::class, 'terminal_id');
    }

    /**
     * Vérifie si la session est ouverte
     */
    public function isOuverte(): bool
    {
        return $this->statut === 'ouverte';
    }

    /**
     * Vérifie si la session est fermée
     */
    public function isFermee(): bool
    {
        return $this->statut === 'fermee';
    }

    /**
     * Vérifie si la session est annulée
     */
    public function isAnnulee(): bool
    {
        return $this->statut === 'annulee';
    }

    /**
     * Calculer l'écart entre le total théorique et le total réel
     */
    public function calculerEcart(): int
    {
        if ($this->total_reel_cents === null) {
            return 0;
        }

        return $this->total_reel_cents - $this->total_encaisse_cents;
    }

    /**
     * Fermer la session de caisse
     */
    public function fermer(int $totalReelCents): bool
    {
        $this->total_reel_cents = $totalReelCents;
        $this->ecart_cents = $this->calculerEcart();
        $this->statut = 'fermee';
        $this->closed_at = now();
        return $this->save();
    }

    /**
     * Annuler la session de caisse
     */
    public function annuler(string $raison = null): bool
    {
        $this->statut = 'annulee';
        $this->closed_at = now();

        if ($raison) {
            // Si vous avez un champ pour stocker la raison d'annulation
             $this->raison_annulation = $raison;
        }

        return $this->save();
    }

}
