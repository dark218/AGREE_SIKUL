<?php

namespace Modules\Parametrage\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\FournisseurPaiement;
use Tests\BaseTestCase;

class FournisseurPaiementControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    public function test_can_view_fournisseur_paiements_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('fournisseurpaiement.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_fournisseur_paiement()
    {
        $this->actingAs($this->user);

        $fournisseurData = [
            'nom' => 'MTN Money',
            'code' => 'MTN',
            'type' => 'mm'
        ];

        $response = $this->post(route('fournisseurpaiement.store'), $fournisseurData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('fournisseurs_paiement', [
            'nom' => 'MTN Money',
            'code' => 'MTN'
        ]);
    }

    public function test_can_update_fournisseur_paiement()
    {
        $this->actingAs($this->user);
        $fournisseur = FournisseurPaiement::factory()->create();

        $updateData = [
            'nom' => 'Wave',
            'code' => 'WAVE',
            'type' => 'mm'
        ];

        $response = $this->put(route('fournisseurpaiement.update', $fournisseur->id), $updateData);

        $response->assertStatus(302);

        $fournisseur->refresh();
        $this->assertEquals('Wave', $fournisseur->nom);
        $this->assertEquals('WAVE', $fournisseur->code);
    }

    public function test_can_soft_delete_fournisseur_paiement()
    {
        $this->actingAs($this->user);
        $fournisseur = FournisseurPaiement::factory()->create();

        $response = $this->put(route('fournisseurpaiement.statut', $fournisseur->id));

        $response->assertRedirect(route('fournisseurpaiement.index'));
        $this->assertSoftDeleted('fournisseurs_paiement', ['id' => $fournisseur->id]);
    }
}
