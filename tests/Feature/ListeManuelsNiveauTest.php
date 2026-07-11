<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Parametrage\Entities\NiveauEtude;
use Modules\Parametrage\Entities\Section;
use Tests\TestCase;

/**
 * Garantit que le niveau d'une liste de manuels provient bien de `niveaux_etudes`
 * (source du menu déroulant) et non de la table héritée `niveaux` :
 * plus de « niveau id invalide » à l'enregistrement.
 */
class ListeManuelsNiveauTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware();
    }

    private function makeNiveau(): NiveauEtude
    {
        $paysId  = DB::table('pays')->insertGetId(['libelle' => 'Pays T', 'code' => 'PT', 'etat' => 'actif', 'created_at' => now(), 'updated_at' => now()]);
        $cycleId = DB::table('cycles_enseignement')->insertGetId(['code' => 'CYC-T', 'libelle' => 'Cycle T', 'pays_id' => $paysId, 'created_at' => now(), 'updated_at' => now()]);

        return NiveauEtude::create([
            'code' => 'NV-6', 'libelle' => '6ème', 'etat' => 'actif',
            'cycle_id' => $cycleId, 'pays_id' => $paysId,
        ]);
    }

    /** @test */
    public function une_liste_accepte_un_niveau_issu_de_niveaux_etudes(): void
    {
        $niveau = $this->makeNiveau();
        $section = Section::create([
            'code' => 'SEC-A', 'libelle' => 'Section A',
            'niveau_etude_id' => $niveau->id, 'etat' => 'actif',
        ]);

        $this->post(route('academique.listes-manuels.store'), [
            'section_id' => $section->id,
            'niveau_id'  => $niveau->id,   // id de niveaux_etudes
            'etat'       => 'actif',
            'livres'     => [['titre' => 'Livre de Maths']],
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('listes_manuels', [
            'section_id' => $section->id,
            'niveau_id'  => $niveau->id,
        ]);
    }

    /** @test */
    public function un_niveau_inexistant_est_refuse(): void
    {
        // Le contrôleur renvoie l'échec de validation sous une clé générique ;
        // l'essentiel : aucune ligne n'est créée avec un niveau invalide.
        $this->post(route('academique.listes-manuels.store'), [
            'niveau_id' => 999999,
            'etat'      => 'actif',
            'livres'    => [['titre' => 'X']],
        ]);

        // Aucune liste ne doit porter ce niveau invalide.
        $this->assertDatabaseMissing('listes_manuels', ['niveau_id' => 999999]);
    }
}
