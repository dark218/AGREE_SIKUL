<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\AbsenceApprenant;
use Modules\Academique\Entities\Apprenant;
use Modules\Parametrage\Entities\Classe;
use Tests\TestCase;

/**
 * Fonctionnalité « Absence Apprenant » : création et cascade classe.
 */
class AbsenceApprenantTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->withoutMiddleware();
    }

    /** @test */
    public function une_absence_apprenant_est_creee(): void
    {
        $classe = Classe::create(['nom' => '6ème A', 'libelle' => '6ème A', 'statut' => 'actif']);
        $apprenant = Apprenant::create([
            'matricule' => 'MAT-1', 'nom' => 'ALPHA', 'prenoms' => 'Un',
            'classe_id' => $classe->id, 'statut' => 'actif',
        ]);

        $this->post(route('academique.absences_apprenants.store'), [
            'apprenant_id' => $apprenant->id,
            'classe_id'    => $classe->id,
            'date_debut'   => '2026-10-01T08:00',
            'date_fin'     => '2026-10-01T10:00',
            'motif'        => 'Maladie',
            'statut'       => 'en_attente',
            'etat'         => 'actif',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('absences_apprenants', [
            'apprenant_id' => $apprenant->id,
            'classe_id'    => $classe->id,
            'statut'       => 'en_attente',
        ]);
    }

    /** @test */
    public function la_date_fin_doit_suivre_la_date_debut(): void
    {
        $classe = Classe::create(['nom' => '5ème', 'libelle' => '5ème', 'statut' => 'actif']);
        $apprenant = Apprenant::create([
            'matricule' => 'MAT-2', 'nom' => 'BETA', 'prenoms' => 'Deux',
            'classe_id' => $classe->id, 'statut' => 'actif',
        ]);

        $this->post(route('academique.absences_apprenants.store'), [
            'apprenant_id' => $apprenant->id,
            'date_debut'   => '2026-10-02T10:00',
            'date_fin'     => '2026-10-02T08:00', // avant le début
            'statut'       => 'en_attente',
        ]);

        $this->assertDatabaseMissing('absences_apprenants', ['apprenant_id' => $apprenant->id]);
    }
}
