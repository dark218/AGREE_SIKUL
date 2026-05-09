<?php

namespace Modules\Parametrage\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\Pays;
use Tests\BaseTestCase;

class PaysControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    public function test_can_view_pays_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('pays.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_pays()
    {
        $this->actingAs($this->user);

        $paysData = [
            'libelle' => 'Test Country',
            'code' => '+123',
            'iso' => 'TC',
            'phone_length' => 8
        ];

        $response = $this->post(route('pays.store'), $paysData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pays', [
            'libelle' => 'Test Country',
            'code' => '+123'
        ]);
    }

    public function test_can_update_pays()
    {
        $this->actingAs($this->user);
        $pays = Pays::factory()->create();

        $updateData = [
            'libelle' => 'Updated Country',
            'code' => '+999',
            'iso' => 'UC',
            'phone_length' => 10
        ];

        $response = $this->put(route('pays.update', $pays->id), $updateData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('pays', [
            'id' => $pays->id,
            'libelle' => 'Updated Country'
        ]);
    }

    public function test_can_soft_delete_pays()
    {
        $this->actingAs($this->user);
        $pays = Pays::factory()->create();

        $response = $this->put(route('pays.statut', $pays->id));

        $response->assertStatus(302);
        $this->assertSoftDeleted('pays', ['id' => $pays->id]);
    }
}
