<?php

namespace Modules\GestionStock\Tests\Unit;

use Modules\GestionStock\Entities\TransfertStock;
use Tests\BaseTestCase;

class TransfertStockTest extends BaseTestCase
{
    public function test_transfert_stock_creation()
    {
        $transfert = TransfertStock::factory()->create([
            'reference' => 'TRF-001',
            'statut' => 'en_cours'
        ]);

        $this->assertDatabaseHas('transferts_stock', [
            'reference' => 'TRF-001',
            'statut' => 'en_cours'
        ]);
    }
}
