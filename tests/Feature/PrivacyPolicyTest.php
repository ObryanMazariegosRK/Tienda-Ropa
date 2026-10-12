<?php

namespace Tests\Feature;

use Tests\TestCase;

class PrivacyPolicyTest extends TestCase
{
    public function test_la_politica_de_privacidad_esta_publicada(): void
    {
        $this->get('/politica-de-privacidad')
            ->assertOk()
            ->assertSee('Política de privacidad');
    }

    public function test_todas_las_paginas_de_la_tienda_enlazan_la_politica_en_el_pie(): void
    {
        foreach (['/', '/ofertas', '/subastas', '/mis-pedidos', '/politica-de-privacidad'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('class="store-footer"', false)
                ->assertSee('href="/politica-de-privacidad"', false);
        }
    }
}
