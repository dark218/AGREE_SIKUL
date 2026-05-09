<?php

namespace Modules\Business\Tests\Unit;

use Tests\BaseTestCase;
use Modules\Business\Entities\Marchand;

class MarchandTest extends BaseTestCase
{
    public function test_marchand_creation()
    {
        $marchand = Marchand::factory()->create([
            'raison_sociale' => 'Test Marchand SARL',
            'identifiant_fiscal' => 'IF-TEST-001'
        ]);

        $this->assertDatabaseHas('marchands', [
            'raison_sociale' => 'Test Marchand SARL',
            'identifiant_fiscal' => 'IF-TEST-001'
        ]);
    }
}
