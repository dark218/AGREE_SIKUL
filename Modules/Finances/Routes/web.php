<?php

use Illuminate\Support\Facades\Route;
use Modules\Finances\Http\Controllers\TypeFraisController;
use Modules\Finances\Http\Controllers\FraisController;
use Modules\Finances\Http\Controllers\PaiementController;
use Modules\Finances\Http\Controllers\EcheancierController;
use Modules\Finances\Http\Controllers\DepenseController;
use Modules\Finances\Http\Controllers\ConfigurationFinanceController;
use Modules\Finances\Http\Controllers\EcolageController;
use Modules\Finances\Http\Controllers\VersementController;
use Modules\Finances\Http\Controllers\SalaireController;
use Modules\Finances\Http\Controllers\FacturationApprenantController;
use Modules\Finances\Http\Controllers\AchatDepenseController;
use Modules\Finances\Http\Controllers\AutreRevenuController;
use Modules\Finances\Http\Controllers\GroupeCompteController;
use Modules\Finances\Http\Controllers\LigneRecetteController;
use Modules\Finances\Http\Controllers\LigneDepenseController;
use Modules\Finances\Http\Controllers\PosteRecetteController;
use Modules\Finances\Http\Controllers\PosteDepenseController;
use Modules\Finances\Http\Controllers\ExamFinanceReportController;
use Modules\Finances\Http\Controllers\RapportFinancierController;

Route::middleware(['auth:web'])->prefix('finances')->name('finances.')->group(function () {

    // Types de Frais
    Route::prefix('types-frais')->name('types-frais.')->group(function () {
        Route::get('/', [TypeFraisController::class, 'index'])->name('index');
        Route::get('/create', [TypeFraisController::class, 'create'])->name('create');
        Route::post('/', [TypeFraisController::class, 'store'])->name('store');
        Route::get('/{type_frais}', [TypeFraisController::class, 'show'])->name('show');
        Route::get('/{type_frais}/edit', [TypeFraisController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{type_frais}', [TypeFraisController::class, 'update'])->name('update');
        Route::delete('/{type_frais}', [TypeFraisController::class, 'destroy'])->name('destroy');
        Route::put('/{type_frais}/statut', [TypeFraisController::class, 'statut'])->name('statut');
    });

    // Frais
    Route::prefix('frais')->name('frais.')->group(function () {
        Route::get('/', [FraisController::class, 'index'])->name('index');
        Route::get('/create', [FraisController::class, 'create'])->name('create');
        Route::post('/', [FraisController::class, 'store'])->name('store');
        Route::get('/{frais}', [FraisController::class, 'show'])->name('show');
        Route::get('/{frais}/edit', [FraisController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{frais}', [FraisController::class, 'update'])->name('update');
        Route::delete('/{frais}', [FraisController::class, 'destroy'])->name('destroy');
        Route::put('/{frais}/statut', [FraisController::class, 'statut'])->name('statut');
    });

    // Paiements
    Route::prefix('paiements')->name('paiements.')->group(function () {
        Route::get('/', [PaiementController::class, 'index'])->name('index');
        Route::get('/create', [PaiementController::class, 'create'])->name('create');
        Route::post('/', [PaiementController::class, 'store'])->name('store');
        Route::get('/{paiement}', [PaiementController::class, 'show'])->name('show');
        Route::get('/{paiement}/edit', [PaiementController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{paiement}', [PaiementController::class, 'update'])->name('update');
        Route::delete('/{paiement}', [PaiementController::class, 'destroy'])->name('destroy');
        Route::put('/{paiement}/statut', [PaiementController::class, 'statut'])->name('statut');
    });

    // Echeanciers
    Route::prefix('echeanciers')->name('echeanciers.')->group(function () {
        Route::get('/', [EcheancierController::class, 'index'])->name('index');
        Route::get('/create', [EcheancierController::class, 'create'])->name('create');
        Route::post('/', [EcheancierController::class, 'store'])->name('store');
        Route::get('/{echeancier}', [EcheancierController::class, 'show'])->name('show');
        Route::get('/{echeancier}/edit', [EcheancierController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{echeancier}', [EcheancierController::class, 'update'])->name('update');
        Route::delete('/{echeancier}', [EcheancierController::class, 'destroy'])->name('destroy');
        Route::put('/{echeancier}/statut', [EcheancierController::class, 'statut'])->name('statut');
    });

    // Depenses
    Route::prefix('depenses')->name('depenses.')->group(function () {
        Route::get('/', [DepenseController::class, 'index'])->name('index');
        Route::get('/create', [DepenseController::class, 'create'])->name('create');
        Route::post('/', [DepenseController::class, 'store'])->name('store');
        Route::get('/{depense}', [DepenseController::class, 'show'])->name('show');
        Route::get('/{depense}/edit', [DepenseController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{depense}', [DepenseController::class, 'update'])->name('update');
        Route::delete('/{depense}', [DepenseController::class, 'destroy'])->name('destroy');
        Route::put('/{depense}/statut', [DepenseController::class, 'statut'])->name('statut');
    });

    // Configurations Finances
    Route::prefix('configurations-finances')->name('configurations-finances.')->group(function () {
        Route::get('/', [ConfigurationFinanceController::class, 'index'])->name('index');
        Route::get('/create', [ConfigurationFinanceController::class, 'create'])->name('create');
        Route::post('/', [ConfigurationFinanceController::class, 'store'])->name('store');
        Route::get('/{configuration}', [ConfigurationFinanceController::class, 'show'])->name('show');
        Route::get('/{configuration}/edit', [ConfigurationFinanceController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{configuration}', [ConfigurationFinanceController::class, 'update'])->name('update');
        Route::delete('/{configuration}', [ConfigurationFinanceController::class, 'destroy'])->name('destroy');
        Route::put('/{configuration}/statut', [ConfigurationFinanceController::class, 'statut'])->name('statut');
    });


    // Ecolages
    Route::prefix('ecolages')->name('ecolage.')->group(function () {
        Route::get('/', [EcolageController::class, 'index'])->name('index');
        Route::get('/create', [EcolageController::class, 'create'])->name('create');
        Route::post('/', [EcolageController::class, 'store'])->name('store');
        Route::get('/{ecolage}', [EcolageController::class, 'show'])->name('show');
        Route::get('/{ecolage}/edit', [EcolageController::class, 'edit'])->name('edit');
        Route::match(['put', 'post'], '/{ecolage}', [EcolageController::class, 'update'])->name('update');
        Route::delete('/{ecolage}', [EcolageController::class, 'destroy'])->name('destroy');
        Route::put('/{ecolage}/statut', [EcolageController::class, 'statut'])->name('statut');
    });

    // Versements
    Route::prefix('versements')->name('versements.')->group(function () {
        Route::get('/', [VersementController::class, 'index'])->name('index');
        Route::get('/create', [VersementController::class, 'create'])->name('create');
        Route::post('/', [VersementController::class, 'store'])->name('store');
        Route::get('/{versement}', [VersementController::class, 'show'])->name('show');
        Route::get('/{versement}/edit', [VersementController::class, 'edit'])->name('edit');
        Route::put('/{versement}', [VersementController::class, 'update'])->name('update');
        Route::delete('/{versement}', [VersementController::class, 'destroy'])->name('destroy');
        Route::put('/{versement}/statut', [VersementController::class, 'statut'])->name('statut');
    });

    // Salaires
    Route::prefix('salaires')->name('salaires.')->group(function () {
        Route::get('/', [SalaireController::class, 'index'])->name('index');
        Route::get('/create', [SalaireController::class, 'create'])->name('create');
        Route::post('/', [SalaireController::class, 'store'])->name('store');
        Route::get('/{salaire}', [SalaireController::class, 'show'])->name('show');
        Route::get('/{salaire}/edit', [SalaireController::class, 'edit'])->name('edit');
        Route::put('/{salaire}', [SalaireController::class, 'update'])->name('update');
        Route::delete('/{salaire}', [SalaireController::class, 'destroy'])->name('destroy');
        Route::put('/{salaire}/statut', [SalaireController::class, 'statut'])->name('statut');
    });

    // Facturations Apprenants
    Route::prefix('facturations-apprenants')->name('facturation-apprenants.')->group(function () {
        Route::get('/', [FacturationApprenantController::class, 'index'])->name('index');
        Route::get('/create', [FacturationApprenantController::class, 'create'])->name('create');
        Route::post('/', [FacturationApprenantController::class, 'store'])->name('store');
        Route::get('/{facturation_apprenant}', [FacturationApprenantController::class, 'show'])->name('show');
        Route::get('/{facturation_apprenant}/edit', [FacturationApprenantController::class, 'edit'])->name('edit');
        Route::put('/{facturation_apprenant}', [FacturationApprenantController::class, 'update'])->name('update');
        Route::delete('/{facturation_apprenant}', [FacturationApprenantController::class, 'destroy'])->name('destroy');
        Route::put('/{facturation_apprenant}/statut', [FacturationApprenantController::class, 'statut'])->name('statut');
    });

    // Achats et Dépenses
    Route::prefix('achats-depenses')->name('achats-depenses.')->group(function () {
        Route::get('/', [AchatDepenseController::class, 'index'])->name('index');
        Route::get('/create', [AchatDepenseController::class, 'create'])->name('create');
        Route::post('/', [AchatDepenseController::class, 'store'])->name('store');
        Route::get('/{achat_depense}', [AchatDepenseController::class, 'show'])->name('show');
        Route::get('/{achat_depense}/edit', [AchatDepenseController::class, 'edit'])->name('edit');
        Route::put('/{achat_depense}', [AchatDepenseController::class, 'update'])->name('update');
        Route::delete('/{achat_depense}', [AchatDepenseController::class, 'destroy'])->name('destroy');
        Route::put('/{achat_depense}/statut', [AchatDepenseController::class, 'statut'])->name('statut');
    });

    // Autres Revenus
    Route::prefix('autres-revenus')->name('autres-revenus.')->group(function () {
        Route::get('/', [AutreRevenuController::class, 'index'])->name('index');
        Route::get('/create', [AutreRevenuController::class, 'create'])->name('create');
        Route::post('/', [AutreRevenuController::class, 'store'])->name('store');
        Route::get('/{autreRevenu}', [AutreRevenuController::class, 'show'])->name('show');
        Route::get('/{autreRevenu}/edit', [AutreRevenuController::class, 'edit'])->name('edit');
        Route::put('/{autreRevenu}', [AutreRevenuController::class, 'update'])->name('update');
        Route::delete('/{autreRevenu}', [AutreRevenuController::class, 'destroy'])->name('destroy');
        Route::put('/{autreRevenu}/statut', [AutreRevenuController::class, 'statut'])->name('statut');
    });

    // Groupes de Comptes
    Route::prefix('groupes-comptes')->name('groupes-comptes.')->group(function () {
        Route::get('/', [GroupeCompteController::class, 'index'])->name('index');
        Route::get('/create', [GroupeCompteController::class, 'create'])->name('create');
        Route::post('/', [GroupeCompteController::class, 'store'])->name('store');
        Route::get('/{groupeCompte}', [GroupeCompteController::class, 'show'])->name('show');
        Route::get('/{groupeCompte}/edit', [GroupeCompteController::class, 'edit'])->name('edit');
        Route::put('/{groupeCompte}', [GroupeCompteController::class, 'update'])->name('update');
        Route::delete('/{groupeCompte}', [GroupeCompteController::class, 'destroy'])->name('destroy');
        Route::put('/{groupeCompte}/statut', [GroupeCompteController::class, 'statut'])->name('statut');
    });

    // Plan comptable OHADA
    Route::prefix('plan-comptes')->name('plan-comptes.')->group(function () {
        Route::get('/', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'index'])->name('index');
        Route::get('/create', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'create'])->name('create');
        Route::post('/', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'store'])->name('store');
        Route::get('/{planCompte}/edit', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'edit'])->name('edit');
        Route::put('/{planCompte}', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'update'])->name('update');
        Route::delete('/{planCompte}', [\Modules\Finances\Http\Controllers\PlanCompteController::class, 'destroy'])->name('destroy');
    });

    // Lignes de Recettes
    Route::prefix('lignes-recettes')->name('lignes-recettes.')->group(function () {
        Route::get('/', [LigneRecetteController::class, 'index'])->name('index');
        Route::get('/create', [LigneRecetteController::class, 'create'])->name('create');
        Route::post('/', [LigneRecetteController::class, 'store'])->name('store');
        Route::get('/{ligneRecette}', [LigneRecetteController::class, 'show'])->name('show');
        Route::get('/{ligneRecette}/edit', [LigneRecetteController::class, 'edit'])->name('edit');
        Route::put('/{ligneRecette}', [LigneRecetteController::class, 'update'])->name('update');
        Route::delete('/{ligneRecette}', [LigneRecetteController::class, 'destroy'])->name('destroy');
        Route::put('/{ligneRecette}/statut', [LigneRecetteController::class, 'statut'])->name('statut');
    });

    // Lignes de Dépenses
    Route::prefix('lignes-depenses')->name('lignes-depenses.')->group(function () {
        Route::get('/', [LigneDepenseController::class, 'index'])->name('index');
        Route::get('/create', [LigneDepenseController::class, 'create'])->name('create');
        Route::post('/', [LigneDepenseController::class, 'store'])->name('store');
        Route::get('/{ligneDepense}', [LigneDepenseController::class, 'show'])->name('show');
        Route::get('/{ligneDepense}/edit', [LigneDepenseController::class, 'edit'])->name('edit');
        Route::put('/{ligneDepense}', [LigneDepenseController::class, 'update'])->name('update');
        Route::delete('/{ligneDepense}', [LigneDepenseController::class, 'destroy'])->name('destroy');
        Route::put('/{ligneDepense}/statut', [LigneDepenseController::class, 'statut'])->name('statut');
    });

    // Postes de Recettes
    Route::prefix('postes-recettes')->name('postes-recettes.')->group(function () {
        Route::get('/', [PosteRecetteController::class, 'index'])->name('index');
        Route::get('/create', [PosteRecetteController::class, 'create'])->name('create');
        Route::post('/', [PosteRecetteController::class, 'store'])->name('store');
        Route::get('/{posteRecette}', [PosteRecetteController::class, 'show'])->name('show');
        Route::get('/{posteRecette}/edit', [PosteRecetteController::class, 'edit'])->name('edit');
        Route::put('/{posteRecette}', [PosteRecetteController::class, 'update'])->name('update');
        Route::delete('/{posteRecette}', [PosteRecetteController::class, 'destroy'])->name('destroy');
        Route::put('/{posteRecette}/statut', [PosteRecetteController::class, 'statut'])->name('statut');
    });

    // Postes de Dépenses
    Route::prefix('postes-depenses')->name('postes-depenses.')->group(function () {
        Route::get('/', [PosteDepenseController::class, 'index'])->name('index');
        Route::get('/create', [PosteDepenseController::class, 'create'])->name('create');
        Route::post('/', [PosteDepenseController::class, 'store'])->name('store');
        Route::get('/{posteDepense}', [PosteDepenseController::class, 'show'])->name('show');
        Route::get('/{posteDepense}/edit', [PosteDepenseController::class, 'edit'])->name('edit');
        Route::put('/{posteDepense}', [PosteDepenseController::class, 'update'])->name('update');
        Route::delete('/{posteDepense}', [PosteDepenseController::class, 'destroy'])->name('destroy');
        Route::put('/{posteDepense}/statut', [PosteDepenseController::class, 'statut'])->name('statut');
    });

    // Exam Finance Reports
    Route::prefix('exam-finance-reports')->name('exam-finance-report.')->group(function () {
        Route::get('/', [ExamFinanceReportController::class, 'index'])->name('index');
        Route::get('/dashboard', [ExamFinanceReportController::class, 'dashboard'])->name('dashboard');
        Route::post('/generate', [ExamFinanceReportController::class, 'generate'])->name('generate');
        Route::get('/compare-months', [ExamFinanceReportController::class, 'compareMonths'])->name('compare');
        Route::get('/export-csv', [ExamFinanceReportController::class, 'exportCsv'])->name('export-csv');
        Route::get('/{report}', [ExamFinanceReportController::class, 'show'])->name('show');
        Route::get('/{report}/export-pdf', [ExamFinanceReportController::class, 'exportPdf'])->name('export-pdf');
    });

    // Rapports Financiers Consolidés (READ-ONLY)
    Route::prefix('rapports-financiers')->name('rapports-financiers.')->group(function () {
        Route::get('/', [RapportFinancierController::class, 'index'])->name('index');
    });

});