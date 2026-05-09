<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\Zone;
use Tests\BaseTestCase;

class ZoneTest extends BaseTestCase
{
    public function test_zone_creation()
    {
        $zone = Zone::factory()->create([
            'libelle' => 'Zone Ouest',
            'description' => 'Zone géographique ouest'
        ]);

        $this->assertDatabaseHas('zones', [
            'libelle' => 'Zone Ouest',
            'description' => 'Zone géographique ouest'
        ]);
    }

    public function test_zone_belongs_to_pays()
    {
        $zone = Zone::factory()->create();

        $this->assertInstanceOf(\Modules\Parametrage\Entities\Pays::class, $zone->pays);
    }
}
