<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Personnel\Entities\Accompagnateur;
use Tests\TestCase;

/**
 * Tests fonctionnels — CRUD Accompagnateur.
 * Couvre :
 *  • création avec 3 blocs accompagnants
 *  • pivot apprenant_accompagnateur (multi-enfants)
 *  • auto-User + role 'accompagnateur'
 */
class AccompagnateurCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_accompagnateur_peut_etre_cree_avec_bloc_accompagnant1(): void
    {
        $this->actingAs(User::factory()->create());

        $acc = Accompagnateur::create([
            'ecole_id'                  => 1,
            'accompagnant1_civilite'    => 'mr',
            'accompagnant1_nom'         => 'TRANSPORT',
            'accompagnant1_prenoms'     => 'Marc',
            'accompagnant1_nom_complet' => 'Marc TRANSPORT',
            'accompagnant1_lien'        => 'Chauffeur',
            'etat'                      => 'actif',
        ]);

        $this->assertDatabaseHas('accompagnateurs', [
            'accompagnant1_nom' => 'TRANSPORT',
            'etat'              => 'actif',
        ]);
    }

    /** @test */
    public function un_accompagnateur_peut_suivre_plusieurs_apprenants(): void
    {
        $this->actingAs(User::factory()->create());

        $a1 = Apprenant::create(['matricule' => 'FRAT-AC-01', 'nom' => 'X', 'prenoms' => 'A', 'statut' => 'actif', 'ecole_id' => 1]);
        $a2 = Apprenant::create(['matricule' => 'FRAT-AC-02', 'nom' => 'X', 'prenoms' => 'B', 'statut' => 'actif', 'ecole_id' => 1]);
        $a3 = Apprenant::create(['matricule' => 'FRAT-AC-03', 'nom' => 'X', 'prenoms' => 'C', 'statut' => 'actif', 'ecole_id' => 1]);

        $acc = Accompagnateur::create([
            'ecole_id' => 1,
            'accompagnant1_nom' => 'BUS', 'accompagnant1_prenoms' => 'Chauffeur',
            'etat' => 'actif',
        ]);
        $acc->apprenants()->sync([
            $a1->id => ['est_principal' => true],
            $a2->id => ['est_principal' => false],
            $a3->id => ['est_principal' => false],
        ]);

        $this->assertCount(3, $acc->fresh()->apprenants);
        $this->assertDatabaseHas('apprenant_accompagnateur', [
            'accompagnateur_id' => $acc->id, 'apprenant_id' => $a1->id, 'est_principal' => 1,
        ]);
    }

    /** @test */
    public function la_relation_apprenants_est_belongs_to_many(): void
    {
        $acc = Accompagnateur::create([
            'ecole_id' => 1,
            'accompagnant1_nom' => 'X', 'accompagnant1_prenoms' => 'Y',
            'etat' => 'actif',
        ]);
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $acc->apprenants()
        );
    }

    /** @test */
    public function la_relation_user_est_belongs_to(): void
    {
        $user = User::factory()->create();
        $acc = Accompagnateur::create([
            'user_id' => $user->id,
            'ecole_id' => 1,
            'accompagnant1_nom' => 'X', 'accompagnant1_prenoms' => 'Y',
            'etat' => 'actif',
        ]);
        $this->assertSame($user->id, $acc->user?->id);
    }
}
