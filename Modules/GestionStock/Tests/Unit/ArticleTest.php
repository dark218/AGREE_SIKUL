<?php

namespace Modules\GestionStock\Tests\Unit;

use Modules\GestionStock\Entities\Article;
use Tests\BaseTestCase;

class ArticleTest extends BaseTestCase
{
    public function test_article_creation()
    {
        $article = Article::factory()->create([
            'nom' => 'Test Article',
            'sku' => 'SKU-TEST-001'
        ]);

        $this->assertDatabaseHas('articles', [
            'nom' => 'Test Article',
            'sku' => 'SKU-TEST-001'
        ]);
    }
}
