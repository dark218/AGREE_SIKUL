<?php

namespace Modules\Business\Tests\Unit;

use Tests\BaseTestCase;
use Modules\Business\Entities\CompteBancaireMarchand;

class CompteBancaireMarchandTest extends BaseTestCase
{
    public function test_compte_bancaire_marchand_creation()
    {
        $compte = CompteBancaireMarchand::factory()->create([
            'numero_compte' => '123456789',
            'nom_compte' => 'Compte Test'
        ]);

        $this->assertDatabaseHas('comptes_bancaires_marchand', [
            'numero_compte' => '123456789',
            'nom_compte' => 'Compte Test'
        ]);
    }

    public function test_compte_bancaire_marchand_toggle_active()
    {
        $compte = CompteBancaireMarchand::factory()->create(['is_active' => true]);

        $compte->toggleActive();

        $this->assertFalse($compte->fresh()->is_active);
    }
}
