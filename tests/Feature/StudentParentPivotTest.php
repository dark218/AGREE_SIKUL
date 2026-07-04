<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academique\Entities\Apprenant;
use Modules\Personnel\Entities\StudentParent;
use Tests\TestCase;

/**
 * Tests spécifiques du pivot `apprenant_parent` (relation N-N).
 * Vérifie l'intégrité des métadonnées : lien_parente + est_principal.
 */
class StudentParentPivotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function le_pivot_stocke_lien_parente_et_est_principal(): void
    {
        $this->actingAs(User::factory()->create());

        $parent = StudentParent::create(['pere_nom' => 'X', 'pere_prenoms' => 'Y', 'etat' => 'actif']);
        $a1 = Apprenant::create(['matricule' => 'PIV-01', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif']);
        $a2 = Apprenant::create(['matricule' => 'PIV-02', 'nom' => 'C', 'prenoms' => 'D', 'statut' => 'actif']);

        $parent->apprenants()->sync([
            $a1->id => ['lien_parente' => 'pere',         'est_principal' => true],
            $a2->id => ['lien_parente' => 'tuteur_legal', 'est_principal' => false],
        ]);

        $refresh = $parent->fresh()->apprenants;
        $found1 = $refresh->firstWhere('id', $a1->id);
        $found2 = $refresh->firstWhere('id', $a2->id);

        $this->assertSame('pere', $found1->pivot->lien_parente);
        $this->assertTrue((bool) $found1->pivot->est_principal);

        $this->assertSame('tuteur_legal', $found2->pivot->lien_parente);
        $this->assertFalse((bool) $found2->pivot->est_principal);
    }

    /** @test */
    public function un_meme_apprenant_ne_peut_etre_liee_deux_fois_au_meme_parent(): void
    {
        $this->actingAs(User::factory()->create());

        $parent = StudentParent::create(['pere_nom' => 'X', 'pere_prenoms' => 'Y', 'etat' => 'actif']);
        $a = Apprenant::create(['matricule' => 'UNIQ-PIV', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif']);

        // sync remplace : après 2 appels, une seule ligne pivot doit exister
        $parent->apprenants()->sync([$a->id => ['lien_parente' => 'pere', 'est_principal' => true]]);
        $parent->apprenants()->sync([$a->id => ['lien_parente' => 'tuteur_legal', 'est_principal' => true]]);

        $this->assertSame(1, \DB::table('apprenant_parent')
            ->where('parent_id', $parent->id)
            ->where('apprenant_id', $a->id)
            ->count()
        );
        // Et la métadonnée est la plus récente
        $this->assertSame('tuteur_legal', \DB::table('apprenant_parent')
            ->where('parent_id', $parent->id)
            ->where('apprenant_id', $a->id)
            ->value('lien_parente')
        );
    }

    /** @test */
    public function la_relation_inverse_apprenant_parents_fonctionne(): void
    {
        $this->actingAs(User::factory()->create());

        $a = Apprenant::create(['matricule' => 'INV-01', 'nom' => 'A', 'prenoms' => 'B', 'statut' => 'actif']);
        $p1 = StudentParent::create(['pere_nom' => 'P1', 'pere_prenoms' => 'X', 'etat' => 'actif']);
        $p2 = StudentParent::create(['mere_nom' => 'P2', 'mere_prenoms' => 'Y', 'etat' => 'actif']);

        $p1->apprenants()->sync([$a->id => ['lien_parente' => 'pere', 'est_principal' => true]]);
        $p2->apprenants()->sync([$a->id => ['lien_parente' => 'mere', 'est_principal' => false]]);

        $this->assertCount(2, $a->fresh()->parents);
    }
}
