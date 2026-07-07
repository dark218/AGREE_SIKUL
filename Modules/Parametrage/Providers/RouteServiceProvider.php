<?php

namespace Modules\Parametrage\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The module namespace to assume when generating URLs to actions.
     *
     * @var string
     */
    protected $moduleNamespace = 'Modules\Parametrage\Http\Controllers';

    /**
     * Called before routes are registered.
     *
     * Register any model bindings or pattern based filters.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Explicit route model bindings for models with snake_case parameter names
        Route::model('cycle_enseignement', \Modules\Parametrage\Entities\CycleEnseignement::class);
        Route::model('type_enseignement', \Modules\Parametrage\Entities\TypeEnseignement::class);
        Route::model('type_etablissement', \Modules\Parametrage\Entities\TypeEtablissement::class);
        Route::model('niveau_etude', \Modules\Parametrage\Entities\NiveauEtude::class);
        Route::model('type_cours', \Modules\Parametrage\Entities\TypeCours::class);
        Route::model('nature_examen', \Modules\Parametrage\Entities\NatureExamen::class);
        Route::model('type_examen', \Modules\Parametrage\Entities\TypeExamen::class);
        Route::model('matiere_unite', \Modules\Parametrage\Entities\MatiereUnite::class);
        Route::model('groupe_matiere', \Modules\Parametrage\Entities\GroupeMatiere::class);
        Route::model('type_apprenant', \Modules\Parametrage\Entities\TypeApprenant::class);
        // (route model binding 'categorie_apprenant' retiré — feature droppée)
        Route::model('titre_civilite', \Modules\Parametrage\Entities\TitreCivilite::class);
        Route::model('type_evenement_agenda', \Modules\Parametrage\Entities\TypeEvenementAgenda::class);
        Route::model('periode_colaire', \Modules\Parametrage\Entities\PeriodeColaire::class);
        Route::model('annee_scolaire', \Modules\Parametrage\Entities\AnneeScolaire::class);
        Route::model('jour_ferie', \Modules\Parametrage\Entities\JourFerie::class);
        Route::model('unite_organisationnelle', \Modules\Parametrage\Entities\UniteOrganisationnelle::class);
        Route::model('type_ressource', \Modules\Parametrage\Entities\TypeRessource::class);
        Route::model('nature_contrat', \Modules\Parametrage\Entities\NatureContrat::class);
        Route::model('categorie_enseignant', \Modules\Parametrage\Entities\CategorieEnseignant::class);
        Route::model('fonction', \Modules\Parametrage\Entities\Fonction::class);
        Route::model('mode_paiement', \Modules\Parametrage\Entities\ModePaiement::class);
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Parametrage', '/Routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->moduleNamespace)
            ->group(module_path('Parametrage', '/Routes/api.php'));
    }
}
