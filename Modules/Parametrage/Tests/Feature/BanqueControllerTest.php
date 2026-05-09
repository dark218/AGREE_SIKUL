<?php

namespace Modules\Parametrage\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\Banque;
use Modules\Parametrage\Entities\Pays;
use Tests\BaseTestCase;

class BanqueControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    public function test_can_view_banques_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('banque.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_banque()
    {
        $this->actingAs($this->user);
        $pays = Pays::factory()->create();

        $banqueData = [
            'pays_id' => $pays->id,
            'nom' => 'Test Bank',
            'code' => 'TB001',
            'bic_swift' => 'TESTBIC123',
            'is_active' => true
        ];

        $response = $this->post(route('banque.store'), $banqueData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('banques', [
            'nom' => 'Test Bank',
            'code' => 'TB001'
        ]);
    }

    public function test_can_update_banque()
    {
        $this->actingAs($this->user);
        $pays = Pays::factory()->create();
        $banque = Banque::factory()->create(['pays_id' => $pays->id]);

        $updateData = [
            'pays_id' => $pays->id,
            'nom' => 'Updated Bank Name',
            'code' => 'UB001',
            'is_active' => false
        ];

        $response = $this->put(route('banque.update', $banque->id), $updateData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('banques', [
            'id' => $banque->id,
            'nom' => 'Updated Bank Name'
        ]);
    }

    public function test_can_toggle_banque_active_status()
    {
        $this->actingAs($this->user);
        $banque = Banque::factory()->create(['is_active' => true]);

        $response = $this->put(route('banque.toggle-active', $banque->id));

        $response->assertStatus(302);
        $this->assertFalse($banque->fresh()->is_active);
    }

    public function test_can_soft_delete_banque()
    {
        $this->actingAs($this->user);
        $banque = Banque::factory()->create();

        $response = $this->put(route('banque.statut', $banque->id));

        $response->assertStatus(302);
        $this->assertSoftDeleted('banques', ['id' => $banque->id]);
    }
}
