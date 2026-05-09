<?php

namespace App\Traits;

use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait HasBaseModelFeatures
{
    use LogsActivity;

    /**
     * Initialiser le trait
     */
    public function initializeHasBaseModelFeatures(): void
    {
        // Ajouter les fillables de BaseModel
        $this->fillable = array_merge($this->fillable, [
            'checksum',
            'external_id',
            'source_system',
            'creation_hostname',
            'modification_hostname',
            'deletion_hostname',
            'creation_username',
            'modification_username',
            'deletion_username',
        ]);

        // Ajouter les hidden de BaseModel
        $this->hidden = array_merge($this->hidden, [
            'checksum',
            'external_id',
            'creation_hostname',
            'modification_hostname',
            'deletion_hostname',
            'creation_username',
            'modification_username',
            'deletion_username',
        ]);
    }

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
     * Attributs a logger (a surcharger dans chaque modele)
     */
    protected function getLoggableAttributes(): array
    {
        return ['*'];
    }

    /**
     * Boot du trait pour les evenements du modele
     */
    public static function bootHasBaseModelFeatures(): void
    {
        static::creating(function ($model) {
            $table = $model->getTable();
            $schema = \Illuminate\Support\Facades\Schema::class;

            $creationHostname = request()->getHost() ?? gethostname() ?? 'localhost';
            $creationUsername = auth()->check() ? auth()->user()->full_login : 'system';

            // Champs d'audit creation (seulement si les colonnes existent)
            if ($schema::hasColumn($table, 'creation_hostname')) {
                $model->creation_hostname = $creationHostname;
            }
            if ($schema::hasColumn($table, 'creation_username')) {
                $model->creation_username = $creationUsername;
            }

            // Champs de tracabilite (seulement si les colonnes existent)
            if ($schema::hasColumn($table, 'source_system')) {
                $model->source_system = 'Agree Sikul';
            }
            if ($schema::hasColumn($table, 'external_id')) {
                $model->external_id = $model->external_id ?? null;
            }

            // Checksum pour integrite (seulement si la colonne existe)
            if ($schema::hasColumn($table, 'checksum')) {
                $model->checksum = $model->generateChecksum();
            }

            // Log de creation
       
        });

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

        static::deleting(function ($model) {
            $table = $model->getTable();
            $schema = \Illuminate\Support\Facades\Schema::class;

            $deletionHostname = request()->getHost() ?? gethostname() ?? 'localhost';
            $deletionUsername = auth()->check() ? auth()->user()->full_login : 'system';

            // Champs d'audit suppression (seulement si les colonnes existent)
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
            \Log::warning("Suppression {$table}", [
                'id' => $model->id,
                'user' => $deletionUsername,
                'hostname' => $deletionHostname
            ]);

            // Sauvegarder les champs d'audit avant suppression seulement si des changements ont été faits
            if ($model->isDirty()) {
                $model->saveQuietly();
            }
        });
    }

    /**
     * Generer un checksum pour l'integrite
     */
    protected function generateChecksum(): string
    {
        $data = collect($this->attributes)
            ->except(['id', 'checksum', 'created_at', 'updated_at', 'deleted_at'])
            ->toJson();

        return hash('sha256', $data);
    }
}
