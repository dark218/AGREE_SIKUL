<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du statut Apprenant (simplifié en Actif / Inactif) et de la tolérance
 * aux clés étrangères optionnelles vides/orphelines (normalizeForeignKeys()).
 *
 * Contexte : le formulaire enregistrait auparavant un code de référentiel
 * (ex: 'STAP_01') qui tronquait la colonne ENUM. On a simplifié le statut à
 * 'actif'/'inactif' et rendu les FK optionnelles tolérantes.
 */
class ApprenantStatutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        // On cible la logique du contrôleur (validation + normalisation des FK),
        // pas la couche permissions RBAC.
        $this->withoutMiddleware();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'matricule' => 'MAT-' . uniqid(),
            'nom'       => 'TEST',
            'prenoms'   => 'Statut',
            'statut'    => 'actif',
        ], $overrides);
    }

    /** @test */
    public function le_statut_actif_est_accepte(): void
    {
        $this->post(route('academique.apprenants.store'), $this->payload([
            'matricule' => 'MAT-ACT-1',
            'statut'    => 'actif',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('apprenants', ['matricule' => 'MAT-ACT-1', 'statut' => 'actif']);
    }

    /** @test */
    public function le_statut_inactif_est_accepte(): void
    {
        $this->post(route('academique.apprenants.store'), $this->payload([
            'matricule' => 'MAT-INACT-1',
            'statut'    => 'inactif',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('apprenants', ['matricule' => 'MAT-INACT-1', 'statut' => 'inactif']);
    }

    /** @test */
    public function un_ancien_code_referentiel_de_statut_est_rejete(): void
    {
        // 'STAP_01' (ancien code référentiel) ne fait plus partie des valeurs valides.
        $this->post(route('academique.apprenants.store'), $this->payload([
            'matricule' => 'MAT-BAD-1',
            'statut'    => 'STAP_01',
        ]))->assertSessionHasErrors('statut');

        $this->assertDatabaseMissing('apprenants', ['matricule' => 'MAT-BAD-1']);
    }

    /** @test */
    public function les_cles_etrangeres_optionnelles_vides_ou_orphelines_ne_bloquent_pas(): void
    {
        // '' (select vidé) et un id inexistant doivent être ramenés à null
        // par normalizeForeignKeys() au lieu de bloquer l'enregistrement.
        $this->post(route('academique.apprenants.store'), $this->payload([
            'matricule'                 => 'MAT-FK-1',
            'commune_naissance_id'      => '',
            'departement_naissance_id'  => 999999, // n'existe pas
            'region_naissance_id'       => '',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('apprenants', [
            'matricule'                => 'MAT-FK-1',
            'commune_naissance_id'     => null,
            'departement_naissance_id' => null,
        ]);
    }
}
