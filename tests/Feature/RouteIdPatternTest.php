<?php

namespace Tests\Feature;

use Tests\TestCase;

class RouteIdPatternTest extends TestCase
{
    public function test_un_id_que_no_es_numero_responde_404_y_no_500(): void
    {
        $this->getJson('/api/auctions/product/null/bids')->assertNotFound();
        $this->getJson('/api/auctions/product/null/status')->assertNotFound();
        $this->getJson('/api/products/abc')->assertNotFound();
    }
}
