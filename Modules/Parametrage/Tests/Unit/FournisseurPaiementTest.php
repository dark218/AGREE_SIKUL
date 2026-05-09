<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\FournisseurPaiement;
use Tests\BaseTestCase;

class FournisseurPaiementTest extends BaseTestCase
{
    public function test_fournisseur_paiement_creation()
    {
        $fournisseur = FournisseurPaiement::factory()->create([
            'nom' => 'Orange Money',
            'code' => 'OM',
            'type' => 'mm'
        ]);

        $this->assertDatabaseHas('fournisseurs_paiement', [
            'nom' => 'Orange Money',
            'code' => 'OM',
            'type' => 'mm'
        ]);
    }

    public function test_fournisseur_paiement_toggle_active()
    {
        $fournisseur = FournisseurPaiement::factory()->create(['statut' => 'actif']);

        $fournisseur->toggleActive();

        $this->assertEquals('inactif', $fournisseur->fresh()->statut);
    }
}
