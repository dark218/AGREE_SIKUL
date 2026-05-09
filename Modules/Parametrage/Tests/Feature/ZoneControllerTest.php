<?php

namespace Modules\Parametrage\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\Zone;
use Tests\BaseTestCase;

class ZoneControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    public function test_can_view_zones_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('zone.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_zone()
    {
        $this->actingAs($this->user);

        $zoneData = [
            'libelle' => 'Zone Nord',
            'description' => 'Zone géographique du nord',
            'pays_id' => \Modules\Parametrage\Entities\Pays::factory()->create()->id
        ];

        $response = $this->post(route('zone.store'), $zoneData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('zones', [
            'libelle' => 'Zone Nord',
            'description' => 'Zone géographique du nord'
        ]);
    }

    public function test_can_update_zone()
    {
        $this->actingAs($this->user);
        $zone = Zone::factory()->create();

        $updateData = [
            'libelle' => 'Zone Sud',
            'description' => 'Zone géographique du sud',
            'pays_id' => $zone->pays_id
        ];

        $response = $this->put(route('zone.update', $zone->id), $updateData);

        $response->assertStatus(302);

        $zone->refresh();
        $this->assertEquals('Zone Sud', $zone->libelle);
        $this->assertEquals('Zone géographique du sud', $zone->description);
    }

    public function test_can_soft_delete_zone()
    {
        $this->actingAs($this->user);
        $zone = Zone::factory()->create();

        $response = $this->put(route('zone.statut', $zone->id));

        $response->assertRedirect(route('zone.index'));
        $this->assertSoftDeleted('zones', ['id' => $zone->id]);
    }
}
