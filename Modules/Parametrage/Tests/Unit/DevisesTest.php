<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\Devises;
use Modules\Parametrage\Entities\PaysDevise;
use Tests\BaseTestCase;

class DevisesTest extends BaseTestCase
{

    public function test_devise_creation()
    {
        $devise = Devises::factory()->create([
            'code' => 'USD',
            'symbole' => '$',
            'libelle' => 'US Dollar'
        ]);

        $this->assertDatabaseHas('devises', [
            'code' => 'USD',
            'symbole' => '$',
            'libelle' => 'US Dollar'
        ]);
    }

    public function test_devise_has_many_pays_devises()
    {
        $devise = Devises::factory()->create();
        $paysDevise = PaysDevise::factory()->create(['devise_id' => $devise->id]);

        $this->assertTrue($devise->paysDevises->contains($paysDevise));
    }
}
