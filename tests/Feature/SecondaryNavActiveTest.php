<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecondaryNavActiveTest extends TestCase
{
    public function test_cada_pagina_marca_su_seccion_en_el_submenu(): void
    {
        $casos = ['/' => 'Tienda Principal', '/ofertas' => 'Rebajas', '/subastas' => 'Subastas'];

        foreach ($casos as $url => $seccion) {
            $html = $this->get($url)->assertOk()->getContent();

            preg_match_all('/<a [^>]*class="secondary-nav-link active"[^>]*>([^<]+)<\/a>/', $html, $activos);
            $this->assertSame([$seccion], $activos[1], "En $url debe estar activo solo \"$seccion\"");
        }
    }
}
