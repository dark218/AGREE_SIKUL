<?php

namespace Modules\Pos\Tests\Unit;

use Modules\Pos\Entities\VentePos;
use Tests\BaseTestCase;

class VentePosTest extends BaseTestCase
{
    public function test_vente_pos_creation()
    {
        $vente = VentePos::factory()->create([
            'reference' => 'VP-001',
            'total_cents' => 100000
        ]);

        $this->assertDatabaseHas('ventes_pos', [
            'reference' => 'VP-001',
            'total_cents' => 100000
        ]);
    }
}
