<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Parametrage\Entities\Civilite;
use Modules\Parametrage\Entities\GroupeSanguin;
use Modules\Parametrage\Entities\Langue;
use Modules\Parametrage\Entities\LienParente;
use Modules\Parametrage\Entities\SituationMatrimoniale;
use Modules\Parametrage\Entities\StatutApprenant;
use Modules\Parametrage\Entities\StatutEmploye;
use Modules\Parametrage\Entities\TypeContrat;
use Modules\Parametrage\Entities\TypeInscription;
use Tests\TestCase;

/**
 * Tests fonctionnels — les 9 référentiels Paramétrage factorisés
 * (TypeContrat, StatutEmploye, SituationMatrimoniale, LienParente,
 * Civilite, StatutApprenant, TypeInscription, GroupeSanguin, Langue).
 *
 * Vérifie que :
 *  - Les seeds initiaux (Français/Anglais, CDI/CDD, etc.) sont en base
 *  - Le trait IsReferentiel expose le scope `actif`
 *  - Le CRUD via AbstractReferentielController fonctionne
 */
class ReferentielsParametrageTest extends TestCase
{
    use RefreshDatabase;

    private array $models = [
        TypeContrat::class,
        StatutEmploye::class,
        SituationMatrimoniale::class,
        LienParente::class,
        Civilite::class,
        StatutApprenant::class,
        TypeInscription::class,
        GroupeSanguin::class,
        Langue::class,
    ];

    /** @test */
    public function chaque_referentiel_a_ses_seeds_initiaux(): void
    {
        $expected = [
            TypeContrat::class => ['CDI', 'CDD', 'VACATAIRE', 'AUTRE'],
            StatutEmploye::class => ['ACTIF', 'SUSPENDU', 'CONGE', 'RETRAITE', 'DEMISSION'],
            SituationMatrimoniale::class => ['CELIBATAIRE', 'MARIE', 'DIVORCE', 'VEUF'],
            Civilite::class => ['MR', 'MME', 'MLLE'],
            StatutApprenant::class => ['ACTIF', 'SUSPENDU', 'EXCLU', 'DIPLOME', 'ABANDONNE'],
            TypeInscription::class => ['NOUVEAU', 'REDOUBLEMENT', 'TRANSFERT', 'REPRISE'],
            GroupeSanguin::class => ['O_POS', 'A_POS', 'AB_POS', 'B_POS'],
            Langue::class => ['FR', 'EN', 'AR'],
        ];

        foreach ($expected as $model => $codes) {
            foreach ($codes as $code) {
                $this->assertNotNull(
                    $model::where('code', $code)->first(),
                    "Le code $code manque dans $model"
                );
            }
        }
    }

    /** @test */
    public function le_scope_actif_filtre_correctement(): void
    {
        // Un enregistrement inactif ne doit pas remonter via ->actif()
        $lang = Langue::create(['code' => 'TEST-LANG', 'libelle' => 'Test', 'etat' => 'inactif', 'ordre' => 999]);
        $this->assertNotNull(Langue::find($lang->id));
        $this->assertNull(Langue::actif()->find($lang->id));
        $this->assertGreaterThan(0, Langue::actif()->count());
    }

    /** @test */
    public function le_code_est_unique_par_referentiel(): void
    {
        // Rejet du doublon "CDI" déjà seedé
        $this->expectException(\Illuminate\Database\QueryException::class);
        TypeContrat::create(['code' => 'CDI', 'libelle' => 'Doublon', 'etat' => 'actif']);
    }

    /** @test */
    public function le_tri_par_defaut_est_par_ordre_croissant(): void
    {
        $items = LienParente::all();
        $ordres = $items->pluck('ordre')->toArray();
        $sorted = $ordres;
        sort($sorted);
        $this->assertSame($sorted, $ordres, 'LienParente doit être trié par ordre ASC');
    }

    /** @test */
    public function le_crud_via_controller_fonctionne(): void
    {
        $this->actingAs(User::factory()->create());

        // CREATE
        $response = $this->post(route('parametrage.types_contrats.store'), [
            'code' => 'STAGE', 'libelle' => 'Stagiaire', 'ordre' => 10, 'etat' => 'actif',
        ]);
        $response->assertRedirect(route('parametrage.types_contrats.index'));
        $this->assertDatabaseHas('types_contrats', ['code' => 'STAGE']);

        // UPDATE
        $tc = TypeContrat::where('code', 'STAGE')->first();
        $response = $this->put(route('parametrage.types_contrats.update', $tc->id), [
            'code' => 'STAGE', 'libelle' => 'Stagiaire école', 'ordre' => 11, 'etat' => 'actif',
        ]);
        $response->assertRedirect(route('parametrage.types_contrats.index'));
        $this->assertSame('Stagiaire école', $tc->fresh()->libelle);

        // DELETE
        $this->delete(route('parametrage.types_contrats.destroy', $tc->id));
        $this->assertSoftDeleted('types_contrats', ['id' => $tc->id]);
    }
}
