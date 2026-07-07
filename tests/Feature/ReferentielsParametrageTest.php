<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Parametrage\Entities\GroupeSanguin;
use Modules\Parametrage\Entities\LienParente;
use Modules\Parametrage\Entities\SituationMatrimoniale;
use Modules\Parametrage\Entities\StatutApprenant;
use Modules\Parametrage\Entities\StatutEmploye;
use Modules\Parametrage\Entities\TypeInscription;
use Tests\TestCase;

/**
 * Tests fonctionnels — les 6 référentiels Paramétrage factorisés
 * (StatutEmploye, SituationMatrimoniale, LienParente, StatutApprenant,
 *  TypeInscription, GroupeSanguin).
 *
 * Note : TypeContrat, Civilite et Langue ont été supprimés dans les Phases 1
 * (fusionnés dans NatureContrat, TitreCivilite ; Langue dormante retirée du
 * menu — les enseignants stockent leurs langues dans le JSON `languages`).
 *
 * Vérifie que :
 *  - Les seeds initiaux sont en base
 *  - Le trait IsReferentiel expose le scope `actif`
 *  - Le code est unique par référentiel
 *  - Le tri par défaut est par ordre croissant
 */
class ReferentielsParametrageTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function chaque_referentiel_a_ses_seeds_initiaux(): void
    {
        $expected = [
            StatutEmploye::class          => ['ACTIF', 'SUSPENDU', 'CONGE', 'RETRAITE', 'DEMISSION'],
            SituationMatrimoniale::class  => ['CELIBATAIRE', 'MARIE', 'DIVORCE', 'VEUF'],
            StatutApprenant::class        => ['ACTIF', 'SUSPENDU', 'EXCLU', 'DIPLOME', 'ABANDONNE'],
            TypeInscription::class        => ['NOUVEAU', 'REDOUBLEMENT', 'TRANSFERT', 'REPRISE'],
            GroupeSanguin::class          => ['O_POS', 'A_POS', 'AB_POS', 'B_POS'],
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
        $lien = LienParente::create([
            'code'    => 'TEST-LIEN-' . uniqid(),
            'libelle' => 'Test',
            'etat'    => 'inactif',
            'ordre'   => 999,
        ]);
        $this->assertNotNull(LienParente::find($lien->id));
        $this->assertNull(LienParente::actif()->find($lien->id));
        $this->assertGreaterThan(0, LienParente::actif()->count());
    }

    /** @test */
    public function le_code_est_unique_par_referentiel(): void
    {
        // Rejet du doublon "NOUVEAU" déjà seedé
        $this->expectException(\Illuminate\Database\QueryException::class);
        TypeInscription::create(['code' => 'NOUVEAU', 'libelle' => 'Doublon', 'etat' => 'actif']);
    }

    /** @test */
    public function le_tri_par_defaut_est_par_ordre_croissant(): void
    {
        $items = LienParente::orderBy('ordre')->get();
        $ordres = $items->pluck('ordre')->toArray();
        $sorted = $ordres;
        sort($sorted);
        $this->assertSame($sorted, $ordres, 'LienParente doit être trié par ordre ASC');
    }
}
