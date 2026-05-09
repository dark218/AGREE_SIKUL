<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\Pays;
use Modules\Parametrage\Entities\PaysDevise;
use Tests\BaseTestCase;

class PaysTest extends BaseTestCase
{

    public function test_pays_creation()
    {
        $pays = Pays::factory()->create([
            'libelle' => 'Test Country',
            'code' => '+123',
            'iso' => 'TC'
        ]);

        $this->assertDatabaseHas('pays', [
            'libelle' => 'Test Country',
            'code' => '+123',
            'iso' => 'TC'
        ]);
    }

    public function test_pays_has_many_pays_devises()
    {
        $pays = Pays::factory()->create();
        $paysDevise = PaysDevise::factory()->create(['pays_id' => $pays->id]);

        $this->assertTrue($pays->paysDevises->contains($paysDevise));
    }

    public function test_full_phone_number_method()
    {
        $pays = Pays::factory()->create(['code' => '+225']);

        $fullNumber = $pays->fullPhoneNumber('12345678');

        $this->assertEquals('+22512345678', $fullNumber);
    }
}
