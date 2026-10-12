<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="vapid-public-key" content="{{ config('webpush.vapid.public_key') }}">
    <title>Rebajas | Amerishop</title>
    <link rel="icon" href="{{ asset('Auth/images/Prenda.png') }}" type="image/x-png">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="{{ asset('js/paginacion.js') }}"></script>
    {{-- Tienda: un archivo por función (public/js/tienda/), en este orden --}}
    <script src="{{ asset('js/tienda/comun.js') }}"></script>
    <script src="{{ asset('js/tienda/sesion.js') }}"></script>
    <script src="{{ asset('js/tienda/catalogo.js') }}"></script>
    <script src="{{ asset('js/tienda/carrito.js') }}"></script>
    <script src="{{ asset('js/tienda/subasta-ganada.js') }}"></script>
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body>

    <header>
        <div class="header-top">
            <div class="mobile-menu-btn" id="mobile-menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 6l16 0" />
                    <path d="M4 12l16 0" />
                    <path d="M4 18l16 0" />
                </svg>
            </div>

            <!--
            <div class="container-logo">
                <a href="/" class="store-title">TECPÁN</a>
            </div>
            -->

            <div class="container-logo">
                <a href="/" class="store-title" aria-label="TECPÁN — Inicio">
                    <img
                    src="{{ asset('Auth/images/LogoTiendaRopa.png') }}"
                    alt="TECPÁN"
                    class="store-logo"
                    >
                </a>
            </div>

            <div class="header-icons">
                <div id="auth-container">
                    <a href="{{ route('login') }}" class="icon-link" title="Iniciar Sesión / Registrarse">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                            <path d="M16 19h6" />
                            <path d="M19 16v6" />
                            <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                        </svg>
                    </a>
                </div>

                <button type="button" class="icon-link" id="cart-icon-btn" title="Ver Carrito" style="position: relative; background: none; border: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" /><path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                    </svg>
                    <span id="cart-count-badge" class="cart-count-badge" style="display: none;">0</span>
                </button>    
                
                <a href="/mis-pedidos" class="icon-link" title="Mis Pedidos">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M9 5H7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2" />
                        <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z" />
                        <path d="M9 12l.01 0" />
                        <path d="M13 12l2 0" />
                        <path d="M9 16l.01 0" />
                        <path d="M13 16l2 0" />
                    </svg>
                </a>                




            </div>
        </div>

        <nav class="main-nav" id="main-nav">
            <ul class="nav-categories" id="nav-categories">
                <li class="nav-item"><a href="#">Cargando categorías...</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <div class="secondary-nav">
            {{-- La clase active marca en qué sección está el cliente --}}
            <a href="/" class="secondary-nav-link {{ request()->is('/') ? 'active' : '' }}" @if(request()->is('/')) aria-current="page" @endif>Tienda Principal</a>
            <a href="/ofertas" class="secondary-nav-link {{ request()->is('ofertas') ? 'active' : '' }}" @if(request()->is('ofertas')) aria-current="page" @endif>Rebajas</a>
            <a href="/subastas" class="secondary-nav-link {{ request()->is('subastas') ? 'active' : '' }}" @if(request()->is('subastas')) aria-current="page" @endif>Subastas</a>
        </div>

        <section class="products-section">
            <h2>REBAJAS</h2>
            <div class="products-container" id="ofertas-container">
                @include('partials.tarjetas-cargando', ['texto' => 'Cargando ofertas...'])
            </div>
            <nav class="pagination" id="pagination" aria-label="Paginación de ofertas"></nav>
        </section>
    </main>

    @include('partials.store-footer')

    <!-- Fondo oscuro semitransparente -->
    <div class="cart-overlay" id="cart-overlay"></div>

    <!-- Panel lateral del carrito -->
    <aside class="cart-drawer" id="cart-drawer">
        <div class="cart-drawer-header">
            <h2 id="cart-drawer-title">Su carrito</h2>
            <button class="cart-drawer-close" id="cart-drawer-close" aria-label="Cerrar carrito">&times;</button>
        </div>

        <div class="cart-drawer-items" id="cart-drawer-items">
            <p class="loading-text">Cargando...</p>
        </div>

        <div class="cart-drawer-footer" id="cart-drawer-footer">
            <!-- Se llena dinámicamente por JS -->
        </div>
    </aside>

    <!--Para las direcciones-->
    <!-- Overlay + modal de checkout -->
    <div class="checkout-overlay" id="checkout-overlay"></div>
    <div class="checkout-modal" id="checkout-modal">
        <button class="checkout-modal-close" id="checkout-modal-close" aria-label="Cerrar">&times;</button>
        <div id="checkout-modal-content"></div>
    </div>








</body>
</html>