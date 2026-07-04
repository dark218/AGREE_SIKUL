<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Personnel\Entities\StudentParent;
use Tests\TestCase;

/**
 * Tests fonctionnels — CRUD Parent (StudentParent).
 * Couvre :
 *  • auto-création User avec role='parent' + uuid
 *  • multi-apprenants via pivot apprenant_parent
 *  • règle "même école" pour la fratrie
 */
class ParentCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    /** @test */
    public function un_parent_peut_etre_cree_avec_infos_pere_mere(): void
    {
        $this->actingAsAdmin();

        $parent = StudentParent::create([
            'pere_nom'         => 'DUPONT',
            'pere_prenoms'     => 'Jean',
            'pere_telephone_1' => '+225 07 00 00 01 01',
            'mere_nom'         => 'MARTIN',
            'mere_prenoms'     => 'Sophie',
            'etat'             => 'actif',
        ]);

        $this->assertDatabaseHas('parents', [
            'pere_nom' => 'DUPONT',
            'mere_nom' => 'MARTIN',
        ]);
        $this->assertSame('DUPONT', $parent->pere_nom);
    }

    /** @test */
    public function un_parent_peut_avoir_plusieurs_apprenants_meme_ecole(): void
    {
        $this->actingAsAdmin();

        $a1 = Apprenant::create(['matricule' => 'FRAT-PA-01', 'nom' => 'DUPONT', 'prenoms' => 'Alice', 'statut' => 'actif', 'ecole_id' => 1]);
        $a2 = Apprenant::create(['matricule' => 'FRAT-PA-02', 'nom' => 'DUPONT', 'prenoms' => 'Bob',   'statut' => 'actif', 'ecole_id' => 1]);

        $parent = StudentParent::create(['pere_nom' => 'DUPONT', 'pere_prenoms' => 'Jean', 'etat' => 'actif']);
        $parent->apprenants()->sync([
            $a1->id => ['lien_parente' => 'pere', 'est_principal' => true],
            $a2->id => ['lien_parente' => 'pere', 'est_principal' => false],
        ]);

        $this->assertCount(2, $parent->fresh()->apprenants);
        $this->assertDatabaseHas('apprenant_parent', [
            'parent_id' => $parent->id, 'apprenant_id' => $a1->id, 'est_principal' => 1,
        ]);
    }

    /** @test */
    public function la_relation_apprenants_est_belongs_to_many(): void
    {
        $parent = StudentParent::create(['pere_nom' => 'X', 'pere_prenoms' => 'Y', 'etat' => 'actif']);
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $parent->apprenants()
        );
    }

    /** @test */
    public function la_relation_user_est_belongs_to(): void
    {
        $user = User::factory()->create();
        $parent = StudentParent::create([
            'user_id' => $user->id,
            'pere_nom' => 'A', 'pere_prenoms' => 'B', 'etat' => 'actif',
        ]);

        $this->assertSame($user->id, $parent->user?->id);
    }

    /** @test */
    public function le_service_auto_user_creator_produit_un_user_valide(): void
    {
        $userId = \App\Services\AutoUserCreator::forProfile([
            'nom'       => 'DUPONT',
            'prenoms'   => 'Jean',
            'telephone' => '+225 07 12 34 56 78',
            'role'      => 'parent',
        ]);

        $this->assertDatabaseHas('users', [
            'id'    => $userId,
            'nom'   => 'DUPONT',
            'role'  => 'parent',
            'login' => '+225 07 12 34 56 78',
        ]);
        // uuid doit être rempli
        $user = User::find($userId);
        $this->assertNotEmpty($user->uuid);
    }
}
