<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\Banque;
use Modules\Parametrage\Entities\Pays;
use Tests\BaseTestCase;

class BanqueTest extends BaseTestCase
{

    public function test_banque_creation()
    {
        $pays = Pays::factory()->create();

        $banque = Banque::factory()->create([
            'pays_id' => $pays->id,
            'nom' => 'Test Bank',
            'is_active' => true
        ]);

        $this->assertDatabaseHas('banques', [
            'nom' => 'Test Bank',
            'pays_id' => $pays->id,
            'is_active' => true
        ]);
    }

    public function test_banque_belongs_to_pays()
    {
        $pays = Pays::factory()->create();
        $banque = Banque::factory()->create(['pays_id' => $pays->id]);

        $this->assertInstanceOf(Pays::class, $banque->pays);
        $this->assertEquals($pays->id, $banque->pays->id);
    }

    public function test_toggle_active_method()
    {
        $banque = Banque::factory()->create(['is_active' => true]);

        $banque->toggleActive();

        $this->assertFalse($banque->fresh()->is_active);

        $banque->toggleActive();

        $this->assertTrue($banque->fresh()->is_active);
    }
}
