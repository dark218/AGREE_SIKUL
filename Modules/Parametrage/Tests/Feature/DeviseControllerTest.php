<?php

namespace Modules\Parametrage\Tests\Feature;

use App\Models\User;
use Modules\Parametrage\Entities\Devises;
use Tests\BaseTestCase;

class DeviseControllerTest extends BaseTestCase
{
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->assignRole(config('appconstants.role.superadmin'));
    }

    public function test_can_view_devises_index()
    {
        $this->actingAs($this->user);

        $response = $this->get(route('devise.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_devise()
    {
        $this->actingAs($this->user);

        $deviseData = [
            'code' => 'USD',
            'symbole' => '$',
            'libelle' => 'US Dollar',
            'decimal_point' => true
        ];

        $response = $this->post(route('devise.store'), $deviseData);

        $response->assertStatus(302);
        $this->assertDatabaseHas('devises', [
            'code' => 'USD',
            'symbole' => '$'
        ]);
    }

    public function test_can_update_devise()
    {
        $this->actingAs($this->user);
        $devise = Devises::factory()->create();

        $updateData = [
            'code' => 'EUR',
            'symbole' => '€',
            'libelle' => 'Euro',
            'decimal_point' => true
        ];

        $response = $this->put(route('devise.update', $devise->id), $updateData);

        $response->assertStatus(302);

        $devise->refresh();
        $this->assertEquals('EUR', $devise->code);
        $this->assertEquals('€', $devise->symbole);
    }

    public function test_can_soft_delete_devise()
    {
        $this->actingAs($this->user);
        $devise = Devises::factory()->create();

        $response = $this->put(route('devise.statut', $devise->id));

        $response->assertRedirect(route('devise.index'));
        $this->assertSoftDeleted('devises', ['id' => $devise->id]);
    }
}
