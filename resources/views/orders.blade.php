<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos | Tienda De Ropa</title>
    <link rel="icon" href="{{ asset('image/logo.png') }}" type="image/x-png">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="{{ asset('js/main.js') }}"></script>
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

        
    </header>

    <main>
        <!--
        <section class="orders-section">
            <a href="#" id="orders-back-link" class="orders-back-link">← Volver</a>
            <h2 class="orders-title">Mis Pedidos</h2>

            <div id="orders-content">
                <p class="loading-text" style="text-align: center; width: 100%;">Cargando tus pedidos...</p>
            </div>
        </section>
        -->

        <section class="orders-section">
            <a href="#" id="orders-back-link" class="orders-back-link">
                <span class="orders-back-arrow" aria-hidden="true">←</span>
                <span>Volver a la tienda</span>
            </a>

            <div class="orders-layout">
                <!-- Menú visual lateral: no modifica ninguna función -->
                <aside class="account-sidebar" aria-label="Navegación de cuenta">
                    <p class="account-sidebar-label">MI CUENTA</p>

                    <div class="account-sidebar-list">
                        <div class="account-sidebar-item">
                            <span class="account-sidebar-icon" aria-hidden="true">◯</span>
                            <span>Perfil</span>
                        </div>

                        <div class="account-sidebar-item active">
                            <span class="account-sidebar-icon" aria-hidden="true">▣</span>
                            <span>Mis pedidos</span>
                        </div>

                        <div class="account-sidebar-item">
                            <span class="account-sidebar-icon" aria-hidden="true">⌖</span>
                            <span>Direcciones</span>
                        </div>
                    </div>
                </aside>

                <!-- Tu contenido dinámico original se conserva -->
                <div class="orders-main-content">
                    <div class="orders-heading">
                        <div>
                
                            <h2 class="orders-title">Mis pedidos</h2>
                        </div>
                    </div>

                    <div id="orders-content">
                        <p class="loading-text" style="text-align: center; width: 100%;">
                            Cargando tus pedidos...
                        </p>
                    </div>
                </div>
            </div>
        </section>


    </main>

    <footer>

        <div class="container-copyright">
            <div class="container-logo">
                <a href="/" class="store-title" aria-label="TECPÁN — Inicio">
                    <img
                    src="{{ asset('Auth/images/LogoTiendaRopa.png') }}"
                    alt="TECPÁN"
                    class="store-logo"
                    >
                </a>
            </div>
            <p>Todos los derechos reservados</p>
        </div>





        <nav class="footer-nav">
            <ul>
                <li><a href="#">Inicio</a></li>
                <li><a href="#">Colección</a></li>
                <li><a href="#">Destacados</a></li>
                <li><a href="#">Contacto</a></li>
            </ul>
        </nav>
    </footer>

    <!-- Drawer del carrito, igual que en las demás páginas -->
    <div class="cart-overlay" id="cart-overlay"></div>
    <aside class="cart-drawer" id="cart-drawer">
        <div class="cart-drawer-header">
            <h2 id="cart-drawer-title">Su carrito</h2>
            <button class="cart-drawer-close" id="cart-drawer-close" aria-label="Cerrar carrito">&times;</button>
        </div>
        <div class="cart-drawer-items" id="cart-drawer-items">
            <p class="loading-text">Cargando...</p>
        </div>
        <div class="cart-drawer-footer" id="cart-drawer-footer"></div>
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