<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Academique\Entities\AffectationEnseignant;
use Modules\Academique\Entities\Enseignant;
use Modules\Finances\Entities\Depense;
use Modules\Finances\Entities\Echeancier;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\Paiement;
use Modules\Finances\Entities\TypeFrais;
use Modules\Parametrage\Entities\GroupeMatiere;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Services\Entities\ConsultationInfirmerie;
use Modules\Services\Entities\PassageCantine;
use Tests\TestCase;

/**
 * Tests de non-régression pour les refactors des Phases 3 et 4.
 *
 * Approche : DB::insert direct pour créer les prérequis (école, année scolaire),
 * évite les factories manquantes dans Parametrage/Services.
 */
class RefactorsPhase3Phase4Test extends TestCase
{
    use RefreshDatabase;

    private function makeInstitution(): int
    {
        // Table pluriel `institutions` (schéma canonique).
        return (int) DB::table('institutions')->insertGetId([
            'code'       => 'INS-' . uniqid(),
            'nom'        => 'Institution Test',
            'statut'     => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Insère un campus minimal + école, renvoie l'id de l'école.
     * (ecoles.campus_id → campuses.id → institutions.id NOT NULL en cascade.)
     */
    private function makeEcole(): int
    {
        $campusId = DB::table('campuses')->insertGetId([
            'institution_id' => $this->makeInstitution(),
            'code'           => 'CAM-' . uniqid(),
            'nom'            => 'Campus Test',
            'statut'         => 'actif',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
        return (int) DB::table('ecoles')->insertGetId([
            'campus_id'  => $campusId,
            'code'       => 'ECO-' . uniqid(),
            'nom'        => 'Ecole Test',
            'statut'     => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeCycleEnseignement(): int
    {
        return (int) DB::table('cycles_enseignement')->insertGetId([
            'code'       => 'CY-' . uniqid(),
            'libelle'    => 'Cycle Test',
            'etat'       => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function makeAnneeScolaire(): int
    {
        return (int) DB::table('annees_scolaires')->insertGetId([
            'libelle'      => '2026-2027-' . uniqid(),
            'date_debut'   => '2026-09-01',
            'date_fin'     => '2027-06-30',
            'duree'        => 10,
            'est_courante' => 1,
            'etat'         => 'actif',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);
    }

    /**
     * Niveau d'étude minimal — requis pour groupes_matieres.niveau_id.
     * cycle_id → cycles_enseignement NOT NULL.
     */
    private function makeNiveauEtude(): int
    {
        return (int) DB::table('niveaux_etudes')->insertGetId([
            'code'       => 'N-' . uniqid(),
            'libelle'    => 'Niveau Test',
            'cycle_id'   => $this->makeCycleEnseignement(),
            'etat'       => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ================================================================
    // PHASE 3.1 — AffectationEnseignant pivot BelongsToMany
    // ================================================================

    /** @test */
    public function affectation_enseignant_utilise_pivot_belongstoman_matieres(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $ens = Enseignant::create([
            'user_id'   => $user->id,
            'matricule' => 'ENS-AFF-01',
            'nom'       => 'A', 'prenoms' => 'B',
            'statut'    => 'actif',
        ]);

        $affectation = AffectationEnseignant::create([
            'enseignant_id' => $ens->id,
            'etat'          => 'actif',
        ]);

        $m1 = MatiereUnite::firstOrCreate(['code' => 'M-TEST-1'], ['libelle' => 'Test 1', 'etat' => 'actif']);
        $m2 = MatiereUnite::firstOrCreate(['code' => 'M-TEST-2'], ['libelle' => 'Test 2', 'etat' => 'actif']);

        $affectation->matieres()->sync([$m1->id, $m2->id]);

        $this->assertCount(2, $affectation->fresh()->matieres);
        $this->assertDatabaseHas('affectation_matieres', [
            'affectation_enseignant_id' => $affectation->id,
            'matiere_id'                => $m1->id,
        ]);
    }

    // ================================================================
    // PHASE 3.2 — GroupeMatiere pivot BelongsToMany
    // ================================================================

    /** @test */
    public function groupe_matiere_utilise_pivot_belongstoman_matieres(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $groupe = GroupeMatiere::create([
            'code'      => 'GM-TEST',
            'libelle'   => 'Groupe Test',
            'niveau_id' => $this->makeNiveauEtude(),
            'etat'      => 'actif',
        ]);

        $m1 = MatiereUnite::firstOrCreate(['code' => 'GM-M-1'], ['libelle' => 'GM Test 1', 'etat' => 'actif']);
        $m2 = MatiereUnite::firstOrCreate(['code' => 'GM-M-2'], ['libelle' => 'GM Test 2', 'etat' => 'actif']);
        $m3 = MatiereUnite::firstOrCreate(['code' => 'GM-M-3'], ['libelle' => 'GM Test 3', 'etat' => 'actif']);

        $groupe->matieres()->sync([$m1->id, $m2->id, $m3->id]);

        $this->assertCount(3, $groupe->fresh()->matieres);
    }

    // ================================================================
    // PHASE 4.6b — Fix Depense §11.7 (data-loss)
    // ================================================================

    /** @test */
    public function depense_se_cree_avec_les_vraies_colonnes_db(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $ecoleId = $this->makeEcole();

        $depense = Depense::create([
            'ecole_id'      => $ecoleId,
            'libelle'       => 'Fournitures bureau',
            'categorie'     => 'Fournitures',
            'montant_cents' => 15_000,
            'date_depense'  => now()->toDateString(),
            'auteur_id'     => $user->id,
        ]);

        $this->assertDatabaseHas('depenses', [
            'id'            => $depense->id,
            'libelle'       => 'Fournitures bureau',
            'montant_cents' => 15_000,
        ]);
        $this->assertSame(150.0, $depense->getMontantEnEuros());
    }

    // ================================================================
    // PHASE 4.6b — TypeFrais aligné (retirait `statut` inexistant)
    // ================================================================

    /** @test */
    public function type_frais_utilise_obligatoire_et_pas_de_statut(): void
    {
        $t = TypeFrais::create([
            'code'          => 'SCOL',
            'libelle'       => 'Scolarité',
            'description'   => 'Frais annuels',
            'montant_cents' => 500_000,
            'obligatoire'   => true,
        ]);

        $this->assertDatabaseHas('types_frais', [
            'code'          => 'SCOL',
            'obligatoire'   => 1,
            'montant_cents' => 500_000,
        ]);
        $this->assertTrue((bool) $t->obligatoire);
    }

    // ================================================================
    // PHASE 4.6b — Frais aligné (apprenant_id/annee_scolaire_id requis)
    // ================================================================

    /** @test */
    public function frais_requiert_apprenant_annee_type(): void
    {
        $apprenant = User::factory()->create();
        $type      = TypeFrais::create(['code' => 'INS', 'libelle' => 'Inscription', 'montant_cents' => 10_000]);
        $anneeId   = $this->makeAnneeScolaire();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $anneeId,
            'type_frais_id'     => $type->id,
            'montant_cents'     => 10_000,
            'statut'            => 'non_paye',
        ]);

        $this->assertDatabaseHas('frais', [
            'id'     => $frais->id,
            'statut' => 'non_paye',
        ]);
        // Bug fix : scope partielPaye utilisait `partiel_paye`, corrigé en `partiellement_paye`
        $this->assertSame(0, Frais::partielPaye()->count());
    }

    // ================================================================
    // PHASE 4.6b — Paiement enums corrigés
    // ================================================================

    /** @test */
    public function paiement_utilise_les_enums_reels_du_schema(): void
    {
        $apprenant = User::factory()->create();
        $type      = TypeFrais::create(['code' => 'PAI', 'libelle' => 'Paiement Test', 'montant_cents' => 1_000]);
        $anneeId   = $this->makeAnneeScolaire();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $anneeId,
            'type_frais_id'     => $type->id,
            'montant_cents'     => 1_000,
            'statut'            => 'non_paye',
        ]);

        $recu = User::factory()->create();

        // Enum DB : espece, cheque, virement, mobile_money, carte
        foreach (['espece', 'cheque', 'virement', 'mobile_money', 'carte'] as $mode) {
            $p = Paiement::create([
                'frais_id'      => $frais->id,
                'apprenant_id'  => $apprenant->id,
                'montant_cents' => 100,
                'mode_paiement' => $mode,
                'date_paiement' => now()->toDateString(),
                'recu_par'      => $recu->id,
            ]);
            $this->assertSame($mode, $p->mode_paiement);
        }
    }

    // ================================================================
    // PHASE 4.6b — Echeancier enums corrigés
    // ================================================================

    /** @test */
    public function echeancier_enum_statut_align(): void
    {
        $apprenant = User::factory()->create();
        $type      = TypeFrais::create(['code' => 'ECH', 'libelle' => 'Echeance Test', 'montant_cents' => 1_000]);
        $anneeId   = $this->makeAnneeScolaire();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $anneeId,
            'type_frais_id'     => $type->id,
            'montant_cents'     => 1_000,
            'statut'            => 'non_paye',
        ]);

        // Enum DB : en_attente, paye, retard (pas non_payee/payee/en_retard/annulee)
        foreach (['en_attente', 'paye', 'retard'] as $i => $statut) {
            $e = Echeancier::create([
                'frais_id'        => $frais->id,
                'numero_echeance' => $i + 1,
                'montant_cents'   => 1_000,
                'date_echeance'   => now()->toDateString(),
                'statut'          => $statut,
            ]);
            $this->assertSame($statut, $e->statut);
        }
    }

    // ================================================================
    // PHASE 4.6c — ConsultationInfirmerie (retirait heure/observations/statut)
    // ================================================================

    /** @test */
    public function consultation_infirmerie_ne_persiste_que_les_champs_reels(): void
    {
        $apprenant = User::factory()->create();
        $infirmier = User::factory()->create();

        $c = ConsultationInfirmerie::create([
            'apprenant_id'      => $apprenant->id,
            'infirmier_id'      => $infirmier->id,
            'date_consultation' => now(),
            'motif'             => 'Fièvre',
            'diagnostic'        => 'Rhume',
            'traitement'        => 'Repos',
        ]);

        $this->assertDatabaseHas('consultations_infirmeries', [
            'id'           => $c->id,
            'motif'        => 'Fièvre',
            'apprenant_id' => $apprenant->id,
        ]);
    }

    // ================================================================
    // PHASE 4.6c — PassageCantine (retirait 5 colonnes fantômes)
    // ================================================================

    /** @test */
    public function passage_cantine_ne_persiste_que_inscription_date_heure(): void
    {
        $apprenant = User::factory()->create();
        $anneeId   = $this->makeAnneeScolaire();

        // Insertion directe d'un service_cantine minimal + inscription.
        $serviceCantineId = DB::table('services_cantines')->insertGetId([
            'nom'        => 'Service Test',
            'ecole_id'   => $apprenant->id, // ecole_id → users (FK atypique)
            'statut'     => 'actif',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $inscriptionId = DB::table('inscriptions_cantines')->insertGetId([
            'service_cantine_id' => $serviceCantineId,
            'apprenant_id'       => $apprenant->id,
            'annee_scolaire_id'  => $anneeId,
            'type_formule'       => 'complet',
            'statut'             => 'active',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $p = PassageCantine::create([
            'inscription_cantine_id' => $inscriptionId,
            'date_passage'           => now()->toDateString(),
            'heure_passage'          => '12:30:00',
        ]);

        $this->assertDatabaseHas('passages_cantines', [
            'id'                     => $p->id,
            'inscription_cantine_id' => $inscriptionId,
        ]);
    }
}
