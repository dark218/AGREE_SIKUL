<?php

use Modules\Academique\Http\Controllers\ApprenantController;
use Modules\Academique\Http\Controllers\EnseignantController;
use Modules\Academique\Http\Controllers\MatiereController;
use Modules\Academique\Http\Controllers\CoursController;
use Modules\Academique\Http\Controllers\SeanceController;
use Modules\Academique\Http\Controllers\EmploiTempsController;
use Modules\Academique\Http\Controllers\EvaluationController;
use Modules\Academique\Http\Controllers\NoteController;
use Modules\Academique\Http\Controllers\BulletinController;
use Modules\Academique\Http\Controllers\InscriptionController;
use Modules\Academique\Http\Controllers\DossierApprenantController;
use Modules\Academique\Http\Controllers\AbsenceApprenantController;
use Modules\Academique\Http\Controllers\AbsenceEnseignantController;
use Modules\Academique\Http\Controllers\AffectationEnseignantController;
use Modules\Academique\Http\Controllers\PresencesController;
use Modules\Academique\Http\Controllers\JustificatifAbsenceController;
use Modules\Academique\Http\Controllers\DevoirController;
use Modules\Academique\Http\Controllers\RenduDevoirController;
use Modules\Academique\Http\Controllers\MoyenneMatiereController;
use Modules\Academique\Http\Controllers\PersonnelAdministratifController;
use Modules\Academique\Http\Controllers\ListeManuelsController;
use Modules\Academique\Http\Controllers\ManuelController;
use Modules\Academique\Http\Controllers\PassageController;
use Modules\Academique\Http\Controllers\ClassesApprenantsController;
use Modules\Academique\Http\Controllers\BibliothequeController;
use Modules\Academique\Http\Controllers\PlanificationExamenController;
use Modules\Academique\Http\Controllers\ExamenEnLigneController;
use Modules\Academique\Http\Controllers\QuestionExamenController;
use Modules\Academique\Http\Controllers\CompositionExamenController;
use Modules\Academique\Http\Controllers\DocumentScolaireController;
use Modules\Academique\Http\Controllers\PdfExportController;
use Modules\Academique\Http\Controllers\ResultatExamenController;
use Modules\Academique\Http\Controllers\ExamFinanceController;

Route::prefix('academique')->name('academique.')->middleware(["auth:web"])->group(function () {

    // ===== PDF EXPORTS =====
    Route::prefix('pdf')->name('pdf.')->group(function () {
        Route::get('/liste-apprenants', [PdfExportController::class, 'listeApprenants'])->name('liste-apprenants');
        Route::get('/apprenant/{apprenant}/fiche', [PdfExportController::class, 'ficheApprenant'])->name('fiche-apprenant');
        Route::get('/apprenant/{apprenant}/attestation', [PdfExportController::class, 'attestationInscription'])->name('attestation-inscription');
        Route::get('/releve-notes-classe', [PdfExportController::class, 'releveNotesClasse'])->name('releve-notes-classe');
        Route::get('/feuille-presence', [PdfExportController::class, 'feuillePresence'])->name('feuille-presence');
        Route::get('/absences-apprenants', [PdfExportController::class, 'rapportAbsencesApprenants'])->name('absences-apprenants');
        Route::get('/absences-enseignants', [PdfExportController::class, 'rapportAbsencesEnseignants'])->name('absences-enseignants');
        Route::get('/moyennes-matieres', [PdfExportController::class, 'releveMoyennesMatieres'])->name('moyennes-matieres');
    });

    // Apprenants
    Route::prefix('apprenants')->name('apprenants.')->group(function () {
        Route::get('/', [ApprenantController::class, 'index'])->name('index');
        Route::get('/create', [ApprenantController::class, 'create'])->name('create');
        Route::post('/', [ApprenantController::class, 'store'])->name('store');
        // API endpoints
        Route::get('/api/calculate-moyennes', [ApprenantController::class, 'calculateMoyennes'])->name('api_calculate_moyennes');
        Route::get('/{apprenant}', [ApprenantController::class, 'show'])->name('show');
        Route::get('/{apprenant}/edit', [ApprenantController::class, 'edit'])->name('edit');
        Route::put('/{apprenant}', [ApprenantController::class, 'update'])->name('update');
        Route::put('/{apprenant}/statut', [ApprenantController::class, 'statut'])->name('statut');
        Route::delete('/{apprenant}', [ApprenantController::class, 'destroy'])->name('destroy');
        Route::get('/{apprenant}/api-show', [ApprenantController::class, 'apiShow'])->name('api_show');
    });

    // Enseignants
    Route::prefix('enseignants')->name('enseignants.')->group(function () {
        Route::get('/', [EnseignantController::class, 'index'])->name('index');
        Route::get('/create', [EnseignantController::class, 'create'])->name('create');
        Route::post('/', [EnseignantController::class, 'store'])->name('store');
        Route::get('/{enseignant}', [EnseignantController::class, 'show'])->name('show');
        Route::get('/{enseignant}/edit', [EnseignantController::class, 'edit'])->name('edit');
        Route::put('/{enseignant}', [EnseignantController::class, 'update'])->name('update');
        Route::put('/{enseignant}/statut', [EnseignantController::class, 'activate'])->name('statut');
        Route::delete('/{enseignant}', [EnseignantController::class, 'destroy'])->name('destroy');
    });

    // Matieres
    Route::prefix('matieres')->name('matieres.')->group(function () {
        Route::get('/', [MatiereController::class, 'index'])->name('index');
        Route::get('/create', [MatiereController::class, 'create'])->name('create');
        Route::post('/', [MatiereController::class, 'store'])->name('store');
        // API endpoint for auto-fill matiere details (coefficient) - must come before /{matiere}
        Route::get('/{id}/api-show', [MatiereController::class, 'apiShow'])->name('api_show');
        Route::get('/{matiere}', [MatiereController::class, 'show'])->name('show');
        Route::get('/{matiere}/edit', [MatiereController::class, 'edit'])->name('edit');
        Route::put('/{matiere}', [MatiereController::class, 'update'])->name('update');
        Route::put('/{matiere}/statut', [MatiereController::class, 'statut'])->name('statut');
        Route::delete('/{matiere}', [MatiereController::class, 'destroy'])->name('destroy');
    });

    // Cours
    Route::prefix('cours')->name('cours.')->group(function () {
        Route::get('/', [CoursController::class, 'index'])->name('index');
        Route::get('/create', [CoursController::class, 'create'])->name('create');
        Route::post('/', [CoursController::class, 'store'])->name('store');
        Route::get('/{cours}', [CoursController::class, 'show'])->name('show');
        Route::get('/{cours}/edit', [CoursController::class, 'edit'])->name('edit');
        Route::put('/{cours}', [CoursController::class, 'update'])->name('update');
        Route::put('/{cours}/statut', [CoursController::class, 'activate'])->name('statut');
        Route::delete('/{cours}', [CoursController::class, 'destroy'])->name('destroy');
    });

    // Seances
    Route::prefix('seances')->name('seances.')->group(function () {
        Route::get('/', [SeanceController::class, 'index'])->name('index');
        Route::get('/create', [SeanceController::class, 'create'])->name('create');
        Route::post('/', [SeanceController::class, 'store'])->name('store');
        Route::get('/{seance}', [SeanceController::class, 'show'])->name('show');
        Route::get('/{seance}/edit', [SeanceController::class, 'edit'])->name('edit');
        Route::put('/{seance}', [SeanceController::class, 'update'])->name('update');
        Route::put('/{seance}/statut', [SeanceController::class, 'statut'])->name('statut');
        Route::delete('/{seance}', [SeanceController::class, 'destroy'])->name('destroy');
    });

    // Emplois du Temps
    Route::prefix('emplois-du-temps')->name('emplois_du_temps.')->group(function () {
        Route::get('/', [EmploiTempsController::class, 'index'])->name('index');
        Route::get('/create', [EmploiTempsController::class, 'create'])->name('create');
        Route::post('/', [EmploiTempsController::class, 'store'])->name('store');
        Route::post('/semaine', [EmploiTempsController::class, 'storeWeek'])->name('store_week');
        Route::get('/{emploi_temps}', [EmploiTempsController::class, 'show'])->name('show');
        Route::get('/{emploi_temps}/pdf', [EmploiTempsController::class, 'exportPdf'])->name('pdf');
        Route::get('/{emploi_temps}/edit', [EmploiTempsController::class, 'edit'])->name('edit');
        Route::get('/{emploi_temps}/edit-semaine', [EmploiTempsController::class, 'editWeek'])->name('edit_week');
        Route::put('/{emploi_temps}', [EmploiTempsController::class, 'update'])->name('update');
        Route::put('/{emploi_temps}/semaine', [EmploiTempsController::class, 'updateWeek'])->name('update_week');
        Route::put('/{emploi_temps}/statut', [EmploiTempsController::class, 'activate'])->name('statut');
        Route::delete('/{emploi_temps}', [EmploiTempsController::class, 'destroy'])->name('destroy');
    });

    // Evaluations
    Route::prefix('evaluations')->name('evaluations.')->group(function () {
        Route::get('/', [EvaluationController::class, 'index'])->name('index');
        Route::get('/create', [EvaluationController::class, 'create'])->name('create');
        Route::post('/', [EvaluationController::class, 'store'])->name('store');
        // API endpoint for auto-fill evaluation details (date) - must come before /{evaluation}
        Route::get('/{id}/api-show', [EvaluationController::class, 'apiShow'])->name('api_show');
        Route::get('/{evaluation}', [EvaluationController::class, 'show'])->name('show');
        Route::get('/{evaluation}/edit', [EvaluationController::class, 'edit'])->name('edit');
        Route::put('/{evaluation}', [EvaluationController::class, 'update'])->name('update');
        Route::put('/{evaluation}/statut', [EvaluationController::class, 'statut'])->name('statut');
        Route::delete('/{evaluation}', [EvaluationController::class, 'destroy'])->name('destroy');
    });

    // PHASE 3: Notes, Bulletins, Inscriptions, Dossiers

    // Notes
    Route::prefix('notes')->name('notes.')->group(function () {
        Route::get('/', [NoteController::class, 'index'])->name('index');
        Route::get('/create', [NoteController::class, 'create'])->name('create');
        Route::post('/', [NoteController::class, 'store'])->name('store');
        Route::get('/{note}', [NoteController::class, 'show'])->name('show');
        Route::get('/{note}/edit', [NoteController::class, 'edit'])->name('edit');
        Route::put('/{note}', [NoteController::class, 'update'])->name('update');
        Route::put('/{note}/statut', [NoteController::class, 'statut'])->name('statut');
        Route::delete('/{note}', [NoteController::class, 'destroy'])->name('destroy');
        Route::get('/releve-classe/pdf', [DocumentScolaireController::class, 'releveClasse'])->name('releve-classe-pdf');
    });

    // Bulletins
    Route::prefix('bulletins')->name('bulletins.')->group(function () {
        Route::get('/', [BulletinController::class, 'index'])->name('index');
        Route::get('/create', [BulletinController::class, 'create'])->name('create');
        Route::post('/', [BulletinController::class, 'store'])->name('store');
        // API endpoint to calculate bulletin data automatically
        Route::get('/api/calculate-data', [BulletinController::class, 'calculateBulletinData'])->name('api_calculate');
        Route::get('/{bulletin}', [BulletinController::class, 'show'])->name('show');
        // PDF auto-détection ou sélection manuelle (?template=xxx)
        Route::get('/{bulletin}/pdf', [BulletinController::class, 'exportPdf'])->name('pdf');
        // Routes legacy (anciennes) — redirigent vers exportPdf avec le bon template
        Route::get('/{bulletin}/pdf-seq', [BulletinController::class, 'exportPdf'])->name('pdf-seq');
        Route::get('/{bulletin}/pdf-anglophone', [BulletinController::class, 'exportPdf'])->name('pdf-anglophone');
        Route::get('/{bulletin}/releve-annuel', [BulletinController::class, 'exportPdf'])->name('releve-annuel');
        Route::get('/{bulletin}/releve-sessions', [BulletinController::class, 'exportPdf'])->name('releve-sessions');
        Route::get('/{bulletin}/releve-semestre', [BulletinController::class, 'exportPdf'])->name('releve-semestre');
        Route::get('/{bulletin}/releve-master', [BulletinController::class, 'exportPdf'])->name('releve-master');
        Route::get('/{bulletin}/releve-cc-galop', [BulletinController::class, 'exportPdf'])->name('releve-cc-galop');
        Route::get('/{bulletin}/edit', [BulletinController::class, 'edit'])->name('edit');
        Route::put('/{bulletin}', [BulletinController::class, 'update'])->name('update');
        Route::put('/{bulletin}/statut', [BulletinController::class, 'activate'])->name('statut');
        Route::delete('/{bulletin}', [BulletinController::class, 'destroy'])->name('destroy');
    });

    // Inscriptions
    Route::prefix('inscriptions')->name('inscriptions.')->group(function () {
        Route::get('/', [InscriptionController::class, 'index'])->name('index');
        Route::get('/create', [InscriptionController::class, 'create'])->name('create');
        Route::post('/', [InscriptionController::class, 'store'])->name('store');
        Route::get('/{inscription}', [InscriptionController::class, 'show'])->name('show');
        Route::get('/{inscription}/edit', [InscriptionController::class, 'edit'])->name('edit');
        Route::put('/{inscription}', [InscriptionController::class, 'update'])->name('update');
        Route::put('/{inscription}/statut', [InscriptionController::class, 'statut'])->name('statut');
        Route::delete('/{inscription}', [InscriptionController::class, 'destroy'])->name('destroy');
    });

    // Dossiers Apprenants
    Route::prefix('dossiers-apprenants')->name('dossiers_apprenants.')->group(function () {
        Route::get('/', [DossierApprenantController::class, 'index'])->name('index');
        Route::get('/create', [DossierApprenantController::class, 'create'])->name('create');
        Route::post('/', [DossierApprenantController::class, 'store'])->name('store');
        Route::get('/{dossier_apprenant}', [DossierApprenantController::class, 'show'])->name('show');
        Route::get('/{dossier_apprenant}/edit', [DossierApprenantController::class, 'edit'])->name('edit');
        Route::put('/{dossier_apprenant}', [DossierApprenantController::class, 'update'])->name('update');
        Route::delete('/{dossier_apprenant}', [DossierApprenantController::class, 'destroy'])->name('destroy');
    });

    // PHASE 4: Absences, Presences, Justifications

    // Absences Apprenants
    Route::prefix('absences-apprenants')->name('absences_apprenants.')->group(function () {
        Route::get('/', [AbsenceApprenantController::class, 'index'])->name('index');
        Route::get('/create', [AbsenceApprenantController::class, 'create'])->name('create');
        Route::post('/', [AbsenceApprenantController::class, 'store'])->name('store');
        Route::get('/{absence_apprenant}', [AbsenceApprenantController::class, 'show'])->name('show');
        Route::get('/{absence_apprenant}/edit', [AbsenceApprenantController::class, 'edit'])->name('edit');
        Route::put('/{absence_apprenant}', [AbsenceApprenantController::class, 'update'])->name('update');
        Route::put('/{absence_apprenant}/statut', [AbsenceApprenantController::class, 'activate'])->name('statut');
        Route::delete('/{absence_apprenant}', [AbsenceApprenantController::class, 'destroy'])->name('destroy');
    });

    // API endpoint for schedule slots - Apprenants
    Route::get('/api/absence-apprenants/schedule-slots', [AbsenceApprenantController::class, 'getScheduleSlots']);

    // Absences Enseignants
    Route::prefix('absences-enseignants')->name('absences_enseignants.')->group(function () {
        Route::get('/', [AbsenceEnseignantController::class, 'index'])->name('index');
        Route::get('/create', [AbsenceEnseignantController::class, 'create'])->name('create');
        Route::post('/', [AbsenceEnseignantController::class, 'store'])->name('store');
        Route::get('/{absence_enseignant}', [AbsenceEnseignantController::class, 'show'])->name('show');
        Route::get('/{absence_enseignant}/edit', [AbsenceEnseignantController::class, 'edit'])->name('edit');
        Route::put('/{absence_enseignant}', [AbsenceEnseignantController::class, 'update'])->name('update');
        Route::put('/{absence_enseignant}/statut', [AbsenceEnseignantController::class, 'activate'])->name('statut');
        Route::delete('/{absence_enseignant}', [AbsenceEnseignantController::class, 'destroy'])->name('destroy');
        // API endpoint for fetching schedule slots
        Route::get('/api/schedule-slots', [AbsenceEnseignantController::class, 'getScheduleSlots'])->name('schedule_slots');
    });

    // Affectations Enseignants
    Route::prefix('affectations-enseignants')->name('affectations_enseignants.')->group(function () {
        Route::get('/', [AffectationEnseignantController::class, 'index'])->name('index');
        Route::get('/create', [AffectationEnseignantController::class, 'create'])->name('create');
        Route::post('/', [AffectationEnseignantController::class, 'store'])->name('store');
        Route::get('/{affectationEnseignant}', [AffectationEnseignantController::class, 'show'])->name('show');
        Route::get('/{affectationEnseignant}/edit', [AffectationEnseignantController::class, 'edit'])->name('edit');
        Route::put('/{affectationEnseignant}', [AffectationEnseignantController::class, 'update'])->name('update');
        Route::put('/{affectationEnseignant}/statut', [AffectationEnseignantController::class, 'statut'])->name('statut');
        Route::delete('/{affectationEnseignant}', [AffectationEnseignantController::class, 'destroy'])->name('destroy');
    });

    // Presences
    Route::prefix('presences')->name('presences.')->group(function () {
        Route::get('/', [PresencesController::class, 'index'])->name('index');
        Route::get('/create', [PresencesController::class, 'create'])->name('create');
        Route::post('/', [PresencesController::class, 'store'])->name('store');
        Route::get('/{presence}', [PresencesController::class, 'show'])->name('show');
        Route::get('/{presence}/edit', [PresencesController::class, 'edit'])->name('edit');
        Route::put('/{presence}', [PresencesController::class, 'update'])->name('update');
        Route::put('/{presence}/statut', [PresencesController::class, 'statut'])->name('statut');
        Route::delete('/{presence}', [PresencesController::class, 'destroy'])->name('destroy');
    });

    // Justificatifs Absences
    Route::prefix('justificatifs-absences')->name('justificatifs_absences.')->group(function () {
        Route::get('/', [JustificatifAbsenceController::class, 'index'])->name('index');
        Route::get('/create', [JustificatifAbsenceController::class, 'create'])->name('create');
        Route::post('/', [JustificatifAbsenceController::class, 'store'])->name('store');
        Route::get('/{justificatif_absence}', [JustificatifAbsenceController::class, 'show'])->name('show');
        Route::get('/{justificatif_absence}/edit', [JustificatifAbsenceController::class, 'edit'])->name('edit');
        Route::put('/{justificatif_absence}', [JustificatifAbsenceController::class, 'update'])->name('update');
        Route::put('/{justificatif_absence}/statut', [JustificatifAbsenceController::class, 'statut'])->name('statut');
        Route::delete('/{justificatif_absence}', [JustificatifAbsenceController::class, 'destroy'])->name('destroy');
    });

    // PHASE 5: Devoirs, Rendus, Moyennes, Personnel Admin

    // Devoirs
    Route::prefix('devoirs')->name('devoirs.')->group(function () {
        Route::get('/', [DevoirController::class, 'index'])->name('index');
        Route::get('/create', [DevoirController::class, 'create'])->name('create');
        Route::post('/', [DevoirController::class, 'store'])->name('store');
        Route::get('/{devoir}', [DevoirController::class, 'show'])->name('show');
        Route::get('/{devoir}/edit', [DevoirController::class, 'edit'])->name('edit');
        Route::put('/{devoir}', [DevoirController::class, 'update'])->name('update');
        Route::put('/{devoir}/statut', [DevoirController::class, 'activate'])->name('statut');
        Route::delete('/{devoir}', [DevoirController::class, 'destroy'])->name('destroy');
    });

    // Rendus Devoirs
    Route::prefix('rendus-devoirs')->name('rendus_devoirs.')->group(function () {
        Route::get('/', [RenduDevoirController::class, 'index'])->name('index');
        Route::get('/create', [RenduDevoirController::class, 'create'])->name('create');
        Route::post('/', [RenduDevoirController::class, 'store'])->name('store');
        Route::get('/{rendu_devoir}', [RenduDevoirController::class, 'show'])->name('show');
        Route::get('/{rendu_devoir}/edit', [RenduDevoirController::class, 'edit'])->name('edit');
        Route::put('/{rendu_devoir}', [RenduDevoirController::class, 'update'])->name('update');
        Route::put('/{rendu_devoir}/statut', [RenduDevoirController::class, 'statut'])->name('statut');
        Route::delete('/{rendu_devoir}', [RenduDevoirController::class, 'destroy'])->name('destroy');
    });

    // Moyennes Matieres (Phase 1: créer les moyennes par apprenant)
    Route::prefix('moyennes-matieres')->name('moyennes_matieres.')->group(function () {
        Route::get('/', [MoyenneMatiereController::class, 'index'])->name('index');
        Route::get('/create', [MoyenneMatiereController::class, 'create'])->name('create');
        Route::get('/check-exists', [MoyenneMatiereController::class, 'checkExists'])->name('check_exists');
        Route::get('/api/averages-by-apprenant', [MoyenneMatiereController::class, 'getAveragesByApprenant'])->name('api_averages_by_apprenant');
        Route::post('/', [MoyenneMatiereController::class, 'store'])->name('store');
        Route::get('/{moyenne_matiere}', [MoyenneMatiereController::class, 'show'])->name('show');
        Route::get('/{moyenne_matiere}/edit', [MoyenneMatiereController::class, 'edit'])->name('edit');
        Route::put('/{moyenne_matiere}', [MoyenneMatiereController::class, 'update'])->name('update');
        Route::put('/{moyenne_matiere}/statut', [MoyenneMatiereController::class, 'statut'])->name('statut');
        Route::delete('/{moyenne_matiere}', [MoyenneMatiereController::class, 'destroy'])->name('destroy');
    });

    // Personnels Administratifs
    Route::prefix('personnels-administratifs')->name('personnels_administratifs.')->group(function () {
        Route::get('/', [PersonnelAdministratifController::class, 'index'])->name('index');
        Route::get('/create', [PersonnelAdministratifController::class, 'create'])->name('create');
        Route::post('/', [PersonnelAdministratifController::class, 'store'])->name('store');
        Route::get('/{personnel_administratif}', [PersonnelAdministratifController::class, 'show'])->name('show');
        Route::get('/{personnel_administratif}/edit', [PersonnelAdministratifController::class, 'edit'])->name('edit');
        Route::put('/{personnel_administratif}', [PersonnelAdministratifController::class, 'update'])->name('update');
        Route::put('/{personnel_administratif}/statut', [PersonnelAdministratifController::class, 'statut'])->name('statut');
        Route::delete('/{personnel_administratif}', [PersonnelAdministratifController::class, 'destroy'])->name('destroy');
    });

    // Absences génériques : feature consolidée dans /absences-apprenants et /absences-enseignants
    // (la table `absences` générique a été dropée pour éliminer la triple redondance)

    // Route pour télécharger/visualiser les fichiers d'absence
    Route::get('/download-absence/{filePath}', [AbsenceApprenantController::class, 'downloadFile'])->name('download_absence_file');

    // Listes Manuels
    Route::prefix('listes-manuels')->name('listes-manuels.')->group(function () {
        Route::get('/', [ListeManuelsController::class, 'index'])->name('index');
        Route::get('/create', [ListeManuelsController::class, 'create'])->name('create');
        Route::post('/', [ListeManuelsController::class, 'store'])->name('store');
        Route::get('/{listeManuels}', [ListeManuelsController::class, 'show'])->name('show');
        Route::get('/{listeManuels}/edit', [ListeManuelsController::class, 'edit'])->name('edit');
        Route::put('/{listeManuels}', [ListeManuelsController::class, 'update'])->name('update');
        Route::put('/{listeManuels}/statut', [ListeManuelsController::class, 'statut'])->name('statut');
        Route::delete('/{listeManuels}', [ListeManuelsController::class, 'destroy'])->name('destroy');
    });

    // Manuels
    Route::prefix('manuels')->name('manuels.')->group(function () {
        Route::get('/', [ManuelController::class, 'index'])->name('index');
        Route::get('/create', [ManuelController::class, 'create'])->name('create');
        Route::post('/', [ManuelController::class, 'store'])->name('store');
        Route::get('/{listeManuels}', [ManuelController::class, 'show'])->name('show');
        Route::get('/{listeManuels}/edit', [ManuelController::class, 'edit'])->name('edit');
        Route::put('/{listeManuels}', [ManuelController::class, 'update'])->name('update');
        Route::put('/{listeManuels}/statut', [ManuelController::class, 'statut'])->name('statut');
        Route::delete('/{listeManuels}', [ManuelController::class, 'destroy'])->name('destroy');
    });

    // Passages
    Route::prefix('passages')->name('passages.')->group(function () {
        Route::get('/', [PassageController::class, 'index'])->name('index');
        Route::get('/create', [PassageController::class, 'create'])->name('create');
        Route::post('/', [PassageController::class, 'store'])->name('store');
        Route::get('/{passage}', [PassageController::class, 'show'])->name('show');
        Route::get('/{passage}/edit', [PassageController::class, 'edit'])->name('edit');
        Route::put('/{passage}', [PassageController::class, 'update'])->name('update');
        Route::put('/{passage}/statut', [PassageController::class, 'statut'])->name('statut');
        Route::delete('/{passage}', [PassageController::class, 'destroy'])->name('destroy');
    });

    // Classes Apprenants (Student Classes)
    Route::prefix('classes-apprenants')->name('classes_apprenants.')->group(function () {
        Route::get('/', [ClassesApprenantsController::class, 'index'])->name('index');
        Route::get('/create', [ClassesApprenantsController::class, 'create'])->name('create');
        Route::post('/', [ClassesApprenantsController::class, 'store'])->name('store');
        Route::get('/{apprenant}', [ClassesApprenantsController::class, 'show'])->name('show');
        Route::get('/{apprenant}/edit', [ClassesApprenantsController::class, 'edit'])->name('edit');
        Route::put('/{apprenant}', [ClassesApprenantsController::class, 'update'])->name('update');
        Route::delete('/{apprenant}', [ClassesApprenantsController::class, 'destroy'])->name('destroy');
    });

    // Bibliotheques
    Route::prefix('bibliotheques')->name('bibliotheques.')->group(function () {
        Route::get('/', [BibliothequeController::class, 'index'])->name('index');
        Route::get('/create', [BibliothequeController::class, 'create'])->name('create');
        Route::post('/', [BibliothequeController::class, 'store'])->name('store');
        Route::get('/{bibliotheque}', [BibliothequeController::class, 'show'])->name('show');
        Route::get('/{bibliotheque}/edit', [BibliothequeController::class, 'edit'])->name('edit');
        Route::put('/{bibliotheque}', [BibliothequeController::class, 'update'])->name('update');
        Route::put('/{bibliotheque}/statut', [BibliothequeController::class, 'statut'])->name('statut');
        Route::delete('/{bibliotheque}', [BibliothequeController::class, 'destroy'])->name('destroy');
    });

    // Planification Examens
    Route::prefix('planification-examens')->name('planification-examens.')->group(function () {
        Route::get('/', [PlanificationExamenController::class, 'index'])->name('index');
        Route::get('/create', [PlanificationExamenController::class, 'create'])->name('create');
        Route::post('/', [PlanificationExamenController::class, 'store'])->name('store');
        Route::get('/{planificationExamen}', [PlanificationExamenController::class, 'show'])->name('show');
        Route::get('/{planificationExamen}/edit', [PlanificationExamenController::class, 'edit'])->name('edit');
        Route::put('/{planificationExamen}', [PlanificationExamenController::class, 'update'])->name('update');
        Route::put('/{planificationExamen}/statut', [PlanificationExamenController::class, 'statut'])->name('statut');
        Route::delete('/{planificationExamen}', [PlanificationExamenController::class, 'destroy'])->name('destroy');
    });

    // Examens En Ligne
    Route::prefix('examens-en-ligne')->name('examens-en-ligne.')->group(function () {
        Route::get('/', [ExamenEnLigneController::class, 'index'])->name('index');
        Route::get('/create', [ExamenEnLigneController::class, 'create'])->name('create');
        Route::post('/', [ExamenEnLigneController::class, 'store'])->name('store');
        Route::get('/{examenEnLigne}', [ExamenEnLigneController::class, 'show'])->name('show');
        Route::get('/{examenEnLigne}/edit', [ExamenEnLigneController::class, 'edit'])->name('edit');
        Route::put('/{examenEnLigne}', [ExamenEnLigneController::class, 'update'])->name('update');
        Route::put('/{examenEnLigne}/statut', [ExamenEnLigneController::class, 'statut'])->name('statut');
        Route::delete('/{examenEnLigne}', [ExamenEnLigneController::class, 'destroy'])->name('destroy');
        Route::get('/{examenEnLigne}/resultats', [ExamenEnLigneController::class, 'resultats'])->name('resultats');

        // Questions d'un examen (API JSON)
        Route::get('/{examenEnLigne}/questions', [QuestionExamenController::class, 'index'])->name('questions.index');
        Route::post('/{examenEnLigne}/questions', [QuestionExamenController::class, 'store'])->name('questions.store');
        Route::put('/{examenEnLigne}/questions/{question}', [QuestionExamenController::class, 'update'])->name('questions.update');
        Route::delete('/{examenEnLigne}/questions/{question}', [QuestionExamenController::class, 'destroy'])->name('questions.destroy');
        Route::post('/{examenEnLigne}/questions/reorder', [QuestionExamenController::class, 'reorder'])->name('questions.reorder');
    });

    // Composition d'examen (côté apprenant)
    Route::prefix('composition')->name('composition.')->group(function () {
        Route::get('/mes-examens', [CompositionExamenController::class, 'mesExamens'])->name('mes-examens');
        Route::post('/{examenEnLigne}/demarrer', [CompositionExamenController::class, 'demarrer'])->name('demarrer');
        Route::get('/{tentative}/composer', [CompositionExamenController::class, 'composer'])->name('composer');
        Route::post('/{tentative}/reponse', [CompositionExamenController::class, 'sauvegarderReponse'])->name('sauvegarder-reponse');
        Route::post('/{tentative}/soumettre', [CompositionExamenController::class, 'soumettre'])->name('soumettre');
        Route::post('/{tentative}/log-incident', [CompositionExamenController::class, 'logIncident'])->name('log-incident');
        Route::get('/{tentative}/resultat', [CompositionExamenController::class, 'resultat'])->name('resultat');
    });

    // Resultats Examens (automatique - lecture seule)
    Route::prefix('resultats-examens')->name('resultats-examens.')->group(function () {
        Route::get('/', [ResultatExamenController::class, 'index'])->name('index');
        Route::get('/classe/{examenEnLigne}/pdf', [ResultatExamenController::class, 'pdfClasse'])->name('pdf-classe');
        Route::get('/{resultatExamen}/pdf', [ResultatExamenController::class, 'pdfIndividuel'])->name('pdf');
        Route::get('/{resultatExamen}', [ResultatExamenController::class, 'show'])->name('show');
    });

    // Exam Finance Management
    Route::prefix('exam-finance')->name('exam-finance.')->group(function () {
        Route::get('/', [ExamFinanceController::class, 'index'])->name('index');
        Route::get('/create', [ExamFinanceController::class, 'create'])->name('create');
        Route::post('/', [ExamFinanceController::class, 'store'])->name('store');
        Route::get('/pending-validation', [ExamFinanceController::class, 'pendingValidation'])->name('pending-validation');
        Route::get('/{examFinance}', [ExamFinanceController::class, 'show'])->name('show');
        Route::get('/{examFinance}/edit', [ExamFinanceController::class, 'edit'])->name('edit');
        Route::put('/{examFinance}', [ExamFinanceController::class, 'update'])->name('update');
        Route::post('/{examFinance}/validate', [ExamFinanceController::class, 'validate'])->name('validate');
        Route::post('/{examFinance}/reject', [ExamFinanceController::class, 'reject'])->name('reject');
        Route::post('/{examFinance}/close', [ExamFinanceController::class, 'close'])->name('close');
        Route::delete('/{examFinance}', [ExamFinanceController::class, 'destroy'])->name('destroy');
        Route::get('/{exam}/status', [ExamFinanceController::class, 'getExamStatus'])->name('status');
    });

});
