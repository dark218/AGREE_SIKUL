<?php

namespace Modules\Parametrage\Tests\Unit;

use Modules\Parametrage\Entities\Fichier;
use Tests\BaseTestCase;

class FichierTest extends BaseTestCase
{
    public function test_fichier_creation()
    {
        $fichier = Fichier::factory()->create([
            'nom' => 'test.pdf',
            'source' => '/uploads/test.pdf'
        ]);

        $this->assertDatabaseHas('fichier', [
            'nom' => 'test.pdf',
            'source' => '/uploads/test.pdf'
        ]);
    }

    public function test_fichier_toggle_active()
    {
        $fichier = Fichier::factory()->create(['active' => 1]);

        $fichier->toggleActive();

        $this->assertEquals(0, $fichier->fresh()->active);
    }
}
