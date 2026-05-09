<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

abstract class BaseModel extends Model
{
    use SoftDeletes, LogsActivity;

    /**
     * Champs communs à tous les modèles
     */
    protected $fillable = [
        // Champs de traçabilité
        'checksum',
        'external_id',
        'source_system',

        // Champs d'audit
        'creation_hostname',
        'modification_hostname',
        'deletion_hostname',
        'creation_username',
        'modification_username',
        'deletion_username',
    ];

    /**
     * Champs cachés par défaut
     */
    protected $hidden = [
        'checksum',
        'external_id',
        'creation_hostname',
        'modification_hostname',
        'deletion_hostname',
        'creation_username',
        'modification_username',
        'deletion_username',
    ];

    /**
     * Casts par défaut
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Dates par défaut
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Configuration Activity Log
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLoggableAttributes())
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Attributs à logger (à surcharger dans chaque modèle)
     */
    protected function getLoggableAttributes(): array
    {
        return [ '*'];
    }

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $table = $model->getTable();
            $schema = \Illuminate\Support\Facades\Schema::class;

            $creationHostname = request()->getHost() ?? gethostname() ?? 'localhost';
            $creationUsername = auth()->check() ? auth()->user()->full_login : 'system';

            // Champs d'audit création (seulement si les colonnes existent)
            if ($schema::hasColumn($table, 'creation_hostname')) {
                $model->creation_hostname = $creationHostname;
            }
            if ($schema::hasColumn($table, 'creation_username')) {
                $model->creation_username = $creationUsername;
            }

            // Champs de traçabilité (seulement si les colonnes existent)
            if ($schema::hasColumn($table, 'source_system')) {
                $model->source_system = 'Agree Sikul';
            }
            if ($schema::hasColumn($table, 'external_id')) {
                $model->external_id = $model->external_id ?? null;
            }

            // Checksum pour intégrité (seulement si la colonne existe)
            if ($schema::hasColumn($table, 'checksum')) {
                $model->checksum = $model->generateChecksum();
            }

       
        });

        // ✅ LORS DE LA MODIFICATION (UPDATE)
        static::updating(function ($model) {
            $table = $model->getTable();
            $schema = \Illuminate\Support\Facades\Schema::class;

            $modificationHostname = request()->getHost() ?? gethostname() ?? 'localhost';
            $modificationUsername = auth()->check() ? auth()->user()->full_login : 'system';

            // Champs d'audit modification (seulement si les colonnes existent)
            if ($schema::hasColumn($table, 'modification_hostname')) {
                $model->modification_hostname = $modificationHostname;
            }
            if ($schema::hasColumn($table, 'modification_username')) {
                $model->modification_username = $modificationUsername;
            }

            // Nouveau checksum (seulement si la colonne existe)
            if ($schema::hasColumn($table, 'checksum')) {
                $model->checksum = $model->generateChecksum();
            }

          
        });

        // ✅ LORS DE LA SUPPRESSION (DELETE)
        static::deleting(function ($model) {
            $table = $model->getTable();
            $schema = \Illuminate\Support\Facades\Schema::class;

            // Champs d'audit suppression (seulement si les colonnes existent)
            $deletionHostname = request()->getHost() ?? gethostname() ?? 'localhost';
            $deletionUsername = auth()->check() ? auth()->user()->full_login : 'system';

            if ($schema::hasColumn($table, 'deletion_hostname')) {
                $model->deletion_hostname = $deletionHostname;
            }
            if ($schema::hasColumn($table, 'deletion_username')) {
                $model->deletion_username = $deletionUsername;
            }

            // Récupérer l'ID de l'utilisateur qui supprime
            $guards = ['client', 'agent', 'api', 'web'];
            $deletedBy = null;

            foreach ($guards as $guard) {
                if (auth($guard)->check()) {
                    $deletedBy = auth($guard)->id();
                    break;
                }
            }

            // Vérifier si la colonne deleted_by existe dans la table
            if ($schema::hasColumn($table, 'deleted_by')) {
                $model->deleted_by = $deletedBy;
            }

            // Log de suppression
            \Log::warning("🗑️ Suppression {$table}", [
                'id' => $model->id,
                'user' => $deletionUsername,
                'hostname' => $deletionHostname
            ]);

            // Sauvegarder les champs d'audit avant le soft delete seulement si des changements ont été faits
            if ($model->isDirty()) {
                $model->saveQuietly();
            }
        });
    }

    /**
     * Générer un checksum pour l'intégrité
     */
    protected function generateChecksum(): string
    {
        $data = collect($this->attributes)
            ->except(['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'])
            ->toJson();

        return hash('sha256', $data);
    }

}
