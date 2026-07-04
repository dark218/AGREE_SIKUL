<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Academique\Entities\Tuteur;
use Tests\TestCase;

/**
 * Tests fonctionnels — CRUD Tuteur + pivot N-N apprenant_tuteur.
 */
class TuteurCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_tuteur_peut_etre_cree_avec_un_user_associe(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $tuteur = Tuteur::create([
            'user_id'    => $user->id,
            'relation'   => 'oncle',
            'profession' => 'Ingénieur',
        ]);

        $this->assertDatabaseHas('tuteurs', [
            'user_id'  => $user->id,
            'relation' => 'oncle',
        ]);
        $this->assertSame($user->id, $tuteur->user_id);
    }

    /** @test */
    public function un_tuteur_peut_suivre_plusieurs_apprenants_meme_ecole(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $a1 = Apprenant::create(['matricule' => 'FRAT-01', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif', 'ecole_id' => 1]);
        $a2 = Apprenant::create(['matricule' => 'FRAT-02', 'nom' => 'A', 'prenoms' => 'C', 'statut' => 'actif', 'ecole_id' => 1]);

        $tuteur = Tuteur::create(['user_id' => $user->id, 'relation' => 'pere']);
        $tuteur->apprenants()->sync([
            $a1->id => ['relation' => 'pere', 'est_principal' => true],
            $a2->id => ['relation' => 'pere', 'est_principal' => false],
        ]);

        $this->assertCount(2, $tuteur->fresh()->apprenants);
        $this->assertDatabaseHas('apprenant_tuteur', [
            'tuteur_id' => $tuteur->id, 'apprenant_id' => $a1->id, 'est_principal' => 1,
        ]);
        $this->assertDatabaseHas('apprenant_tuteur', [
            'tuteur_id' => $tuteur->id, 'apprenant_id' => $a2->id, 'est_principal' => 0,
        ]);
    }

    /** @test */
    public function la_relation_apprenants_est_belongs_to_many(): void
    {
        $user = User::factory()->create();
        $t = Tuteur::create(['user_id' => $user->id]);
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $t->apprenants()
        );
    }
}
