<?php

use Modules\Parametrage\Http\Controllers\DeviseController;
use Modules\Parametrage\Http\Controllers\PaysController;
use Modules\Parametrage\Http\Controllers\ZoneController;
use Modules\Parametrage\Http\Controllers\FournisseurPaiementController;
use Modules\Parametrage\Http\Controllers\BanqueController;
use Modules\Parametrage\Http\Controllers\RegionController;
use Modules\Parametrage\Http\Controllers\DepartementController;
use Modules\Parametrage\Http\Controllers\CommuneController;
use Modules\Parametrage\Http\Controllers\QuartierController;
use Modules\Parametrage\Http\Controllers\CycleEnseignementController;
use Modules\Parametrage\Http\Controllers\TypeEnseignementController;
use Modules\Parametrage\Http\Controllers\TypeEtablissementController;
use Modules\Parametrage\Http\Controllers\SectionController;
use Modules\Parametrage\Http\Controllers\NiveauEtudeController;
use Modules\Parametrage\Http\Controllers\TypeCoursController;
use Modules\Parametrage\Http\Controllers\NatureExamenController;
use Modules\Parametrage\Http\Controllers\TypeExamenController;
use Modules\Parametrage\Http\Controllers\UniteOrganisationnelleController;
use Modules\Parametrage\Http\Controllers\MatiereUniteController;
use Modules\Parametrage\Http\Controllers\GroupeMatiereController;
use Modules\Parametrage\Http\Controllers\TypeApprenantController;
use Modules\Parametrage\Http\Controllers\CategorieApprenantController;
use Modules\Parametrage\Http\Controllers\TitreCiviliteController;
use Modules\Parametrage\Http\Controllers\TypeEvenementAgendaController;
use Modules\Parametrage\Http\Controllers\PeriodesColairesController;
use Modules\Parametrage\Http\Controllers\AnneesScolairesController;
use Modules\Parametrage\Http\Controllers\TypeRessourceController;
use Modules\Parametrage\Http\Controllers\NatureContratController;
use Modules\Parametrage\Http\Controllers\CategorieEnseignantController;
use Modules\Parametrage\Http\Controllers\JourFerieController;
use Modules\Parametrage\Http\Controllers\MoyensPaiementController;
use Modules\Parametrage\Http\Controllers\FonctionController;
use Modules\Parametrage\Http\Controllers\InstitutionController;
use Modules\Parametrage\Http\Controllers\CampusController;
use Modules\Parametrage\Http\Controllers\EcoleController;
use Modules\Parametrage\Http\Controllers\NiveauController;
use Modules\Parametrage\Http\Controllers\ClasseController;
use Modules\Parametrage\Http\Controllers\FichierController;
use Modules\Parametrage\Http\Controllers\DevisePaysController;
use Modules\Parametrage\Http\Controllers\TypeEtablissementSpeController;
use Modules\Parametrage\Http\Controllers\LookupController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::prefix('parametrage')->name('parametrage.')->middleware(["auth:web"])->group(function () {

    // Redirect /parametrage to first feature
    Route::get('/', function() {
        return redirect()->route('parametrage.pays.index');
    });

    // ============================================
    // Routes Gestion des Devises
    // ============================================
    Route::prefix('devises')->name('devises.')->group(function () {
        Route::get('/', [DeviseController::class, 'index'])->name('index');
        Route::get('/create', [DeviseController::class, 'create'])->name('create');
        Route::post('/', [DeviseController::class, 'store'])->name('store');
        Route::get('/{id}', [DeviseController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [DeviseController::class, 'edit'])->name('edit');
        Route::put('/{id}', [DeviseController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [DeviseController::class, 'activate'])->name('statut');
        Route::delete('/{id}', [DeviseController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Routes Gestion des Pays
    // ============================================
    Route::prefix('pays')->name('pays.')->group(function () {
        Route::get('/', [PaysController::class, 'index'])->name('index');
        Route::get('/create', [PaysController::class, 'create'])->name('create');
        Route::post('/', [PaysController::class, 'store'])->name('store');
        Route::get('/{id}', [PaysController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [PaysController::class, 'edit'])->name('edit');
        Route::put('/{id}', [PaysController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [PaysController::class, 'activate'])->name('statut');
        Route::delete('/{id}', [PaysController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Routes Gestion des Fournisseurs de Paiement
    // ============================================
    Route::prefix('fournisseurs-paiements')->name('fournisseurs_paiement.')->group(function () {
        Route::get('/', [FournisseurPaiementController::class, 'index'])->name('index');
        Route::get('/create', [FournisseurPaiementController::class, 'create'])->name('create');
        Route::post('/', [FournisseurPaiementController::class, 'store'])->name('store');
        Route::get('/{id}', [FournisseurPaiementController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [FournisseurPaiementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [FournisseurPaiementController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [FournisseurPaiementController::class, 'activate'])->name('statut');
        Route::delete('/{id}', [FournisseurPaiementController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Routes Gestion des Zones
    // ============================================
    Route::prefix('zones')->name('zones.')->group(function () {
        Route::get('/', [ZoneController::class, 'index'])->name('index');
        Route::get('/create', [ZoneController::class, 'create'])->name('create');
        Route::post('/', [ZoneController::class, 'store'])->name('store');
        Route::get('/{id}', [ZoneController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [ZoneController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ZoneController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [ZoneController::class, 'activate'])->name('statut');
        Route::delete('/{id}', [ZoneController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // Routes Gestion des Banques
    // ============================================
    Route::prefix('banques')->name('banques.')->group(function () {
        Route::get('/', [BanqueController::class, 'index'])->name('index');
        Route::get('/create', [BanqueController::class, 'create'])->name('create');
        Route::post('/', [BanqueController::class, 'store'])->name('store');
        Route::get('/{id}', [BanqueController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [BanqueController::class, 'edit'])->name('edit');
        Route::put('/{id}', [BanqueController::class, 'update'])->name('update');
        Route::put('/{id}/statut', [BanqueController::class, 'activate'])->name('statut');
        Route::put('/{id}/toggle-active', [BanqueController::class, 'toggleActive'])->name('toggle-active');
        Route::delete('/{id}', [BanqueController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // GÉOGRAPHIE
    // ============================================
    Route::prefix('regions')->name('regions.')->group(function () {
        Route::get('/', [RegionController::class, 'index'])->name('index');
        Route::get('/create', [RegionController::class, 'create'])->name('create');
        Route::post('/', [RegionController::class, 'store'])->name('store');
        Route::get('/{region}', [RegionController::class, 'show'])->name('show');
        Route::get('/{region}/edit', [RegionController::class, 'edit'])->name('edit');
        Route::put('/{region}', [RegionController::class, 'update'])->name('update');
        Route::delete('/{region}', [RegionController::class, 'destroy'])->name('destroy');
        Route::put('/{region}/statut', [RegionController::class, 'activate'])->name('statut');
    });

    Route::prefix('departements')->name('departements.')->group(function () {
        Route::get('/', [DepartementController::class, 'index'])->name('index');
        Route::get('/create', [DepartementController::class, 'create'])->name('create');
        Route::post('/', [DepartementController::class, 'store'])->name('store');
        Route::get('/{departement}', [DepartementController::class, 'show'])->name('show');
        Route::get('/{departement}/edit', [DepartementController::class, 'edit'])->name('edit');
        Route::put('/{departement}', [DepartementController::class, 'update'])->name('update');
        Route::delete('/{departement}', [DepartementController::class, 'destroy'])->name('destroy');
        Route::put('/{departement}/statut', [DepartementController::class, 'activate'])->name('statut');
    });

    Route::prefix('communes')->name('communes.')->group(function () {
        Route::get('/', [CommuneController::class, 'index'])->name('index');
        Route::get('/create', [CommuneController::class, 'create'])->name('create');
        Route::post('/', [CommuneController::class, 'store'])->name('store');
        Route::get('/{commune}', [CommuneController::class, 'show'])->name('show');
        Route::get('/{commune}/edit', [CommuneController::class, 'edit'])->name('edit');
        Route::put('/{commune}', [CommuneController::class, 'update'])->name('update');
        Route::delete('/{commune}', [CommuneController::class, 'destroy'])->name('destroy');
        Route::put('/{commune}/statut', [CommuneController::class, 'activate'])->name('statut');
    });

    Route::prefix('quartiers')->name('quartiers.')->group(function () {
        Route::get('/', [QuartierController::class, 'index'])->name('index');
        Route::get('/create', [QuartierController::class, 'create'])->name('create');
        Route::post('/', [QuartierController::class, 'store'])->name('store');
        Route::get('/{quartier}', [QuartierController::class, 'show'])->name('show');
        Route::get('/{quartier}/edit', [QuartierController::class, 'edit'])->name('edit');
        Route::put('/{quartier}', [QuartierController::class, 'update'])->name('update');
        Route::delete('/{quartier}', [QuartierController::class, 'destroy'])->name('destroy');
        Route::put('/{quartier}/statut', [QuartierController::class, 'activate'])->name('statut');
    });

    // ============================================
    // ENSEIGNEMENT
    // ============================================
    Route::prefix('cycles-enseignements')->name('cycles_enseignement.')->group(function () {
        Route::get('/', [CycleEnseignementController::class, 'index'])->name('index');
        Route::get('/create', [CycleEnseignementController::class, 'create'])->name('create');
        Route::post('/', [CycleEnseignementController::class, 'store'])->name('store');
        Route::get('/{cycle_enseignement}', [CycleEnseignementController::class, 'show'])->name('show');
        Route::get('/{cycle_enseignement}/edit', [CycleEnseignementController::class, 'edit'])->name('edit');
        Route::put('/{cycle_enseignement}', [CycleEnseignementController::class, 'update'])->name('update');
        Route::put('/{cycle_enseignement}/statut', [CycleEnseignementController::class, 'activate'])->name('statut');
        Route::delete('/{cycle_enseignement}', [CycleEnseignementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('types-enseignements')->name('types_enseignement.')->group(function () {
        Route::get('/', [TypeEnseignementController::class, 'index'])->name('index');
        Route::get('/create', [TypeEnseignementController::class, 'create'])->name('create');
        Route::post('/', [TypeEnseignementController::class, 'store'])->name('store');
        Route::get('/{type_enseignement}', [TypeEnseignementController::class, 'show'])->name('show');
        Route::get('/{type_enseignement}/edit', [TypeEnseignementController::class, 'edit'])->name('edit');
        Route::put('/{type_enseignement}', [TypeEnseignementController::class, 'update'])->name('update');
        Route::put('/{type_enseignement}/statut', [TypeEnseignementController::class, 'activate'])->name('statut');
        Route::delete('/{type_enseignement}', [TypeEnseignementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('types-etablissements')->name('types_etablissements.')->group(function () {
        Route::get('/', [TypeEtablissementController::class, 'index'])->name('index');
        Route::get('/create', [TypeEtablissementController::class, 'create'])->name('create');
        Route::post('/', [TypeEtablissementController::class, 'store'])->name('store');
        Route::get('/{type_etablissement}', [TypeEtablissementController::class, 'show'])->name('show');
        Route::get('/{type_etablissement}/edit', [TypeEtablissementController::class, 'edit'])->name('edit');
        Route::put('/{type_etablissement}', [TypeEtablissementController::class, 'update'])->name('update');
        Route::put('/{type_etablissement}/statut', [TypeEtablissementController::class, 'activate'])->name('statut');
        Route::delete('/{type_etablissement}', [TypeEtablissementController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('sections')->name('sections.')->group(function () {
        Route::get('/', [SectionController::class, 'index'])->name('index');
        Route::get('/create', [SectionController::class, 'create'])->name('create');
        Route::post('/', [SectionController::class, 'store'])->name('store');
        Route::get('/{section}', [SectionController::class, 'show'])->name('show');
        Route::get('/{section}/edit', [SectionController::class, 'edit'])->name('edit');
        Route::put('/{section}', [SectionController::class, 'update'])->name('update');
        Route::put('/{section}/statut', [SectionController::class, 'activate'])->name('statut');
        Route::delete('/{section}', [SectionController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('niveaux-etudes')->name('niveaux_etude.')->group(function () {
        Route::get('/', [NiveauEtudeController::class, 'index'])->name('index');
        Route::get('/create', [NiveauEtudeController::class, 'create'])->name('create');
        Route::post('/', [NiveauEtudeController::class, 'store'])->name('store');
        Route::get('/{niveau_etude}', [NiveauEtudeController::class, 'show'])->name('show');
        Route::get('/{niveau_etude}/edit', [NiveauEtudeController::class, 'edit'])->name('edit');
        Route::put('/{niveau_etude}', [NiveauEtudeController::class, 'update'])->name('update');
        Route::put('/{niveau_etude}/statut', [NiveauEtudeController::class, 'activate'])->name('statut');
        Route::delete('/{niveau_etude}', [NiveauEtudeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('types-cours')->name('types_cours.')->group(function () {
        Route::get('/', [TypeCoursController::class, 'index'])->name('index');
        Route::get('/create', [TypeCoursController::class, 'create'])->name('create');
        Route::post('/', [TypeCoursController::class, 'store'])->name('store');
        Route::get('/{type_cours}', [TypeCoursController::class, 'show'])->name('show');
        Route::get('/{type_cours}/edit', [TypeCoursController::class, 'edit'])->name('edit');
        Route::put('/{type_cours}', [TypeCoursController::class, 'update'])->name('update');
        Route::put('/{type_cours}/statut', [TypeCoursController::class, 'activate'])->name('statut');
        Route::delete('/{type_cours}', [TypeCoursController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('natures-examens')->name('natures_examens.')->group(function () {
        Route::get('/', [NatureExamenController::class, 'index'])->name('index');
        Route::get('/create', [NatureExamenController::class, 'create'])->name('create');
        Route::post('/', [NatureExamenController::class, 'store'])->name('store');
        Route::get('/{nature_examen}', [NatureExamenController::class, 'show'])->name('show');
        Route::get('/{nature_examen}/edit', [NatureExamenController::class, 'edit'])->name('edit');
        Route::put('/{nature_examen}', [NatureExamenController::class, 'update'])->name('update');
        Route::put('/{nature_examen}/statut', [NatureExamenController::class, 'activate'])->name('statut');
        Route::delete('/{nature_examen}', [NatureExamenController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('types-examens')->name('types_examens.')->group(function () {
        Route::get('/', [TypeExamenController::class, 'index'])->name('index');
        Route::get('/create', [TypeExamenController::class, 'create'])->name('create');
        Route::post('/', [TypeExamenController::class, 'store'])->name('store');
        Route::get('/{type_examen}', [TypeExamenController::class, 'show'])->name('show');
        Route::get('/{type_examen}/edit', [TypeExamenController::class, 'edit'])->name('edit');
        Route::put('/{type_examen}', [TypeExamenController::class, 'update'])->name('update');
        Route::delete('/{type_examen}', [TypeExamenController::class, 'destroy'])->name('destroy');
        Route::put('/{type_examen}/statut', [TypeExamenController::class, 'activate'])->name('statut');
    });

    // ============================================
    // MATIÈRES
    // ============================================
    Route::prefix('matieres-unites')->name('matiere_unites.')->group(function () {
        Route::get('/', [MatiereUniteController::class, 'index'])->name('index');
        Route::get('/create', [MatiereUniteController::class, 'create'])->name('create');
        Route::post('/', [MatiereUniteController::class, 'store'])->name('store');
        Route::get('/{matiere_unite}', [MatiereUniteController::class, 'show'])->name('show');
        Route::get('/{matiere_unite}/edit', [MatiereUniteController::class, 'edit'])->name('edit');
        Route::put('/{matiere_unite}', [MatiereUniteController::class, 'update'])->name('update');
        Route::put('/{matiere_unite}/statut', [MatiereUniteController::class, 'activate'])->name('statut');
        Route::delete('/{matiere_unite}', [MatiereUniteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('groupes-matieres')->name('groupes_matiere.')->group(function () {
        Route::get('/', [GroupeMatiereController::class, 'index'])->name('index');
        Route::get('/create', [GroupeMatiereController::class, 'create'])->name('create');
        Route::post('/', [GroupeMatiereController::class, 'store'])->name('store');
        Route::get('/{groupe_matiere}', [GroupeMatiereController::class, 'show'])->name('show');
        Route::get('/{groupe_matiere}/edit', [GroupeMatiereController::class, 'edit'])->name('edit');
        Route::put('/{groupe_matiere}', [GroupeMatiereController::class, 'update'])->name('update');
        Route::put('/{groupe_matiere}/statut', [GroupeMatiereController::class, 'activate'])->name('statut');
        Route::delete('/{groupe_matiere}', [GroupeMatiereController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // APPRENANTS & ENSEIGNANTS
    // ============================================
    Route::prefix('types-apprenants')->name('types_apprenants.')->group(function () {
        Route::get('/', [TypeApprenantController::class, 'index'])->name('index');
        Route::get('/create', [TypeApprenantController::class, 'create'])->name('create');
        Route::post('/', [TypeApprenantController::class, 'store'])->name('store');
        Route::get('/{type_apprenant}', [TypeApprenantController::class, 'show'])->name('show');
        Route::get('/{type_apprenant}/edit', [TypeApprenantController::class, 'edit'])->name('edit');
        Route::put('/{type_apprenant}', [TypeApprenantController::class, 'update'])->name('update');
        Route::delete('/{type_apprenant}', [TypeApprenantController::class, 'destroy'])->name('destroy');
        Route::put('/{type_apprenant}/statut', [TypeApprenantController::class, 'activate'])->name('statut');
    });

    Route::prefix('categories-apprenants')->name('categories_apprenant.')->group(function () {
        Route::get('/', [CategorieApprenantController::class, 'index'])->name('index');
        Route::get('/create', [CategorieApprenantController::class, 'create'])->name('create');
        Route::post('/', [CategorieApprenantController::class, 'store'])->name('store');
        Route::get('/{categorie_apprenant}', [CategorieApprenantController::class, 'show'])->name('show');
        Route::get('/{categorie_apprenant}/edit', [CategorieApprenantController::class, 'edit'])->name('edit');
        Route::put('/{categorie_apprenant}', [CategorieApprenantController::class, 'update'])->name('update');
        Route::put('/{categorie_apprenant}/statut', [CategorieApprenantController::class, 'activate'])->name('statut');
        Route::delete('/{categorie_apprenant}', [CategorieApprenantController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('categories-enseignants')->name('categories_enseignant.')->group(function () {
        Route::get('/', [CategorieEnseignantController::class, 'index'])->name('index');
        Route::get('/create', [CategorieEnseignantController::class, 'create'])->name('create');
        Route::post('/', [CategorieEnseignantController::class, 'store'])->name('store');
        Route::get('/{categorie_enseignant}', [CategorieEnseignantController::class, 'show'])->name('show');
        Route::get('/{categorie_enseignant}/edit', [CategorieEnseignantController::class, 'edit'])->name('edit');
        Route::put('/{categorie_enseignant}', [CategorieEnseignantController::class, 'update'])->name('update');
        Route::put('/{categorie_enseignant}/statut', [CategorieEnseignantController::class, 'activate'])->name('statut');
        Route::delete('/{categorie_enseignant}', [CategorieEnseignantController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('titres-civilites')->name('titres_civilites.')->group(function () {
        Route::get('/', [TitreCiviliteController::class, 'index'])->name('index');
        Route::get('/create', [TitreCiviliteController::class, 'create'])->name('create');
        Route::post('/', [TitreCiviliteController::class, 'store'])->name('store');
        Route::get('/{titre_civilite}', [TitreCiviliteController::class, 'show'])->name('show');
        Route::get('/{titre_civilite}/edit', [TitreCiviliteController::class, 'edit'])->name('edit');
        Route::put('/{titre_civilite}', [TitreCiviliteController::class, 'update'])->name('update');
        Route::put('/{titre_civilite}/statut', [TitreCiviliteController::class, 'activate'])->name('statut');
        Route::delete('/{titre_civilite}', [TitreCiviliteController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // CALENDRIER
    // ============================================
    Route::prefix('types-evenements')->name('types_evenement.')->group(function () {
        Route::get('/', [TypeEvenementAgendaController::class, 'index'])->name('index');
        Route::get('/create', [TypeEvenementAgendaController::class, 'create'])->name('create');
        Route::post('/', [TypeEvenementAgendaController::class, 'store'])->name('store');
        Route::get('/{type_evenement_agenda}', [TypeEvenementAgendaController::class, 'show'])->name('show');
        Route::get('/{type_evenement_agenda}/edit', [TypeEvenementAgendaController::class, 'edit'])->name('edit');
        Route::put('/{type_evenement_agenda}', [TypeEvenementAgendaController::class, 'update'])->name('update');
        Route::put('/{type_evenement_agenda}/statut', [TypeEvenementAgendaController::class, 'activate'])->name('statut');
        Route::delete('/{type_evenement_agenda}', [TypeEvenementAgendaController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('periodes-colaires')->name('periodes_colaires.')->group(function () {
        Route::get('/', [PeriodesColairesController::class, 'index'])->name('index');
        Route::get('/create', [PeriodesColairesController::class, 'create'])->name('create');
        Route::post('/', [PeriodesColairesController::class, 'store'])->name('store');
        Route::get('/{periode_colaire}', [PeriodesColairesController::class, 'show'])->name('show');
        Route::get('/{periode_colaire}/edit', [PeriodesColairesController::class, 'edit'])->name('edit');
        Route::put('/{periode_colaire}', [PeriodesColairesController::class, 'update'])->name('update');
        Route::delete('/{periode_colaire}', [PeriodesColairesController::class, 'destroy'])->name('destroy');
        Route::put('/{periode_colaire}/statut', [PeriodesColairesController::class, 'activate'])->name('statut');
    });

    Route::prefix('annees-scolaires')->name('annees_scolaires.')->group(function () {
        Route::get('/', [AnneesScolairesController::class, 'index'])->name('index');
        Route::get('/create', [AnneesScolairesController::class, 'create'])->name('create');
        Route::post('/', [AnneesScolairesController::class, 'store'])->name('store');
        Route::get('/{annee_scolaire}', [AnneesScolairesController::class, 'show'])->name('show');
        Route::get('/{annee_scolaire}/edit', [AnneesScolairesController::class, 'edit'])->name('edit');
        Route::put('/{annee_scolaire}', [AnneesScolairesController::class, 'update'])->name('update');
        Route::delete('/{annee_scolaire}', [AnneesScolairesController::class, 'destroy'])->name('destroy');
        Route::put('/{annee_scolaire}/statut', [AnneesScolairesController::class, 'activate'])->name('statut');
        Route::put('/{annee_scolaire}/current', [AnneesScolairesController::class, 'makeCurrent'])->name('current');
    });

    Route::prefix('jours-feries')->name('jours_feries.')->group(function () {
        Route::get('/', [JourFerieController::class, 'index'])->name('index');
        Route::get('/create', [JourFerieController::class, 'create'])->name('create');
        Route::post('/', [JourFerieController::class, 'store'])->name('store');
        Route::get('/{jour_ferie}', [JourFerieController::class, 'show'])->name('show');
        Route::get('/{jour_ferie}/edit', [JourFerieController::class, 'edit'])->name('edit');
        Route::put('/{jour_ferie}', [JourFerieController::class, 'update'])->name('update');
        Route::put('/{jour_ferie}/statut', [JourFerieController::class, 'activate'])->name('statut');
        Route::delete('/{jour_ferie}', [JourFerieController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // RH & ADMINISTRATION
    // ============================================
    Route::prefix('unites-organisationnelles')->name('unite_organisationnelles.')->group(function () {
        Route::get('/', [UniteOrganisationnelleController::class, 'index'])->name('index');
        Route::get('/create', [UniteOrganisationnelleController::class, 'create'])->name('create');
        Route::post('/', [UniteOrganisationnelleController::class, 'store'])->name('store');
        Route::get('/{unite_organisationnelle}', [UniteOrganisationnelleController::class, 'show'])->name('show');
        Route::get('/{unite_organisationnelle}/edit', [UniteOrganisationnelleController::class, 'edit'])->name('edit');
        Route::put('/{unite_organisationnelle}', [UniteOrganisationnelleController::class, 'update'])->name('update');
        Route::delete('/{unite_organisationnelle}', [UniteOrganisationnelleController::class, 'destroy'])->name('destroy');
        Route::put('/{unite_organisationnelle}/statut', [UniteOrganisationnelleController::class, 'activate'])->name('statut');
    });

    Route::prefix('types-ressources')->name('types_ressource.')->group(function () {
        Route::get('/', [TypeRessourceController::class, 'index'])->name('index');
        Route::get('/create', [TypeRessourceController::class, 'create'])->name('create');
        Route::post('/', [TypeRessourceController::class, 'store'])->name('store');
        Route::get('/{type_ressource}', [TypeRessourceController::class, 'show'])->name('show');
        Route::get('/{type_ressource}/edit', [TypeRessourceController::class, 'edit'])->name('edit');
        Route::put('/{type_ressource}', [TypeRessourceController::class, 'update'])->name('update');
        Route::put('/{type_ressource}/statut', [TypeRessourceController::class, 'activate'])->name('statut');
        Route::delete('/{type_ressource}', [TypeRessourceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('natures-contrats')->name('natures_contrat.')->group(function () {
        Route::get('/', [NatureContratController::class, 'index'])->name('index');
        Route::get('/create', [NatureContratController::class, 'create'])->name('create');
        Route::post('/', [NatureContratController::class, 'store'])->name('store');
        Route::get('/{nature_contrat}', [NatureContratController::class, 'show'])->name('show');
        Route::get('/{nature_contrat}/edit', [NatureContratController::class, 'edit'])->name('edit');
        Route::put('/{nature_contrat}', [NatureContratController::class, 'update'])->name('update');
        Route::put('/{nature_contrat}/statut', [NatureContratController::class, 'activate'])->name('statut');
        Route::delete('/{nature_contrat}', [NatureContratController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('fonctions')->name('fonctions.')->group(function () {
        Route::get('/', [FonctionController::class, 'index'])->name('index');
        Route::get('/create', [FonctionController::class, 'create'])->name('create');
        Route::post('/', [FonctionController::class, 'store'])->name('store');
        Route::get('/{fonction}', [FonctionController::class, 'show'])->name('show');
        Route::get('/{fonction}/edit', [FonctionController::class, 'edit'])->name('edit');
        Route::put('/{fonction}', [FonctionController::class, 'update'])->name('update');
        Route::put('/{fonction}/statut', [FonctionController::class, 'activate'])->name('statut');
        Route::delete('/{fonction}', [FonctionController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // FINANCES
    // ============================================
    Route::prefix('moyens-paiements')->name('moyens_paiement.')->group(function () {
        Route::get('/', [MoyensPaiementController::class, 'index'])->name('index');
        Route::get('/create', [MoyensPaiementController::class, 'create'])->name('create');
        Route::post('/', [MoyensPaiementController::class, 'store'])->name('store');
        Route::get('/{moyen_paiement}', [MoyensPaiementController::class, 'show'])->name('show');
        Route::get('/{moyen_paiement}/edit', [MoyensPaiementController::class, 'edit'])->name('edit');
        Route::put('/{moyen_paiement}', [MoyensPaiementController::class, 'update'])->name('update');
        Route::put('/{moyen_paiement}/statut', [MoyensPaiementController::class, 'activate'])->name('statut');
        Route::delete('/{moyen_paiement}', [MoyensPaiementController::class, 'destroy'])->name('destroy');
    });

    // ============================================
    // GESTION SCOLAIRE
    // ============================================

    // Institutions
    Route::prefix('institutions')->name('institution.')->group(function () {
        Route::get('/', [InstitutionController::class, 'index'])->name('index');
        Route::get('/create', [InstitutionController::class, 'create'])->name('create');
        Route::post('/', [InstitutionController::class, 'store'])->name('store');
        Route::get('/{institution}', [InstitutionController::class, 'show'])->name('show');
        Route::get('/{institution}/edit', [InstitutionController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{institution}', [InstitutionController::class, 'update'])->name('update');
        Route::delete('/{institution}', [InstitutionController::class, 'destroy'])->name('destroy');
        Route::post('/{institution}/activate', [InstitutionController::class, 'activate'])->name('activate');
        Route::put('/{institution}/statut', [InstitutionController::class, 'statut'])->name('statut');
    });

    // Campus
    Route::prefix('campuses')->name('campuses.')->group(function () {
        Route::get('/', [CampusController::class, 'index'])->name('index');
        Route::get('/create', [CampusController::class, 'create'])->name('create');
        Route::post('/', [CampusController::class, 'store'])->name('store');
        Route::get('/{campus}', [CampusController::class, 'show'])->name('show');
        Route::get('/{campus}/edit', [CampusController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{campus}', [CampusController::class, 'update'])->name('update');
        Route::delete('/{campus}', [CampusController::class, 'destroy'])->name('destroy');
        Route::post('/{campus}/activate', [CampusController::class, 'activate'])->name('activate');
        Route::put('/{campus}/statut', [CampusController::class, 'statut'])->name('statut');
    });

    // Ecoles
    Route::prefix('ecoles')->name('ecoles.')->group(function () {
        Route::get('/', [EcoleController::class, 'index'])->name('index');
        Route::get('/create', [EcoleController::class, 'create'])->name('create');
        Route::post('/', [EcoleController::class, 'store'])->name('store');
        Route::get('/{ecole}', [EcoleController::class, 'show'])->name('show');
        Route::get('/{ecole}/edit', [EcoleController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{ecole}', [EcoleController::class, 'update'])->name('update');
        Route::delete('/{ecole}', [EcoleController::class, 'destroy'])->name('destroy');
        Route::post('/{ecole}/activate', [EcoleController::class, 'activate'])->name('activate');
        Route::put('/{ecole}/statut', [EcoleController::class, 'statut'])->name('statut');
    });

    // Niveaux
    Route::prefix('niveaux')->name('niveaux.')->group(function () {
        Route::get('/', [NiveauController::class, 'index'])->name('index');
        Route::get('/create', [NiveauController::class, 'create'])->name('create');
        Route::post('/', [NiveauController::class, 'store'])->name('store');
        Route::get('/{niveau}', [NiveauController::class, 'show'])->name('show');
        Route::get('/{niveau}/edit', [NiveauController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{niveau}', [NiveauController::class, 'update'])->name('update');
        Route::delete('/{niveau}', [NiveauController::class, 'destroy'])->name('destroy');
        Route::post('/{niveau}/activate', [NiveauController::class, 'activate'])->name('activate');
        Route::put('/{niveau}/statut', [NiveauController::class, 'statut'])->name('statut');
    });

    // Classes
    Route::prefix('classes')->name('classes.')->group(function () {
        Route::get('/', [ClasseController::class, 'index'])->name('index');
        Route::get('/create', [ClasseController::class, 'create'])->name('create');
        Route::post('/', [ClasseController::class, 'store'])->name('store');
        Route::get('/{classe}', [ClasseController::class, 'show'])->name('show');
        Route::get('/{classe}/edit', [ClasseController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{classe}', [ClasseController::class, 'update'])->name('update');
        Route::delete('/{classe}', [ClasseController::class, 'destroy'])->name('destroy');
        Route::post('/{classe}/activate', [ClasseController::class, 'activate'])->name('activate');
        Route::put('/{classe}/statut', [ClasseController::class, 'statut'])->name('statut');
    });

    // Fichiers
    Route::prefix('fichiers')->name('fichiers.')->group(function () {
        Route::get('/', [FichierController::class, 'index'])->name('index');
        Route::get('/create', [FichierController::class, 'create'])->name('create');
        Route::post('/', [FichierController::class, 'store'])->name('store');
        Route::get('/{fichier}', [FichierController::class, 'show'])->name('show');
        Route::get('/{fichier}/edit', [FichierController::class, 'edit'])->name('edit');
        Route::put('/{fichier}', [FichierController::class, 'update'])->name('update');
        Route::delete('/{fichier}', [FichierController::class, 'destroy'])->name('destroy');
        Route::put('/{fichier}/statut', [FichierController::class, 'activate'])->name('statut');
    });

    // Devises Pays
    Route::prefix('devises-pays')->name('devises_pays.')->group(function () {
        Route::get('/', [DevisePaysController::class, 'index'])->name('index');
        Route::get('/create', [DevisePaysController::class, 'create'])->name('create');
        Route::post('/', [DevisePaysController::class, 'store'])->name('store');
        Route::get('/{devisespay}', [DevisePaysController::class, 'show'])->name('show');
        Route::get('/{devisespay}/edit', [DevisePaysController::class, 'edit'])->name('edit');
        Route::put('/{devisespay}', [DevisePaysController::class, 'update'])->name('update');
        Route::delete('/{devisespay}', [DevisePaysController::class, 'destroy'])->name('destroy');
        Route::put('/{devisespay}/statut', [DevisePaysController::class, 'activate'])->name('statut');
    });

    // Types Établissement Spé
    Route::prefix('types-etablissements-spe')->name('types_etablissement_spe.')->group(function () {
        Route::get('/', [TypeEtablissementSpeController::class, 'index'])->name('index');
        Route::get('/create', [TypeEtablissementSpeController::class, 'create'])->name('create');
        Route::post('/', [TypeEtablissementSpeController::class, 'store'])->name('store');
        Route::get('/{typesEtablissementSpe}', [TypeEtablissementSpeController::class, 'show'])->name('show');
        Route::get('/{typesEtablissementSpe}/edit', [TypeEtablissementSpeController::class, 'edit'])->name('edit');
        Route::put('/{typesEtablissementSpe}', [TypeEtablissementSpeController::class, 'update'])->name('update');
        Route::delete('/{typesEtablissementSpe}', [TypeEtablissementSpeController::class, 'destroy'])->name('destroy');
        Route::put('/{typesEtablissementSpe}/statut', [TypeEtablissementSpeController::class, 'activate'])->name('statut');
    });

    // Redirect parametres-ecole to pays (placeholder for missing feature)
    Route::get('/parametres-ecole', [PaysController::class, 'index'])->name('parametres_ecole.index');

});

// API: Classes details for auto-fill (outside parametrage group, no permission required)
Route::get('/api/classes/{id}', [ClasseController::class, 'apiShow'])->name('classes.api-show');

// API: Cascade géographique pour les formulaires (Institution/Campus/Ecole/Classe)
// Quartier → Commune → Département → Région → Pays
Route::prefix('parametrage/api')->name('parametrage.api.')->group(function () {
    Route::get('/quartier/{id}/hierarchy', [LookupController::class, 'quartierHierarchy'])->name('quartier.hierarchy');
    Route::get('/commune/{id}/hierarchy', [LookupController::class, 'communeHierarchy'])->name('commune.hierarchy');
    Route::get('/departement/{id}/hierarchy', [LookupController::class, 'departementHierarchy'])->name('departement.hierarchy');
    Route::get('/region/{id}/hierarchy', [LookupController::class, 'regionHierarchy'])->name('region.hierarchy');
});
