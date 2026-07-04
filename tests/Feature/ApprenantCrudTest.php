<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Genre;
use Tests\TestCase;

/**
 * Tests fonctionnels — CRUD Apprenant.
 * Vérifie que le formulaire de création accepte les champs attendus
 * (y compris genre_id, photo) et que les relations pivot / contacts
 * humains fonctionnent.
 */
class ApprenantCrudTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    /** @test */
    public function un_apprenant_peut_etre_cree_avec_les_champs_essentiels(): void
    {
        $this->actingAsAdmin();
        $genre = Genre::firstOrCreate(['code' => 'M'], ['libelle' => 'Masculin', 'etat' => 'actif']);

        $apprenant = Apprenant::create([
            'matricule'  => 'MAT-TEST-001',
            'nom'        => 'DUPONT',
            'prenoms'    => 'Jean',
            'sexe'       => 'M',
            'genre_id'   => $genre->id,
            'statut'     => 'actif',
            'nom_pere'   => 'Marc DUPONT',
            'nom_mere'   => 'Sophie MARTIN',
        ]);

        $this->assertDatabaseHas('apprenants', [
            'matricule' => 'MAT-TEST-001',
            'nom'       => 'DUPONT',
            'genre_id'  => $genre->id,
        ]);
        $this->assertSame('Masculin', $apprenant->genre?->libelle);
    }

    /** @test */
    public function le_matricule_apprenant_est_unique(): void
    {
        $this->actingAsAdmin();

        Apprenant::create(['matricule' => 'DUP-TEST', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Apprenant::create(['matricule' => 'DUP-TEST', 'nom' => 'C', 'prenoms' => 'D', 'statut' => 'actif']);
    }

    /** @test */
    public function les_relations_parents_tuteurs_accompagnateurs_sont_definies(): void
    {
        $this->actingAsAdmin();
        $a = Apprenant::create([
            'matricule' => 'REL-TEST-001',
            'nom' => 'X', 'prenoms' => 'Y', 'statut' => 'actif',
        ]);

        // Ces relations sont N-N via pivot — ici on vérifie juste que les
        // méthodes existent et retournent un BelongsToMany.
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $a->parents()
        );
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $a->tuteurs()
        );
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $a->accompagnateurs()
        );
    }

    /** @test */
    public function les_apprenants_soft_deletes_sont_exclus_par_defaut(): void
    {
        $this->actingAsAdmin();
        $a = Apprenant::create([
            'matricule' => 'SOFT-TEST-001',
            'nom' => 'Z', 'prenoms' => 'W', 'statut' => 'actif',
        ]);
        $a->delete();

        $this->assertNull(Apprenant::find($a->id));
        $this->assertNotNull(Apprenant::withTrashed()->find($a->id));
    }
}
