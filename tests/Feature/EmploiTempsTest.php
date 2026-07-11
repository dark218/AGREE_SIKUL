<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Enseignant;
use Modules\Academique\Entities\EmploiTemps;
use Modules\Parametrage\Entities\Classe;
use Modules\Parametrage\Entities\Ecole;
use Modules\Parametrage\Entities\MatiereUnite;
use Tests\TestCase;

/**
 * Tests fonctionnels — Emploi du temps (refonte cadre + créneaux) :
 *  - création d'un cadre + créneaux via la route ;
 *  - cascade : le contexte (école…) est repris de la classe ;
 *  - détection de conflits d'horaires (chevauchement + enseignant occupé).
 */
class EmploiTempsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware();
    }

    private function makeClasse(): Classe
    {
        // ecole_id est nullable : pas besoin de toute la chaîne géographique.
        return Classe::create([
            'nom'     => '6ème A',
            'libelle' => '6ème A',
            'statut'  => 'actif',
        ]);
    }

    private function makeMatiere(): MatiereUnite
    {
        return MatiereUnite::create(['code' => 'MATH', 'libelle' => 'Mathématiques']);
    }

    private function makeEnseignant(): Enseignant
    {
        $u = User::factory()->create();
        return Enseignant::create(['user_id' => $u->id, 'nom' => 'PROF', 'prenoms' => 'Test', 'statut' => 'actif']);
    }

    /** @test */
    public function un_emploi_du_temps_est_cree_avec_ses_creneaux_et_herite_du_contexte_classe(): void
    {
        $classe = $this->makeClasse();
        $mat = $this->makeMatiere();
        $ens = $this->makeEnseignant();

        $this->post(route('academique.emplois_du_temps.store'), [
            'classe_id'  => $classe->id,
            'libelle'    => 'EDT 6ème A',
            'date_debut' => '2026-09-01',
            'date_fin'   => '2026-12-20',
            'etat'       => 'actif',
            'creneaux'   => [
                ['jour' => 'lundi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'matiere_id' => $mat->id, 'enseignant_id' => $ens->id, 'salle' => 'A1'],
                ['jour' => 'mardi', 'heure_debut' => '10:00', 'heure_fin' => '12:00', 'matiere_id' => $mat->id, 'enseignant_id' => $ens->id, 'salle' => 'A2'],
            ],
        ])->assertSessionHasNoErrors();

        // Cadre créé + contexte hérité de la classe (école)
        $this->assertDatabaseHas('emplois_temps', [
            'classe_id' => $classe->id,
            'libelle'   => 'EDT 6ème A',
            'ecole_id'  => $classe->ecole_id,
            'etat'      => 'actif',
        ]);

        $emploi = EmploiTemps::where('classe_id', $classe->id)->first();
        $this->assertNotNull($emploi);
        $this->assertSame(2, $emploi->creneaux()->count());
    }

    /** @test */
    public function deux_creneaux_du_meme_jour_qui_se_chevauchent_sont_refuses(): void
    {
        $classe = $this->makeClasse();
        $ens = $this->makeEnseignant();

        $this->post(route('academique.emplois_du_temps.store'), [
            'classe_id' => $classe->id,
            'libelle'   => 'EDT conflit interne',
            'etat'      => 'actif',
            'creneaux'  => [
                ['jour' => 'lundi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'enseignant_id' => $ens->id],
                ['jour' => 'lundi', 'heure_debut' => '09:00', 'heure_fin' => '11:00', 'enseignant_id' => $ens->id],
            ],
        ])->assertSessionHasErrors('creneaux');

        $this->assertDatabaseMissing('emplois_temps', ['libelle' => 'EDT conflit interne']);
    }

    /** @test */
    public function un_enseignant_deja_occupe_sur_un_autre_emploi_est_refuse(): void
    {
        $classeA = $this->makeClasse();
        $classeB = $this->makeClasse();
        $ens = $this->makeEnseignant();

        // 1er emploi du temps : le prof enseigne lundi 08:00–10:00
        $this->post(route('academique.emplois_du_temps.store'), [
            'classe_id' => $classeA->id,
            'libelle'   => 'EDT A',
            'etat'      => 'actif',
            'creneaux'  => [
                ['jour' => 'lundi', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'enseignant_id' => $ens->id],
            ],
        ])->assertSessionHasNoErrors();

        // 2e emploi du temps (autre classe) : même prof, lundi 09:00–11:00 → conflit
        $this->post(route('academique.emplois_du_temps.store'), [
            'classe_id' => $classeB->id,
            'libelle'   => 'EDT B',
            'etat'      => 'actif',
            'creneaux'  => [
                ['jour' => 'lundi', 'heure_debut' => '09:00', 'heure_fin' => '11:00', 'enseignant_id' => $ens->id],
            ],
        ])->assertSessionHasErrors('creneaux');

        $this->assertDatabaseMissing('emplois_temps', ['libelle' => 'EDT B']);
    }
}
