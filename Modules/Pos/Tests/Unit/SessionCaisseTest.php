<?php

namespace Modules\Pos\Tests\Unit;

use Modules\Pos\Entities\SessionCaisse;
use Tests\BaseTestCase;

class SessionCaisseTest extends BaseTestCase
{
    public function test_session_caisse_creation()
    {
        $session = SessionCaisse::factory()->create([
            'reference' => 'SC-001',
            'fond_ouverture_cents' => 500000
        ]);

        $this->assertDatabaseHas('sessions_caisse', [
            'reference' => 'SC-001',
            'fond_ouverture_cents' => 500000
        ]);
    }
}
