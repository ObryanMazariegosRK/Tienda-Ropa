<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos | Amerishop</title>
    <link rel="icon" href="{{ asset('Auth/images/Prenda.png') }}" type="image/x-png">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                        <div class="account-sidebar-item" id="sidebarProfileBtn">
                            <span class="account-sidebar-icon" aria-hidden="true">◯</span>
                            <span>Perfil</span>
                        </div>

                        <div class="account-sidebar-item active">
                            <span class="account-sidebar-icon" aria-hidden="true">▣</span>
                            <span>Mis pedidos</span>
                        </div>

                        <div class="account-sidebar-item" id="sidebarAddressesBtn">
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

     <footer class="store-footer">
        <div class="store-footer-main">
            <!-- Marca -->
            <div class="footer-brand">
                <a href="/" class="footer-logo-link" aria-label="Amerishop — Inicio">
                    <img
                        src="{{ asset('Auth/images/LogoTiendaRopa.png') }}"
                        alt="Amerishop"
                        class="footer-store-logo"
                    >
                </a>

                <p class="footer-brand-text">
                    © 2026 Amerishop. Todos los derechos reservados.
                </p>
            </div>

            <!-- Contacto -->
            <section class="footer-contact" aria-labelledby="footer-contact-title">
                <h3 id="footer-contact-title">Contáctanos</h3>

                <ul class="footer-contact-list">
                    <li>
                        <span class="footer-contact-icon" aria-hidden="true">
                            <!-- Ubicación -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                                <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 14.314 0z" />
                            </svg>
                        </span>
                        <span>Tecpán, Chimaltenango,<br>Guatemala</span>
                    </li>

                    <li>
                        <span class="footer-contact-icon" aria-hidden="true">
                            <!-- Correo -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 7a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-10z" />
                                <path d="M3 7l9 6l9 -6" />
                            </svg>
                        </span>
                        <a href="mailto:amerishop@gmail.com">amerishop@gmail.com</a>
                    </li>

                    <li>
                        <span class="footer-contact-icon" aria-hidden="true">
                            <!-- Horario -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" />
                                <path d="M12 7v5l3 3" />
                            </svg>
                        </span>
                        <span>
                            Lun–Vie: 8:00 AM – 6:00 PM<br>
                            Sáb: 9:00 AM – 2:00 PM
                        </span>
                    </li>
                </ul>
            </section>

            <!-- Navegación -->
            <nav class="footer-links" aria-label="Enlaces del pie de página">
                <h3>Explora</h3>
                <ul>
                    <li><a href="/">Inicio</a></li>
                    <li><a href="/ofertas">Ofertas</a></li>
                    <li><a href="/subastas">Subastas</a></li>
                    <li><a href="/mis-pedidos">Mis pedidos</a></li>
                </ul>
            </nav>

            <!-- Redes -->
            <section class="footer-social" aria-labelledby="footer-social-title">
                <h3 id="footer-social-title">Síguenos</h3>


                <a href="https://www.instagram.com/amerishopgt502/" target="_blank" rel="noopener noreferrer" class="instagram-link" aria-label="Visitar Instagram de Amerishop">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M4 4m0 4a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4z" />
                        <path d="M12 8a4 4 0 1 0 0 8a4 4 0 0 0 0 -8z" />
                        <path d="M16.5 7.5l0 .01" />
                    </svg>
                    <span>@amerishopgt502</span>
                </a>
                
                    
            </section>
        </div>
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

    <!-- Modal de cuenta (Perfil / Direcciones) -->
    <div class="account-modal-overlay" id="account-modal-overlay"></div>
    <div class="account-modal" id="account-modal">
        <button class="account-modal-close" id="account-modal-close" aria-label="Cerrar">&times;</button>
        <div id="account-modal-content"></div>
    </div>






</body>
</html>