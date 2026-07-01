<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda De Ropa</title>
    <link rel="icon" href="{{ asset('image/logo.png') }}" type="image/x-png">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="{{ asset('js/main.js') }}"></script>
</head>
<body>

    <header>
        <div class="header-top">
            <div class="mobile-menu-btn" id="mobile-menu-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-menu-2">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 6l16 0" />
                    <path d="M4 12l16 0" />
                    <path d="M4 18l16 0" />
                </svg>
            </div>

            <div class="container-logo">
                <a href="/" class="store-title">SENDRE</a>
            </div>

            <div class="header-icons">
                <a href="/register" class="icon-link" title="Crear Cuenta">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-user-plus">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M16 19h6" /><path d="M19 16v6" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
                    </svg>
                </a>


                <a href="/cart" class="icon-link" title="Ver Carrito">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z" /><path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                    </svg>
                </a>
            </div>
        </div>

        <nav class="main-nav" id="main-nav">
            <ul class="nav-categories" id="nav-categories">
                </ul>
        </nav>
    </header>




    <main>
        <!--BANNER-->
        <<section class="hero-section" id="hero-banner-container">
            <div class="loading-banner">Cargando campaña...</div>
        </section>
        
        <section class="gallery-section">
            <ul class="gallery-lists">
                <li data-category="men">HOMBRES</li>
                <li data-category="minimal" class="active">COLECCIÓN MINIMALISTA</li>
                <li data-category="women">MUJERES</li>
            </ul>
            <div class="gallery-container">
                <div class="gallery-layout" data-category="men" id="layout-1">
                    <div class="gallery-text gallery-first-text">
                        <h3 class="gallery-subtitle">Ropa Para Hombre</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">VER COLECCIÓN</a>
                    </div>
                    <img src="{{ asset('image/collection-1.jpg') }}" alt="Colección 1">
                    <img src="{{ asset('image/collection-2.jpg') }}" alt="Colección 2">
                    <img src="{{ asset('image/collection-3.jpg') }}" alt="Colección 3" class="gallery-last-image">
                    <div class="gallery-text gallery-last-text">
                        <h3 class="gallery-subtitle">¿Por Qué Minimalista?</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">LEER LA HISTORIA</a>
                    </div>
                </div>
                <div class="gallery-layout" data-category="minimal" id="layout-2">
                    <div class="gallery-text gallery-first-text">
                        <h3 class="gallery-subtitle">La Colección</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">VER COLECCIÓN</a>
                    </div>
                    <img src="{{ asset('image/collection-1.jpg') }}" alt="Colección 1">
                    <img src="{{ asset('image/collection-2.jpg') }}" alt="Colección 2">
                    <img src="{{ asset('image/collection-3.jpg') }}" alt="Colección 3" class="gallery-last-image">
                    <div class="gallery-text gallery-last-text">
                        <h3 class="gallery-subtitle">¿Por Qué Minimalista?</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">LEER LA HISTORIA</a>
                    </div>
                </div>
                <div class="gallery-layout" data-category="women" id="layout-3">
                    <div class="gallery-text gallery-first-text">
                        <h3 class="gallery-subtitle">Ropa Para Mujeres</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">VER COLECCIÓN</a>
                    </div>
                    <img src="{{ asset('image/collection-1.jpg') }}" alt="Colección 1">
                    <img src="{{ asset('image/collection-2.jpg') }}" alt="Colección 2">
                    <img src="{{ asset('image/collection-3.jpg') }}" alt="Colección 3" class="gallery-last-image">
                    <div class="gallery-text gallery-last-text">
                        <h3 class="gallery-subtitle">¿Por Qué Minimalista?</h3>
                        <p class="gallery-description">Lorem ipsum dolor sit amet consectetur adipisicing elit. Officiis, exercitationem error labore eveniet, at nulla reiciendis nesciunt inventore deleniti corrupti, saepe dignissimos unde! Error reprehenderit deserunt, similique illo numquam quas!</p>
                        <a href="#" class="link-default">LEER LA HISTORIA</a>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="products-section">
            <h2>PRODUCTOS DESTACADOS</h2>
            <div class="products-container" id="products-container">
                <p class="loading-text" style="text-align: center; width: 100%;">Cargando catálogo...</p>
            </div>
        </section>
        
        <section class="story-section">
            <div class="story-container">
                <div class="story-container-text">
                    <h3>Nuestra Historia</h3>
                    <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. In necessitatibus natus ab officia commodi sunt molestias nostrum saepe quod veritatis repellendus similique perferendis impedit, animi, earum veniam provident nihil numquam.</p>
                </div>
            </div>
            <div class="story-container-image">
                <img src="{{ asset('image/story-image.jpg') }}" alt="Nuestra historia" class="story-image">
            </div>
        </section>
        
        <section class="subscriber-section">
            <p>Suscribete y mantente al día con nuestros últimos productos</p>
            <div class="container-input">
                <input type="email" placeholder="ejemplo@gmail.com" class="subscriber-input">
                <button class="subscriber-btn">ENVIAR</button>
                <p class="subscriber-thanks">¡Gracias por suscribirte!</p>
            </div>
        </section>
    </main>
    
    <footer>
        <div class="container-copyright">
            <div class="footer-logo">
                <img src="{{ asset('image/logo.png') }}" alt="Logo Tienda">
            </div>
            <p>Sendre |. Todos los derechos reservados</p>
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
</body>
</html>