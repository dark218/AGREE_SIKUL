<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Enseignant;
use Modules\Parametrage\Entities\Genre;
use Tests\TestCase;

/**
 * Tests fonctionnels — CRUD Enseignant.
 * Couvre le bug historique du role users truncation + auto-création
 * du User + relations MatiereUnite/NiveauEtude.
 */
class EnseignantCrudTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function un_enseignant_peut_etre_cree_avec_user_existant(): void
    {
        $user = User::factory()->create(['nom' => 'MBONDO', 'prenoms' => 'Georges']);
        $this->actingAs($user);
        $genre = Genre::firstOrCreate(['code' => 'M'], ['libelle' => 'Masculin', 'etat' => 'actif']);

        $enseignant = Enseignant::create([
            'user_id'   => $user->id,
            'matricule' => 'ENS-001',
            'nom'       => 'MBONDO',
            'prenoms'   => 'Georges',
            'gender'    => 'M',
            'genre_id'  => $genre->id,
            'statut'    => 'actif',
        ]);

        $this->assertDatabaseHas('enseignants', [
            'matricule' => 'ENS-001',
            'user_id'   => $user->id,
        ]);
        $this->assertSame('MBONDO', $enseignant->nom);
    }

    /** @test */
    public function la_colonne_users_role_accepte_enseignant(): void
    {
        // Le bug SQL 42S22 était : users.role ENUM legacy sans 'enseignant'.
        // Vérifie que la migration 2026_07_04_120000_change_users_role_to_string
        // a bien passé la colonne en VARCHAR.
        $user = User::create([
            'uuid'       => \Illuminate\Support\Str::uuid()->toString(),
            'nom'        => 'TEST',
            'prenoms'    => 'Enseignant',
            'email'      => 'test-ens@example.com',
            'login'      => 'test-ens',
            'full_login' => 'test-ens',
            'password'   => bcrypt('secret'),
            'role'       => 'enseignant', // ← ne doit plus tronquer
            'statut'     => 'actif',
        ]);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'role' => 'enseignant',
        ]);
    }

    /** @test */
    public function les_relations_matieres_niveaux_pointent_vers_parametrage(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $e = Enseignant::create([
            'user_id'   => $user->id,
            'matricule' => 'ENS-REL',
            'nom'       => 'A', 'prenoms' => 'B',
            'statut'    => 'actif',
        ]);

        // Depuis la refonte, matieres() pointe vers MatiereUnite, niveaux() vers NiveauEtude
        $this->assertStringContainsString(
            'MatiereUnite',
            get_class($e->matieres()->getRelated())
        );
        $this->assertStringContainsString(
            'NiveauEtude',
            get_class($e->niveaux()->getRelated())
        );
    }

    /** @test */
    public function le_matricule_enseignant_est_unique(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Enseignant::create([
            'user_id' => $user1->id, 'matricule' => 'UNIQ-ENS', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        Enseignant::create([
            'user_id' => $user2->id, 'matricule' => 'UNIQ-ENS', 'nom' => 'C', 'prenoms' => 'D', 'statut' => 'actif',
        ]);
    }
}
