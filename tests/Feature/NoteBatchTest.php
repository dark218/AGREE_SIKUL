<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\MatiereUnite;
use Tests\TestCase;

/**
 * Tests fonctionnels — saisie EN LOT des notes :
 *  - un contexte crée UNE évaluation + UNE note par apprenant ;
 *  - la note est normalisée /20 et la mention est calculée automatiquement ;
 *  - l'API renvoie les apprenants d'une classe.
 */
class NoteBatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function makeContext(): array
    {
        $classe = Classe::create(['nom' => '6ème A', 'libelle' => '6ème A', 'statut' => 'actif']);
        $mat = MatiereUnite::firstOrCreate(['code' => 'MATH'], ['libelle' => 'Mathématiques']);
        $a1 = Apprenant::firstOrCreate(['matricule' => 'MAT-A1'], ['nom' => 'ALPHA', 'prenoms' => 'Un', 'classe_id' => $classe->id, 'statut' => 'actif']);
        $a2 = Apprenant::firstOrCreate(['matricule' => 'MAT-A2'], ['nom' => 'BETA', 'prenoms' => 'Deux', 'classe_id' => $classe->id, 'statut' => 'actif']);
        // Rattache les apprenants à la classe du test courant.
        $a1->update(['classe_id' => $classe->id]);
        $a2->update(['classe_id' => $classe->id]);
        return compact('classe', 'mat', 'a1', 'a2');
    }

    /** @test */
    public function la_saisie_en_lot_cree_une_evaluation_et_une_note_par_apprenant(): void
    {
        $this->withoutMiddleware();
        ['classe' => $classe, 'mat' => $mat, 'a1' => $a1, 'a2' => $a2] = $this->makeContext();

        $this->post(route('academique.notes.store'), [
            'classe_id'   => $classe->id,
            'matiere_id'  => $mat->id,
            'note_sur'    => 20,
            'date_examen' => '2026-10-01',
            'titre'       => 'Composition Maths T1',
            'lignes'      => [
                ['apprenant_id' => $a1->id, 'note_originale' => 16, 'observation' => 'Excellent'],
                ['apprenant_id' => $a2->id, 'note_originale' => 8],
            ],
        ])->assertSessionHasNoErrors();

        // 1 évaluation (le contexte) + 2 notes
        $this->assertDatabaseCount('evaluations', 1);
        $this->assertDatabaseHas('notes', ['apprenant_id' => $a1->id, 'note' => 16, 'mention' => 'Très Bien', 'appreciation' => 'Excellent']);
        $this->assertDatabaseHas('notes', ['apprenant_id' => $a2->id, 'note' => 8, 'mention' => 'Médiocre']);
    }

    /** @test */
    public function la_note_est_normalisee_sur_20(): void
    {
        $this->withoutMiddleware();
        ['classe' => $classe, 'a1' => $a1] = $this->makeContext();

        // note 30 sur 60 -> 10/20 -> mention Passable
        $this->post(route('academique.notes.store'), [
            'classe_id' => $classe->id,
            'note_sur'  => 60,
            'lignes'    => [['apprenant_id' => $a1->id, 'note_originale' => 30]],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', ['apprenant_id' => $a1->id, 'note' => 10, 'mention' => 'Passable']);
    }

    /** @test */
    public function l_api_renvoie_les_apprenants_de_la_classe(): void
    {
        // On garde le SubstituteBindings (route model binding) : on ne coupe que la permission.
        $this->withoutMiddleware(CheckPermission::class);
        ['classe' => $classe] = $this->makeContext();

        $this->get(route('academique.notes.apprenants_classe', $classe->id))
            ->assertOk()
            ->assertJsonCount(2, 'apprenants');
    }
}
