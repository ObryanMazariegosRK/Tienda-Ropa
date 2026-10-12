{{-- Pie de página de la tienda: se incluye en todas las vistas públicas --}}
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
        <li><a href="{{ route('privacidad', [], false) }}">Política de privacidad</a></li>
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
