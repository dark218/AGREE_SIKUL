<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\AffectationEnseignant;
use Modules\Academique\Entities\Enseignant;
use Modules\Finances\Entities\Depense;
use Modules\Finances\Entities\Echeancier;
use Modules\Finances\Entities\Frais;
use Modules\Finances\Entities\Paiement;
use Modules\Finances\Entities\TypeFrais;
use Modules\Parametrage\Entities\AnneeScolaire;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\GroupeMatiere;
use Modules\Parametrage\Entities\MatiereUnite;
use Modules\Services\Entities\ConsultationInfirmerie;
use Modules\Services\Entities\InscriptionCantine;
use Modules\Services\Entities\PassageCantine;
use Tests\TestCase;

/**
 * Tests de non-régression pour les refactors des Phases 3 et 4 :
 *   - Pivots BelongsToMany (Phase 3.1 & 3.2)
 *   - Refonte des 11 stubs §11.8 (Phase 4.6)
 *   - Fix Depense §11.7
 *
 * Ces tests vérifient que les entités et controllers sont bien alignés sur
 * le schéma DB — chaque bug qui causait un SQL crash (Field ... doesn't have
 * a default value) ou une perte silencieuse doit rester fixé.
 */
class RefactorsPhase3Phase4Test extends TestCase
{
    use RefreshDatabase;

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
            'code'    => 'GM-TEST',
            'libelle' => 'Groupe Test',
            'etat'    => 'actif',
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
        $ecole = Ecole::factory()->create();

        $depense = Depense::create([
            'ecole_id'      => $ecole->id,
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
        $this->assertTrue($t->obligatoire);
    }

    // ================================================================
    // PHASE 4.6b — Frais aligné (apprenant_id/annee_scolaire_id requis)
    // ================================================================

    /** @test */
    public function frais_requiert_apprenant_annee_type(): void
    {
        $apprenant = User::factory()->create();
        $type      = TypeFrais::create(['code' => 'INS', 'libelle' => 'Inscription', 'montant_cents' => 10_000]);
        $annee     = AnneeScolaire::factory()->create();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $annee->id,
            'type_frais_id'     => $type->id,
            'montant_cents'     => 10_000,
            'statut'            => 'non_paye',
        ]);

        $this->assertDatabaseHas('frais', [
            'id'       => $frais->id,
            'statut'   => 'non_paye',
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
        $annee     = AnneeScolaire::factory()->create();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $annee->id,
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
        $annee     = AnneeScolaire::factory()->create();

        $frais = Frais::create([
            'apprenant_id'      => $apprenant->id,
            'annee_scolaire_id' => $annee->id,
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
        // Skip si la migration InscriptionCantine n'est pas cohérente en test.
        if (!class_exists(InscriptionCantine::class)) {
            $this->markTestSkipped('InscriptionCantine indisponible');
        }

        // Minimal : une inscription cantine pour la FK
        $apprenant = User::factory()->create();
        $inscription = InscriptionCantine::factory()->create([
            'apprenant_id' => $apprenant->id,
        ]);

        $p = PassageCantine::create([
            'inscription_cantine_id' => $inscription->id,
            'date_passage'           => now()->toDateString(),
            'heure_passage'          => '12:30:00',
        ]);

        $this->assertDatabaseHas('passages_cantines', [
            'id'                     => $p->id,
            'inscription_cantine_id' => $inscription->id,
        ]);
    }
}
