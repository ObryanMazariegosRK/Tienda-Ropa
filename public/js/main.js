let allProducts = [];         // Almacena todos los productos cargados del servidor
let globalCategories = [];    // Copia en memoria para resolver la jerarquía (Padres e Hijas)
let productGalleries = {};    // Guarda el índice de imagen actual por cada producto

// Referencias del DOM globales (se resolverán al cargar el DOM)
let navCategoriesContainer;
let productsContainer;
let quickviewGalleries = {}; // Índice de imagen actual dentro del panel de vistazo rápido
const AUCTION_SNOOZE_MS = 60 * 60 * 1000;
const ESTADO_DISPONIBLE = 'available';


document.addEventListener("DOMContentLoaded", () => {

    // Guardamos la página actual como "última página de la tienda visitada",
    // para que "Mis Pedidos" pueda regresar exactamente a donde estabas.
    // Se excluye a sí misma para no sobreescribir el valor al entrar/salir de ahí
    if (!window.location.pathname.startsWith('/mis-pedidos')) {
        sessionStorage.setItem('last_store_page', window.location.pathname);
    }

    //Resolver las referencias del DOM correctamente para evitar errores de duplicación
    navCategoriesContainer = document.getElementById("nav-categories");
    productsContainer = document.getElementById("products-container")
        || document.getElementById("ofertas-container");

    //Interactividad de la sección de galerías estáticas ("Hombres", "Mujeres", "Minimalista")
    const container = document.querySelectorAll(".gallery-layout");


    const showClothes = (category) => {
        container.forEach(container => container.style.display = "none");

        let filterContainer;
        if(category === "minimal"){
            filterContainer = Array.from(container).filter(container => ["layout-2"].includes(container.id));
        } else {
            filterContainer = Array.from(container).filter(container => container.getAttribute("data-category") === category).slice(0, 1);
        }

        filterContainer.forEach(container => container.style.display = "grid");
    };

    document.querySelectorAll(".gallery-lists li").forEach(item => {
        item.addEventListener("click", (e) => {
            document.querySelectorAll(".gallery-lists li").forEach(li => li.classList.remove("active"));
            e.target.classList.add("active");

            let category = e.target.getAttribute("data-category");
            showClothes(category);
        });
    });

    const defaultItem = document.querySelector(".gallery-lists li[data-category='minimal']");
    if (defaultItem) {
        showClothes(defaultItem.getAttribute("data-category"));
    }

    //Código para el formulario de la suscripción
    const subscriberInput = document.querySelector(".subscriber-input");
    const subscriberBtn = document.querySelector(".subscriber-btn");
    const subscriberThanks = document.querySelector(".subscriber-thanks");

    let timeOutId;
    if (subscriberBtn) {
        subscriberBtn.disabled = true;
    }

    if (subscriberInput && subscriberBtn) {
        subscriberInput.addEventListener("input", () => {
            if(subscriberInput.value.length > 0){
                subscriberBtn.disabled = false;
            } else {
                subscriberBtn.disabled = true;
            }
        });

        subscriberBtn.addEventListener("click", ()=> {
            subscriberInput.value = "";
            subscriberThanks.style.display = "block";

            clearTimeout(timeOutId);

            timeOutId = setTimeout(()=> {
                subscriberThanks.style.display = "none";
            }, 3000);
        });
    }



    //Interactividad del menú móvil (Abrir y cerrar menú lateral)
    //const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    //const mainNav = document.getElementById("main-nav");

    //if (mobileMenuBtn && mainNav) {
        //mobileMenuBtn.addEventListener("click", () => {
        //    mainNav.classList.toggle("active");
        //});
    //}
    const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    const mainNav = document.getElementById("main-nav");

    /*Creamos el fondo oscuro sin modificar el archivo Blade */
    const mobileNavOverlay = document.createElement("div");
    mobileNavOverlay.id = "mobile-nav-overlay";
    document.body.appendChild(mobileNavOverlay);

    /*Garantiza que la tienda cargue con el scroll desbloqueado */
    document.body.classList.remove("mobile-menu-open");

    function cerrarMenuMovil() {
        if (!mainNav) return;

        mainNav.classList.remove("active");
        mobileNavOverlay.classList.remove("active");
        document.body.classList.remove("mobile-menu-open");
    }

    function abrirMenuMovil() {
        if (!mainNav) return;

        mainNav.classList.add("active");
        mobileNavOverlay.classList.add("active");
        document.body.classList.add("mobile-menu-open");
    }

    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener("click", () => {
            const menuAbierto = mainNav.classList.contains("active");

            if (menuAbierto) {
                cerrarMenuMovil();
            } else {
                abrirMenuMovil();
            }
        });
    }

    /* Cerrar al tocar la parte oscura de la tienda */
    mobileNavOverlay.addEventListener("click", cerrarMenuMovil);

    /* Cerrar con Escape */
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            cerrarMenuMovil();
        }
    });
    

    const heroBannerContainer = document.getElementById("hero-banner-container");
    let heroCarouselTimer = null;
    let heroCarouselIndex = 0;
    let heroGroupMedia = [];

    const loadHeroBanner = async () => {
        if (!heroBannerContainer) return;
        try {
            const response = await fetch('/api/banner');
            const data = await response.json();

            // data.data es el grupo activo, o null si el admin no ha activado ninguno
            if (data.success && data.data && data.data.media.length > 0) {
                heroGroupMedia = data.data.media.map(m => ({
                    url: m.mediaUrl,
                    type: data.data.type // 'image' o 'video', fijo para todo el grupo
                }));

                heroBannerContainer.innerHTML = `<div class="hero-carousel" id="hero-carousel"></div>`;
                mostrarSlideHero(0);
                iniciarAutoAvanceHero();
            } else {
                heroBannerContainer.innerHTML = `
                    <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
                `;
            }
        } catch (error) {
            console.error("Error al cargar el banner:", error);
            heroBannerContainer.innerHTML = `
                <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
            `;
        }
    };

    

    function mostrarSlideHero(index) {
        const carousel = document.getElementById("hero-carousel");
        if (!carousel) return;

        const item = heroGroupMedia[index];
        heroCarouselIndex = index;

        if (item.type === 'video') {
            // Sin "loop": queremos que termine y dispare el avance al siguiente
            carousel.innerHTML = `<video class="hero-media" autoplay muted playsinline src="/storage/${item.url}"></video>`;
            carousel.querySelector('video').addEventListener('ended', avanzarSlideHero);
        } else {
            carousel.innerHTML = `<img class="hero-media" src="/storage/${item.url}" alt="Banner">`;
        }
    }

    function avanzarSlideHero() {
        const siguiente = (heroCarouselIndex + 1) % heroGroupMedia.length;
        mostrarSlideHero(siguiente);
    }

    function iniciarAutoAvanceHero() {
        if (heroGroupMedia.length <= 1) return;

        // Si el grupo es de video, el avance ya lo dispara el evento 'ended'
        // dentro de mostrarSlideHero — no necesitamos temporizador aquí.
        if (heroGroupMedia[0].type === 'video') return;

        clearInterval(heroCarouselTimer);
        heroCarouselTimer = setInterval(avanzarSlideHero, 6000);
    }
























    // Lanzamos las peticiones iniciales asíncronas
    loadHeroBanner();
    loadCategories();
    cargarContadorCarritoInicial();
    actualizarAuthUI();
    verificarSubastasGanadas();
    //Para revisar cada 10 segundos mientras que la pestaña este abierta
    //setInterval(verificarSubastasGanadas, 20000);
    iniciarSondeoLigero();

    // Decide UNA sola fuente de productos según la página, evitando la carrera
    if (document.getElementById("products-container")) {
        loadFeaturedProducts();
    } else if (document.getElementById("ofertas-container")) {
        modoSoloOfertas = true;
        loadFeaturedProducts();
    }

    window.addEventListener('load', ajustarOffsetSecondaryNav);
    window.addEventListener('resize', ajustarOffsetSecondaryNav);

});

// =========================================================================
// 1. CARGAR MENÚ DE CATEGORÍAS (Sincronizado con filtros)
// =========================================================================
const loadCategories = async () => {
    try {
        const response = await fetch('/api/categories');
        let categorias = await response.json();

        if (categorias.data) {
            categorias = categorias.data;
        }
        categorias = categorias.filter(cat => cat.isActive);

        if (Array.isArray(categorias) && categorias.length > 0) {
            globalCategories = categorias; 
            navCategoriesContainer.innerHTML = ""; 

            // Activamos el deslizamiento fluido con el ratón (Drag scroll)
            activarArrastreConMouse(navCategoriesContainer);

            // Botón por defecto para ver todos los productos
            navCategoriesContainer.innerHTML += `
                <li class="nav-item">
                    <a href="#" onclick="filtrarPorCategoria('all', 'Todos los productos', event)">TODOS</a>
                </li>
            `;

            const padres = categorias.filter(cat => cat.parentCategoryId === null);

            padres.forEach(padre => {
                const hijos = categorias.filter(cat => cat.parentCategoryId === padre.id);
                let dropdownHtml = ""; 
                
                if (hijos.length > 0) {
                    dropdownHtml = `<ul class="dropdown-menu">`;
                    hijos.forEach(hijo => {
                        dropdownHtml += `<li><a href="#" onclick="filtrarPorCategoria(${hijo.id}, '${hijo.name}', event)">${hijo.name}</a></li>`;
                    });
                    dropdownHtml += `</ul>`;
                }

                const liPadreHtml = `
                    <li class="nav-item">
                        <a href="#" onclick="filtrarPorCategoria(${padre.id}, '${padre.name}', event)">${padre.name}</a>
                        ${dropdownHtml}
                    </li>
                `;
                navCategoriesContainer.innerHTML += liPadreHtml;
            });
            
            ajustarOffsetSecondaryNav();
            
        } else {
            navCategoriesContainer.innerHTML = '<li class="nav-item"><a href="#">Sin categorías</a></li>';
        }
    } catch (error) {
        console.error("Error al cargar categorías:", error);
    }
};

// =========================================================================
// 2. FUNCIÓN DE ARRASTRE HORIZONTAL (Drag to scroll con mouse)
// =========================================================================
function activarArrastreConMouse(slider) {
    let isDown = false;
    let startX;
    let scrollLeft;

    slider.addEventListener('mousedown', (e) => {
        isDown = true;
        slider.style.cursor = 'grabbing';
        startX = e.pageX - slider.offsetLeft;
        scrollLeft = slider.scrollLeft;
    });

    slider.addEventListener('mouseleave', () => {
        isDown = false;
        slider.style.cursor = 'auto';
    });

    slider.addEventListener('mouseup', () => {
        isDown = false;
        slider.style.cursor = 'auto';
    });

    slider.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const x = e.pageX - slider.offsetLeft;
        const walk = (x - startX) * 2; 
        slider.scrollLeft = scrollLeft - walk;
    });
}

// =========================================================================
// 3. CARGAR PRODUCTOS DESDE LA API
// =========================================================================
/*const loadFeaturedProducts = async () => {
    try {
        const response = await fetch('/api/products');
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            allProducts = result.data.filter(p => p.status === ESTADO_DISPONIBLE);
            renderizarProductos(allProducts);
        } else {
            productsContainer.innerHTML = `<p class="loading-text">No hay productos disponibles en este momento.</p>`;
        }
    } catch (error) {
        console.error("Error al cargar los productos:", error);
        productsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar el catálogo de productos.</p>`;
    }
};*/

const PER_PAGE = 24;
let currentPage = 1;
let totalPages = 1;
let modoSoloOfertas = false;

const loadFeaturedProducts = async (page = 1, hacerScroll = false) => {
    try {
        const params = new URLSearchParams({ page, per_page: PER_PAGE });
        if (modoSoloOfertas) params.set('on_offer', 'true');

        const response = await fetch(`/api/products?${params.toString()}`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            allProducts = result.data;
            renderizarProductos(allProducts);

            currentPage = result.meta.current_page;
            totalPages = result.meta.last_page;
            renderizarPaginacion();

            if (hacerScroll) {
                productsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else {
            productsContainer.innerHTML = `<p class="loading-text">No hay productos disponibles en este momento.</p>`;
            document.getElementById('pagination').innerHTML = '';
        }
    } catch (error) {
        console.error("Error al cargar los productos:", error);
        productsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar el catálogo de productos.</p>`;
    }
};

function renderizarPaginacion() {
    const contenedor = document.getElementById('pagination');
    if (!contenedor) return;

    if (totalPages <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    let html = `<button class="page-btn page-nav" data-page="${currentPage - 1}" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;

    for (let i = 1; i <= totalPages; i++) {
        html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
    }

    html += `<button class="page-btn page-nav" data-page="${currentPage + 1}" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;

    contenedor.innerHTML = html;

    contenedor.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => loadFeaturedProducts(parseInt(btn.dataset.page, 10), true));
    });   
}

// =========================================================================
// 4. RENDERIZAR LAS TARJETAS EN PANTALLA (Borderless UI)
// =========================================================================
function renderizarProductos(productos) {
    if (!productsContainer) return;
    productsContainer.innerHTML = "";

    if (productos.length === 0) {
        productsContainer.innerHTML = `
            <div class="loading-text" style="grid-column: 1 / -1;">
                <i class="fas fa-box-open fa-2x" style="margin-bottom: 0.5rem; display: block; color: #ccc;"></i>
                No se encontraron productos en esta categoría.
            </div>`;
        return;
    }

    productos.forEach(product => {
        productsContainer.innerHTML += construirTarjetaProducto(product);
    });

}

function construirTarjetaProducto(product) {
    let fotos = [];
    if (product.images && product.images.length > 0) {
        fotos = product.images.map(img => {
            if (typeof img === 'string') return img;
            return img.url ? `/storage/${img.url}` : '/image/ImagenNoDefinida.png';
        });
    } else {
        fotos = ['/image/ImagenNoDefinida.png'];
    }

    productGalleries[product.id] = { images: fotos, currentIndex: 0 };

    const tieneOferta = product.offerPrice !== null
        && product.offerPrice !== undefined
        && product.offerPrice < product.price;

    const descuento = tieneOferta
        ? Math.round((1 - (product.offerPrice / product.price)) * 100)
        : 0;

    const precioHtml = tieneOferta ? `
        <div class="product-price-offer">
            <span class="price-current">${product.offerPrice.toFixed(2)} GTQ</span>
            <span class="price-original">Q${product.price.toFixed(2)}</span>
            <span class="price-label-offer">Oferta</span>
        </div>
    ` : `
        <p class="product-price">${product.price.toFixed(2)} GTQ</p>
    `;

    return `
        <div class="product-card" data-id="${product.id}" onclick="irADetalleProducto(${product.id}, event)">
            <div class="product-image-wrapper" 
                 onmouseenter="hoverImagen(${product.id}, true)" 
                 onmouseleave="hoverImagen(${product.id}, false)">

                ${tieneOferta ? `<span class="discount-badge">${descuento}% de descuento</span>` : ''}

                <img id="img-${product.id}" src="${fotos[0]}" alt="${product.name}" loading="lazy">

                ${fotos.length > 1 ? `
                    <button class="product-carousel-btn prev" onclick="cambiarImagen(${product.id}, -1, event)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="product-carousel-btn next" onclick="cambiarImagen(${product.id}, 1, event)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                ` : ''}

                <button class="product-quickview-btn" onclick="toggleQuickView(${product.id}, event)">
                    Vistazo rápido
                </button>
            </div>
            <div class="product-info">
                <h3 class="product-title" title="${product.name}">${product.name}</h3>
                ${precioHtml}
            </div>
        </div>
    `;
}
// =========================================================================
// 5. INTERACTIVIDAD DEL CARRUSEL EN LA TARJETA
// =========================================================================
window.hoverImagen = function(productId, isHovering) {
    const gallery = productGalleries[productId];
    if (!gallery || gallery.images.length < 2) return;
    
    const imgElement = document.getElementById(`img-${productId}`);
    if (isHovering && gallery.currentIndex === 0) {
        imgElement.src = gallery.images[1];
    } else if (!isHovering) {
        imgElement.src = gallery.images[gallery.currentIndex];
    }
};

window.cambiarImagen = function(productId, direction, event) {
    event.stopPropagation(); 
    
    const gallery = productGalleries[productId];
    if (!gallery || gallery.images.length < 2) return;

    gallery.currentIndex += direction;
    if (gallery.currentIndex >= gallery.images.length) {
        gallery.currentIndex = 0;
    } else if (gallery.currentIndex < 0) {
        gallery.currentIndex = gallery.images.length - 1;
    }

    const imgElement = document.getElementById(`img-${productId}`);
    imgElement.src = gallery.images[gallery.currentIndex];
};

window.irADetalleProducto = function(productId, event) {
    // Solo navegamos en pantallas de móvil/tablet chica.
    // En desktop el clic en la tarjeta no hace nada; ahí se usa "Vistazo rápido".
    if (window.innerWidth > 768) return;

    toggleQuickView(productId, event);
};

window.toggleQuickView = function(productId, event) {
    event.stopPropagation();

    const panelExistente = document.getElementById(`quickview-${productId}`);

    // Si ya está abierto para este producto, lo cerramos (toggle)
    if (panelExistente) {
        panelExistente.remove();
        return;
    }

    // Cerramos cualquier otro panel que estuviera abierto (uno a la vez)
    document.querySelectorAll('.quickview-panel').forEach(panel => panel.remove());

    const product = allProducts.find(p => p.id === productId);
    const card = document.querySelector(`.product-card[data-id="${productId}"]`);
    if (!product || !card) return;

    const panel = document.createElement('div');
    panel.className = 'quickview-panel';
    panel.id = `quickview-${productId}`;
    panel.innerHTML = construirQuickViewHtml(product);

    // Lo insertamos como hermano de la tarjeta: al tener grid-column:1/-1
    // en CSS, el grid lo empuja automáticamente a una fila nueva de ancho completo
    card.insertAdjacentElement('afterend', panel);

    quickviewGalleries[productId] = { images: productGalleries[productId].images, currentIndex: 0 };
    // Desplaza la pantalla suavemente hasta dejar visible el panel
    requestAnimationFrame(() => {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
};

function construirQuickViewHtml(product) {
    const fotos = productGalleries[product.id].images;

    const tieneOferta = product.offerPrice !== null
        && product.offerPrice !== undefined
        && product.offerPrice < product.price;

    const precioHtml = tieneOferta ? `
        <div class="product-price-offer quickview-price">
            <span class="price-current">${product.offerPrice.toFixed(2)} GTQ</span>
            <span class="price-original">Q${product.price.toFixed(2)}</span>
            <span class="price-label-offer">Oferta</span>
        </div>
    ` : `
        <p class="quickview-price">${product.price.toFixed(2)} GTQ</p>
    `;

    const thumbnailsHtml = fotos.map((foto, index) => `
        <img src="${foto}" 
             class="quickview-thumb ${index === 0 ? 'active' : ''}" 
             onclick="cambiarImagenQuickView(${product.id}, ${index}, event)">
    `).join('');

    return `
        <button class="quickview-close-btn" onclick="toggleQuickView(${product.id}, event)" aria-label="Cerrar vistazo rápido">
            &times;
        </button>

        <div class="quickview-gallery">
            <div class="quickview-main-image">
                <img id="quickview-img-${product.id}" src="${fotos[0]}" alt="${product.name}">
                ${fotos.length > 1 ? `
                    <button class="product-carousel-btn prev" onclick="cambiarImagenQuickView(${product.id}, 'prev', event)">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="product-carousel-btn next" onclick="cambiarImagenQuickView(${product.id}, 'next', event)">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                ` : ''}
            </div>
            ${fotos.length > 1 ? `<div class="quickview-thumbnails">${thumbnailsHtml}</div>` : ''}
        </div>

        <div class="quickview-info">
            <h3 class="quickview-title">${product.name}</h3>
            ${precioHtml}
            <div class="quickview-description">
                <p>${product.description || ''}</p>
            </div>
            <button class="quickview-add-btn" onclick="agregarAlCarrito(${product.id}, event)">
                AÑADIR AL CARRITO
            </button>
        </div>
    `;

    
}

window.cambiarImagenQuickView = function(productId, direction, event) {
    if (event) event.stopPropagation();

    const gallery = quickviewGalleries[productId];
    if (!gallery) return;

    if (direction === 'prev') {
        gallery.currentIndex = (gallery.currentIndex - 1 + gallery.images.length) % gallery.images.length;
    } else if (direction === 'next') {
        gallery.currentIndex = (gallery.currentIndex + 1) % gallery.images.length;
    } else {
        gallery.currentIndex = direction; // clic directo sobre una miniatura
    }

    document.getElementById(`quickview-img-${productId}`).src = gallery.images[gallery.currentIndex];

    document.querySelectorAll(`#quickview-${productId} .quickview-thumb`).forEach((thumb, index) => {
        thumb.classList.toggle('active', index === gallery.currentIndex);
    });
};


// =========================================================================
// 6. FILTRO DE PRODUCTOS INTELIGENTE (Soporte Móvil + Jerarquías)
// =========================================================================
window.filtrarPorCategoria = function(categoryId, categoryName, event) {
    if (event) event.preventDefault(); 

    // --- MANEJO ESPECIAL PARA VISTA MÓVIL ---
    // Si estamos en celular, interceptamos el clic para actuar como acordeón si tiene hijos
    if (window.innerWidth <= 768 && event && categoryId !== 'all') {
        const clickedLink = event.currentTarget || event.target;
        const subMenu = clickedLink.nextElementSibling;
        
        if (subMenu && subMenu.classList.contains('dropdown-menu')) {
            const navItem = clickedLink.closest(".nav-item");
            if (navItem) {
                navItem.classList.toggle("open"); // Abre/cierra el acordeón móvil
            }
            return; // Detiene el filtrado para permitir la selección de una subcategoría
        }
    }

    // --- FILTRADO DE PRODUCTOS ---
    const titleElement = document.getElementById("current-category-title");
    if (titleElement) {
        titleElement.textContent = categoryName.toUpperCase();
    }

    // Caso "TODOS"
    if (categoryId === 'all') {
        renderizarProductos(allProducts);
    } else {
        // Obtenemos los IDs del padre y todas sus subcategorías (hijas)
        const idsFiltrables = [categoryId]; 
        const subcategoriasHijas = globalCategories.filter(cat => cat.parentCategoryId === categoryId);
        subcategoriasHijas.forEach(hija => idsFiltrables.push(hija.id));

        const productosFiltrados = allProducts.filter(prod => {
            const prodCatId = prod.categoryId || prod.category_id;
            return idsFiltrables.includes(prodCatId);
        });

        renderizarProductos(productosFiltrados);
    }

    // --- AUTO-CIERRE DEL MENÚ MÓVIL ---
    // Cerramos el menú lateral de forma automática para que el usuario pueda ver los resultados
    //const mainNav = document.getElementById("main-nav");
    //if (mainNav && mainNav.classList.contains("active")) {
    //    mainNav.classList.remove("active");
    //}

    const mainNav = document.getElementById("main-nav");
    const mobileNavOverlay = document.getElementById("mobile-nav-overlay");

    /* Solo en móvil: cerrar el panel y mostrar el catálogo */
    /* En móvil: cerrar el panel lateral */
    if (window.innerWidth <= 768) {
        if (mainNav) {
            mainNav.classList.remove("active");
        }

        if (mobileNavOverlay) {
            mobileNavOverlay.classList.remove("active");
        }

        document.body.classList.remove("mobile-menu-open");
    }

    /* En móvil y computadora: llevar al catálogo filtrado */
    if (productsContainer) {
        setTimeout(() => {
            productsContainer.scrollIntoView({
                behavior: "smooth",
                block: "start"
            });
        }, 150);
    }


};


/*
async function loadOfertas() {
    try {
        const response = await fetch('/api/products');
        const result = await response.json();

        if (!result.success) {
            productsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar las ofertas.</p>`;
            return;
        }

        const ofertas = result.data.filter(p =>
            p.status === ESTADO_DISPONIBLE &&
            p.offerPrice !== null &&
            p.offerPrice !== undefined &&
            p.offerPrice < p.price
        );

        allProducts = ofertas;
        renderizarProductos(allProducts);

    } catch (error) {
        console.error("Error al cargar las ofertas:", error);
        productsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar las ofertas.</p>`;
    }
}
*/

// ==========================================
// 7. CARRITO DE COMPRAS
// ==========================================
window.agregarAlCarrito = async function(productId, event) {
    if (event) event.stopPropagation();

    const token = localStorage.getItem('auth_token');
    if (!token) {
        alert('Debes iniciar sesión para agregar productos al carrito.');
        window.location.href = '/login';
        return;
    }

    try {
        const response = await fetch('/api/cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store',
            body: JSON.stringify({ productId })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo agregar el producto al carrito.');
        }

        actualizarContadorCarrito(data.data.itemCount);
        await abrirCarrito(); // 👈 antes tenías alert(), ahora abre el drawer directamente

    } catch (error) {
        alert(error.message);
    }
};

function actualizarContadorCarrito(count) {
    const badge = document.getElementById('cart-count-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
}

async function cargarContadorCarritoInicial() {
    const token = localStorage.getItem('auth_token');
    const badge = document.getElementById('cart-count-badge');
    if (!token || !badge) return;

    try {
        const response = await fetch('/api/cart', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        if (!response.ok) return;

        const data = await response.json();
        actualizarContadorCarrito(data.data.itemCount);
    } catch (error) {
        console.error('Error al cargar el contador del carrito:', error);
    }
}



// ==========================================
// 8. DRAWER DEL CARRITO (panel lateral)
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const cartIconBtn = document.getElementById("cart-icon-btn");
    const cartOverlay = document.getElementById("cart-overlay");
    const cartCloseBtn = document.getElementById("cart-drawer-close");

    if (cartIconBtn) {
        cartIconBtn.addEventListener("click", abrirCarrito);
    }
    if (cartOverlay) {
        cartOverlay.addEventListener("click", cerrarCarrito);
    }
    if (cartCloseBtn) {
        cartCloseBtn.addEventListener("click", cerrarCarrito);
    }
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") cerrarCarrito();
    });
});

window.abrirCarrito = async function() {
    const drawer = document.getElementById("cart-drawer");
    const overlay = document.getElementById("cart-overlay");
    if (!drawer || !overlay) return;

    drawer.classList.add("active");
    overlay.classList.add("active");
    document.body.style.overflow = "hidden"; // evita scroll de fondo mientras está abierto

    await cargarCarritoEnDrawer();
};

window.cerrarCarrito = function() {
    const drawer = document.getElementById("cart-drawer");
    const overlay = document.getElementById("cart-overlay");
    if (!drawer || !overlay) return;

    drawer.classList.remove("active");
    overlay.classList.remove("active");
    document.body.style.overflow = "";
};

async function cargarCarritoEnDrawer() {
    const itemsContainer = document.getElementById("cart-drawer-items");
    const token = localStorage.getItem('auth_token');

    if (!token) {
        itemsContainer.innerHTML = `
            <div class="cart-drawer-empty">
                <p>Debes iniciar sesión para ver tu carrito.</p>
            </div>`;
        document.getElementById("cart-drawer-footer").innerHTML = "";
        return;
    }

    itemsContainer.innerHTML = `<p class="loading-text">Cargando...</p>`;

    try {
        const response = await fetch('/api/cart', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'No se pudo cargar tu carrito.');

        renderizarCarritoDrawer(data.data);
        actualizarContadorCarrito(data.data.itemCount);

    } catch (error) {
        itemsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar tu carrito.</p>`;
    }
}

function renderizarCarritoDrawer(cart) {
    const titleEl = document.getElementById("cart-drawer-title");
    const itemsContainer = document.getElementById("cart-drawer-items");
    const footerContainer = document.getElementById("cart-drawer-footer");

    titleEl.textContent = `Su carrito (${cart.itemCount})`;

    if (cart.items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="cart-drawer-empty">
                <p>Tu carrito está vacío.</p>
            </div>`;
        footerContainer.innerHTML = "";
        return;
    }

    itemsContainer.innerHTML = cart.items.map(item => `
        <div class="cart-drawer-item" data-cart-item-id="${item.id}">
            <img src="${item.productImage ? '/storage/' + item.productImage : '/image/ImagenNoDefinida.png'}" 
                 alt="${item.productName}" class="cart-drawer-item-image">
            <div class="cart-drawer-item-info">
                <h3 class="cart-drawer-item-name">${item.productName}</h3>
                <div class="cart-drawer-item-price">
                    <span class="price-final">${item.price.toFixed(2)} GTQ</span>
                    ${item.originalPrice ? `<span class="price-original">Q${item.originalPrice.toFixed(2)}</span>` : ''}
                </div>
            </div>
            <button class="cart-drawer-item-remove" onclick="eliminarDelCarritoDrawer(${item.id})" aria-label="Eliminar producto">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `).join('');

    footerContainer.innerHTML = `
        <div class="cart-drawer-subtotal-row">
            <span>Subtotal:</span>
            <span>${cart.total.toFixed(2)} GTQ</span>
        </div>
        <p class="cart-drawer-note">Impuesto incluido. Envío calculado en la pantalla de pago.</p>
        <button class="cart-drawer-checkout-btn" onclick="iniciarCheckout()">
            PAGAR
        </button>
    `;
}

window.eliminarDelCarritoDrawer = async function(cartItemId) {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const response = await fetch(`/api/cart/${cartItemId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'No se pudo eliminar el producto.');

        renderizarCarritoDrawer(data.data);
        actualizarContadorCarrito(data.data.itemCount);

    } catch (error) {
        alert(error.message);
    }
};

// ==========================================
// 9. MODAL DE CHECKOUT (dirección + confirmación)
// ==========================================
let checkoutState = { addresses: [] };

document.addEventListener("DOMContentLoaded", () => {
    const closeBtn = document.getElementById("checkout-modal-close");
    const overlay = document.getElementById("checkout-overlay");
    if (closeBtn) closeBtn.addEventListener("click", cerrarCheckoutModal);
    if (overlay) overlay.addEventListener("click", cerrarCheckoutModal);
});

window.iniciarCheckout = async function() {
    cerrarCarrito();
    abrirCheckoutModal();
    await cargarPasoDireccion();
};

function abrirCheckoutModal() {
    document.getElementById("checkout-modal").classList.add("active");
    document.getElementById("checkout-overlay").classList.add("active");
    document.body.style.overflow = "hidden";
}

window.cerrarCheckoutModal = function() {
    document.getElementById("checkout-modal").classList.remove("active");
    document.getElementById("checkout-overlay").classList.remove("active");
    document.body.style.overflow = "";
};

async function cargarPasoDireccion() {
    const content = document.getElementById("checkout-modal-content");
    const token = localStorage.getItem('auth_token');

    if (!token) {
        content.innerHTML = `
            <div class="checkout-step">
                <h3>Inicia sesión para continuar</h3>
                <a href="/login" class="checkout-primary-btn">Iniciar sesión</a>
            </div>`;
        return;
    }

    content.innerHTML = `<p class="loading-text">Cargando tus direcciones...</p>`;

    try {
        const response = await fetch('/api/addresses', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar tus direcciones.');

        checkoutState.addresses = data.data;
        renderizarPasoDireccion();

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

function renderizarPasoDireccion() {
    const content = document.getElementById("checkout-modal-content");
    const addresses = checkoutState.addresses;

    const listHtml = addresses.map(addr => `
        <label class="checkout-address-option">
            <input type="radio" name="checkout-address" value="${addr.id}" ${addr.isDefault ? 'checked' : ''}>
            <div class="checkout-address-info">
                <strong>${addr.label}</strong>
                <span>${addr.addressLine}</span>
            </div>
        </label>
    `).join('');

    content.innerHTML = `
        <div class="checkout-step">
            <h3>¿A dónde enviamos tu pedido?</h3>

            ${addresses.length > 0 ? `
                <div class="checkout-address-list">${listHtml}</div>
                <button type="button" class="checkout-link-btn" id="toggle-new-address-btn">+ Agregar nueva dirección</button>
            ` : `<p class="checkout-empty-note">Aún no tienes direcciones guardadas. Agrega una para continuar.</p>`}

            <form id="new-address-form" class="checkout-new-address-form ${addresses.length > 0 ? 'hidden' : ''}">
                <div class="checkout-form-row">
                    <label>Etiqueta</label>
                    <input type="text" id="new-address-label" placeholder="Ej. Casa, Trabajo" maxlength="50">
                </div>
                <div class="checkout-form-row">
                    <label>Dirección completa</label>
                    <textarea id="new-address-line" rows="2" placeholder="Zona, calle, referencia..." maxlength="500"></textarea>
                </div>
                ${addresses.length > 0 ? `
                    <label class="checkout-checkbox-row">
                        <input type="checkbox" id="new-address-default">
                        Usar como dirección predeterminada
                    </label>
                ` : ''}
            </form>

            <p class="checkout-error" id="checkout-error" style="display:none;"></p>
            <button type="button" class="checkout-primary-btn" id="checkout-confirm-btn">Confirmar pedido</button>
        </div>
    `;

    const toggleBtn = document.getElementById("toggle-new-address-btn");
    const form = document.getElementById("new-address-form");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            form.classList.toggle("hidden");
            toggleBtn.textContent = form.classList.contains("hidden") ? "+ Agregar nueva dirección" : "Cancelar nueva dirección";
        });
    }

    document.getElementById("checkout-confirm-btn").onclick = procesarConfirmacionCheckout;
}

async function procesarConfirmacionCheckout() {
    const errorEl = document.getElementById("checkout-error");
    errorEl.style.display = "none";

    const token = localStorage.getItem('auth_token');
    const seleccionado = document.querySelector('input[name="checkout-address"]:checked');
    const labelInput = document.getElementById("new-address-label");
    const lineInput = document.getElementById("new-address-line");
    const formVisible = labelInput && !document.getElementById("new-address-form").classList.contains("hidden");

    try {
        let addressId;

        if (formVisible && labelInput.value.trim() && lineInput.value.trim()) {
            const isDefaultCheckbox = document.getElementById("new-address-default");
            const isDefault = isDefaultCheckbox ? isDefaultCheckbox.checked : true;
            const nueva = await crearDireccion(token, labelInput.value.trim(), lineInput.value.trim(), isDefault);
            addressId = nueva.id;
        } else if (seleccionado) {
            addressId = parseInt(seleccionado.value, 10);
        } else {
            errorEl.textContent = "Selecciona una dirección o agrega una nueva.";
            errorEl.style.display = "block";
            return;
        }

        const resultado = await confirmarCheckout(token, addressId);
        renderizarPasoConfirmacion(resultado);

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = "block";
    }
}

async function crearDireccion(token, label, addressLine, isDefault) {
    const response = await fetch('/api/addresses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify({ label, addressLine, isDefault })
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'No se pudo guardar la dirección.');
    return data.data;
}

async function confirmarCheckout(token, addressId) {
    const response = await fetch('/api/orders/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify({ addressId })
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'No se pudo generar tu pedido.');

    actualizarContadorCarrito(0);
    return data.data; // { order, whatsappUrl }
}

function renderizarPasoConfirmacion(resultado) {
    const content = document.getElementById("checkout-modal-content");
    const { order, whatsappUrl } = resultado;

    content.innerHTML = `
        <div class="checkout-step checkout-confirmation">
            <div class="checkout-success-icon">✓</div>
            <h3>¡Tu pedido #${order.id} fue registrado!</h3>
            <p>Total: <strong>${order.total.toFixed(2)} GTQ</strong></p>
            <p class="checkout-confirmation-note">
                Te vamos a abrir WhatsApp con tu pedido ya escrito.
                <strong>Asegúrate de presionar "Enviar"</strong> para confirmarlo con nosotros.
            </p>
            <a href="${whatsappUrl}" target="_blank" class="checkout-whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Abrir WhatsApp
            </a>
            <p class="checkout-fallback-note">
                ¿No se abrió? Escríbenos directamente al <strong>+502 3666-6075</strong>
                mencionando tu número de pedido <strong>#${order.id}</strong>.
            </p>
            <button type="button" class="checkout-link-btn" onclick="cerrarCheckoutModal(); window.location.href='/mis-pedidos';">
                Ver mis pedidos
            </button>
        </div>
    `;

    window.open(whatsappUrl, '_blank');
}


// ==========================================
// 10. VISTA "MIS PEDIDOS"
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const ordersContent = document.getElementById("orders-content");
    if (ordersContent) {
        cargarMisPedidos(ordersContent);

        const backLink = document.getElementById("orders-back-link");
        if (backLink) {
            const previousPage = sessionStorage.getItem('last_store_page') || '/';
            backLink.href = previousPage;
        }
    }
});

const ORDER_STATUS_LABELS = {
    pending_payment: 'Pendiente de pago',
    confirmed: 'Confirmado',
    preparing: 'En preparación',
    on_route: 'En camino',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
};
const ORDER_STATUS_STEPS = ['pending_payment', 'confirmed', 'preparing', 'on_route', 'delivered'];
const ORDER_STATUS_ICONS = {
    pending_payment: 'fa-hourglass-half',
    confirmed: 'fa-clipboard-check',
    preparing: 'fa-box',
    on_route: 'fa-truck',
    delivered: 'fa-circle-check',
    cancelled: 'fa-circle-xmark'
};

async function cargarMisPedidos(container) {
    const token = localStorage.getItem('auth_token');

    if (!token) {
        container.innerHTML = `
            <div class="orders-empty">
                <p>Debes iniciar sesión para ver tus pedidos.</p>
                <a href="/login" class="orders-empty-link">Iniciar sesión</a>
            </div>`;
        return;
    }

    try {
        const response = await fetch('/api/orders/mine', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });

        // El token existía pero Sanctum lo rechazó (inválido o expirado)
        if (response.status === 401) {
            localStorage.removeItem('auth_token'); // limpiamos el token muerto
            container.innerHTML = `
                <div class="orders-empty">
                    <p>Tu sesión expiró. Por favor, inicia sesión de nuevo.</p>
                    <a href="/login" class="orders-empty-link">Iniciar sesión</a>
                </div>`;
            return;
        }

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudieron cargar tus pedidos.');
        }

        renderizarMisPedidos(container, data.data);

    } catch (error) {
        console.error(error);
        container.innerHTML = `<p class="loading-text">Ocurrió un error al cargar tus pedidos.</p>`;
    }
}

function renderizarMisPedidos(container, orders) {
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="orders-empty">
                <p>Aún no tienes pedidos realizados.</p>
                <a href="/" class="orders-empty-link">Ver catálogo</a>
            </div>`;
        return;
    }

    container.innerHTML = orders.map(order => {
        const fecha = new Date(order.createdAt).toLocaleDateString('es-GT', {
            year: 'numeric', month: 'long', day: 'numeric'
        });

        const statusLabel = ORDER_STATUS_LABELS[order.status] || order.status;
        const statusIcon = ORDER_STATUS_ICONS[order.status] || 'fa-circle-question';
        const itemsHtml = order.items.map(item => `
            <div class="order-item-row">
                <img src="${item.productImage ? '/storage/' + item.productImage : '/image/ImagenNoDefinida.png'}" 
                     alt="${item.productName}" class="order-item-image">
                <div class="order-item-info">
                    <p class="order-item-name">${item.productName}</p>
                    <p class="order-item-price">${item.unitPrice.toFixed(2)} GTQ</p>
                </div>
            </div>
        `).join('');

        return `
            <div class="order-card">
                <div class="order-card-header">
                    <div>
                        <p class="order-card-id">Pedido #${order.id}</p>
                        <p class="order-card-date">${fecha}</p>
                    </div>
                    ${construirBarraDeEstado(order.status)}
                </div>

                <div class="order-card-items">
                    ${itemsHtml}
                </div>

                <div class="order-card-footer">
                    <span>Total</span>
                    <span class="order-card-total">${order.total.toFixed(2)} GTQ</span>
                </div>
            </div>
        `;
    }).join('');
}

function construirBarraDeEstado(status) {
    if (status === 'cancelled') {
        return `
            <div class="order-status-cancelled-badge">
                <i class="fas ${ORDER_STATUS_ICONS.cancelled}"></i>
                <span>${ORDER_STATUS_LABELS.cancelled}</span>
            </div>`;
    }

    const currentIndex = ORDER_STATUS_STEPS.indexOf(status);

    return `
        <div class="order-status-tracker">
            ${ORDER_STATUS_STEPS.map((stepStatus, index) => {
                const claseEstado = index < currentIndex ? 'completed' : (index === currentIndex ? 'current' : '');
                return `
                    <div class="order-status-step ${claseEstado}">
                        <span class="order-status-icon"><i class="fas ${ORDER_STATUS_ICONS[stepStatus]}"></i></span>
                        <span class="order-status-label">${ORDER_STATUS_LABELS[stepStatus]}</span>
                    </div>`;
            }).join('')}
        </div>`;
}


// ==========================================
// 11. ESTADO DE SESIÓN EN EL HEADER (login / hola + logout)
// ==========================================
async function actualizarAuthUI() {
    const authContainer = document.getElementById('auth-container');
    if (!authContainer) return;

    const token = localStorage.getItem('auth_token');
    if (!token) {
        renderLoginLink(authContainer);
        return;
    }

    try {
        const response = await fetch('/api/profile', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });

        if (!response.ok) {
            // Token inválido o expirado
            localStorage.removeItem('auth_token');
            renderLoginLink(authContainer);
            return;
        }

        const data = await response.json();
        renderLoggedInUI(authContainer, data.name); 

    } catch (error) {
        console.error('Error al verificar sesión:', error);
        renderLoginLink(authContainer);
    }
}

function renderLoginLink(container) {
    container.innerHTML = `
        <a href="/login" class="icon-link" title="Iniciar Sesión / Registrarse">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                <path d="M16 19h6" />
                <path d="M19 16v6" />
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
            </svg>
        </a>`;
}

function renderLoggedInUI(container, name) {
    container.innerHTML = `
        <span class="user-greeting">Hola, ${name}</span>
        <button type="button" class="icon-link" id="logout-btn" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" />
                <path d="M15 6v-1a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2v-1" />
            </svg>
        </button>`;

    document.getElementById('logout-btn').addEventListener('click', confirmarCerrarSesion);
}

window.confirmarCerrarSesion = async function() {
    if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) return;

    const token = localStorage.getItem('auth_token');

    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
    } catch (error) {
        console.error('Error al cerrar sesión en el servidor:', error);
        // Continuamos igual: aunque falle la llamada al backend,
        // igual limpiamos la sesión local para no dejar al usuario atascado
    } finally {
        localStorage.removeItem('auth_token');
        window.location.href = '/';
    }
};

// ==========================================
// 11. Subastas Presentacion al ganar una subasta
// ==========================================
async function verificarSubastasGanadas() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const response = await fetch('/api/auctions/my-bid-status', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!data.success) return;

        if (data.data.wins.length > 0) {
            mostrarBannerSubastaGanada(data.data.wins);
            return;
        }

        const key = auctionFlagKey();
        if (data.data.activeBidsCount === 0 && key) {
            localStorage.removeItem(key);
        }

    } catch (error) {
        console.error('Error al verificar subastas ganadas:', error);
    }
}

function iniciarSondeoLigero() {
    setInterval(() => {
        const key = auctionFlagKey();
        if (key && localStorage.getItem(key) === 'true') {
            verificarSubastasGanadas();
        }
    }, 20000);
}


function mostrarBannerSubastaGanada(wins) {
    if (document.getElementById('auction-win-modal')) return; // ya se está mostrando
    //Para que no interrumpa si el usuario esta en el modal de checkout
    if (document.getElementById('checkout-modal')?.classList.contains('active')) return;
    const primera = wins[0];
    const extraTexto = wins.length > 1
        ? `<p class="auction-win-extra">Tienes ${wins.length - 1} subasta(s) ganada(s) más pendientes de confirmar.</p>`
        : '';

    const overlay = document.createElement('div');
    overlay.id = 'auction-win-overlay';
    overlay.className = 'auction-win-overlay active';

    const modal = document.createElement('div');
    modal.id = 'auction-win-modal';
    modal.className = 'auction-win-modal active';

    modal.innerHTML = `
        <div class="auction-win-icon">🏆</div>
        <h2>¡Ganaste la subasta!</h2>
        <p class="auction-win-product">${primera.productName}</p>
        <p class="auction-win-price">Q${primera.finalPrice.toFixed(2)}</p>
        ${extraTexto}
        <div class="auction-win-actions">
            <button type="button" class="auction-win-confirm-btn" onclick="cerrarModalGanador(); abrirConfirmacionSubastaGanada(${primera.auctionId})">
                Confirmar pedido
            </button>
            <button type="button" class="auction-win-decline-btn" onclick="confirmarCancelarSubasta(${primera.auctionId})">
                No quiero este producto
            </button>
        </div>
    `;


    

    document.body.appendChild(overlay);
    document.body.appendChild(modal);
    document.body.style.overflow = "hidden";
}

window.cerrarModalGanador = function() {
    document.getElementById('auction-win-overlay')?.remove();
    document.getElementById('auction-win-modal')?.remove();
    document.body.style.overflow = "";
};


window.confirmarCancelarSubasta = async function(auctionId) {
    if (!confirm('¿Seguro que no quieres este producto? Se le ofrecerá al siguiente postor.')) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/auctions/${auctionId}/decline`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo procesar tu decisión.');

        alert('Entendido, se le ha ofrecido el producto al siguiente postor.');
        cerrarModalGanador();

    } catch (error) {
        alert(error.message);
    }
};

function auctionFlagKey() {
    const token = localStorage.getItem('auth_token');
    return token ? `tiene_pujas_activas_${token}` : null;
}



//MODAL DE SUBASTAS
window.abrirConfirmacionSubastaGanada = async function(auctionId) {
    document.getElementById('auction-win-banner')?.remove();

    abrirCheckoutModal(); // función que ya existe, solo abre el modal visualmente
    await cargarPasoDireccionParaSubasta(auctionId);
};

async function cargarPasoDireccionParaSubasta(auctionId) {
    const content = document.getElementById("checkout-modal-content");
    const token = localStorage.getItem('auth_token');

    content.innerHTML = `<p class="loading-text">Cargando tus direcciones...</p>`;

    try {
        const response = await fetch('/api/addresses', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar tus direcciones.');

        checkoutState.addresses = data.data;
        renderizarPasoDireccion(); // función que ya tienes

        //Sobrescribimos el botón de confirmar para que apunte al endpoint de subasta
        document.getElementById("checkout-confirm-btn").onclick = () => procesarConfirmacionSubasta(auctionId);

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

async function procesarConfirmacionSubasta(auctionId) {
    const errorEl = document.getElementById("checkout-error");
    errorEl.style.display = "none";

    const token = localStorage.getItem('auth_token');
    const seleccionado = document.querySelector('input[name="checkout-address"]:checked');
    const labelInput = document.getElementById("new-address-label");
    const lineInput = document.getElementById("new-address-line");
    const formVisible = labelInput && !document.getElementById("new-address-form").classList.contains("hidden");

    try {
        let addressId;

        if (formVisible && labelInput.value.trim() && lineInput.value.trim()) {
            const isDefaultCheckbox = document.getElementById("new-address-default");
            const isDefault = isDefaultCheckbox ? isDefaultCheckbox.checked : true;
            const nueva = await crearDireccion(token, labelInput.value.trim(), lineInput.value.trim(), isDefault);
            addressId = nueva.id;
        } else if (seleccionado) {
            addressId = parseInt(seleccionado.value, 10);
        } else {
            errorEl.textContent = "Selecciona una dirección o agrega una nueva.";
            errorEl.style.display = "block";
            return;
        }

        const response = await fetch(`/api/auctions/${auctionId}/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ addressId })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo confirmar tu pedido.');

        renderizarPasoConfirmacion(data.data); // función que ya tienes, reutilizada tal cual

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = "block";
    }


    
}

// ==========================================
// MODAL DE CUENTA (Perfil / Direcciones)
// ==========================================
let direccionesCache = [];

document.addEventListener("DOMContentLoaded", () => {
    const profileBtn = document.getElementById("sidebarProfileBtn");
    const addressesBtn = document.getElementById("sidebarAddressesBtn");
    const closeBtn = document.getElementById("account-modal-close");
    const overlay = document.getElementById("account-modal-overlay");

    if (profileBtn) profileBtn.addEventListener("click", abrirModalPerfil);
    if (addressesBtn) addressesBtn.addEventListener("click", abrirModalDirecciones);
    if (closeBtn) closeBtn.addEventListener("click", cerrarAccountModal);
    if (overlay) overlay.addEventListener("click", cerrarAccountModal);
});

function abrirAccountModal() {
    document.getElementById("account-modal").classList.add("active");
    document.getElementById("account-modal-overlay").classList.add("active");
    document.body.style.overflow = "hidden";
}

window.cerrarAccountModal = function() {
    document.getElementById("account-modal").classList.remove("active");
    document.getElementById("account-modal-overlay").classList.remove("active");
    document.body.style.overflow = "";
};

//Para dejar el submenu fijo xd
function ajustarOffsetSecondaryNav() {
    const header = document.querySelector('header');
    if (!header) return;
    document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
}

// ===== PERFIL (solo lectura) =====
async function abrirModalPerfil() {
    abrirAccountModal();
    const content = document.getElementById("account-modal-content");
    content.innerHTML = `<p class="loading-text">Cargando...</p>`;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/profile', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error('No se pudo cargar tu perfil.');

        const iniciales = (data.name?.[0] || '') + (data.lastName?.[0] || '');

        content.innerHTML = `
            <div class="account-profile-view">
                <div class="account-profile-avatar">${iniciales.toUpperCase()}</div>
                <h3 class="account-profile-name">${data.name} ${data.lastName || ''}</h3>
                <div class="account-profile-details">
                    <div class="account-profile-field">
                        <span class="account-profile-icon">✉</span>
                        <div>
                            <label>Correo electrónico</label>
                            <p>${data.email}</p>
                        </div>
                    </div>
                    ${data.phone ? `
                    <div class="account-profile-field">
                        <span class="account-profile-icon">☎</span>
                        <div>
                            <label>Teléfono</label>
                            <p>${data.phone}</p>
                        </div>
                    </div>` : ''}
                </div>
            </div>
        `;
    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

// ===== DIRECCIONES (CRUD completo) =====
async function abrirModalDirecciones() {
    abrirAccountModal();
    await cargarDireccionesModal();
}

async function cargarDireccionesModal() {
    const content = document.getElementById("account-modal-content");
    content.innerHTML = `<p class="loading-text">Cargando direcciones...</p>`;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/addresses', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar tus direcciones.');

        direccionesCache = data.data;
        renderizarDireccionesModal();

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

function renderizarDireccionesModal() {
    const content = document.getElementById("account-modal-content");
    const alLimite = direccionesCache.length >= 5;

    const listHtml = direccionesCache.map(addr => `
        <div class="account-address-card ${addr.isDefault ? 'is-default' : ''}" data-address-id="${addr.id}" onclick="clicEnDireccion(${addr.id}, event)">
            <div class="account-address-info">
                <strong>${addr.label}</strong> ${addr.isDefault ? '<span class="account-address-default-badge">Predeterminada</span>' : ''}
                <p>${addr.addressLine}</p>
            </div>
            <div class="account-address-actions">
                <button type="button" onclick="event.stopPropagation(); abrirEdicionDireccion(${addr.id})">Editar</button>
                <button type="button" class="account-address-delete" onclick="event.stopPropagation(); eliminarDireccionModal(${addr.id})">Eliminar</button>
            </div>
        </div>
    `).join('');

    content.innerHTML = `
        <h3>Mis Direcciones</h3>
        <div class="account-address-list">
            ${direccionesCache.length > 0 ? listHtml : '<p class="text-muted">Aún no tienes direcciones guardadas.</p>'}
        </div>
        ${direccionesCache.length > 0 ? '<p class="account-address-hint">Toca una dirección para marcarla como predeterminada.</p>' : ''}

        ${alLimite
            ? `<p class="account-address-limit-note">Ya tienes el máximo de 5 direcciones. Elimina una para poder agregar otra.</p>`
            : `<button type="button" class="account-link-btn" id="toggleNewAddressBtn">+ Agregar nueva dirección</button>
               <form id="newAddressModalForm" class="account-new-address-form hidden">
                   <div class="account-form-row">
                       <label>Etiqueta</label>
                       <input type="text" id="newAddrLabel" placeholder="Ej. Casa, Trabajo" maxlength="50">
                   </div>
                   <div class="account-form-row">
                       <label>Dirección completa</label>
                       <textarea id="newAddrLine" rows="2" placeholder="Zona, calle, referencia..." maxlength="500"></textarea>
                   </div>
                   <button type="button" class="account-primary-btn" onclick="guardarNuevaDireccionModal()">Guardar dirección</button>
               </form>`
        }

        <p class="account-modal-error" id="accountAddressError" style="display:none;"></p>
    `;

    if (!alLimite) {
        document.getElementById("toggleNewAddressBtn").addEventListener("click", () => {
            document.getElementById("newAddressModalForm").classList.toggle("hidden");
        });
    }
}



window.clicEnDireccion = function(id, event) {
    const addr = direccionesCache.find(a => a.id === id);
    if (!addr || addr.isDefault) return; // ya es la predeterminada, no hace nada
    marcarDireccionPredeterminada(id);
};

window.marcarDireccionPredeterminada = async function(id) {
    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}/default`, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo actualizar.');
        cargarDireccionesModal();
    } catch (error) {
        alert(error.message);
    }
};

window.eliminarDireccionModal = async function(id) {
    if (!confirm('¿Eliminar esta dirección?')) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo eliminar.');
        cargarDireccionesModal();
    } catch (error) {
        alert(error.message);
    }
};

window.guardarNuevaDireccionModal = async function() {
    const errorEl = document.getElementById("accountAddressError");
    errorEl.style.display = 'none';

    const label = document.getElementById("newAddrLabel").value.trim();
    const addressLine = document.getElementById("newAddrLine").value.trim();

    if (!label || !addressLine) {
        errorEl.textContent = 'Completa la etiqueta y la dirección.';
        errorEl.style.display = 'block';
        return;
    }

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/addresses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ label, addressLine, isDefault: false })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo guardar.');

        cargarDireccionesModal();

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = 'block';
    }
};


window.abrirEdicionDireccion = function(id) {
    const addr = direccionesCache.find(a => a.id === id);
    if (!addr) return;

    const card = document.querySelector(`.account-address-card[data-address-id="${id}"]`);
    card.innerHTML = `
        <div class="account-form-row">
            <label>Etiqueta</label>
            <input type="text" id="editAddrLabel_${id}" value="${addr.label}" maxlength="50">
        </div>
        <div class="account-form-row">
            <label>Dirección completa</label>
            <textarea id="editAddrLine_${id}" rows="2" maxlength="500">${addr.addressLine}</textarea>
        </div>
        <div class="account-address-actions">
            <button type="button" onclick="guardarEdicionDireccion(${id})">Guardar</button>
            <button type="button" onclick="renderizarDireccionesModal()">Cancelar</button>
        </div>
    `;
};

window.guardarEdicionDireccion = async function(id) {
    const label = document.getElementById(`editAddrLabel_${id}`).value.trim();
    const addressLine = document.getElementById(`editAddrLine_${id}`).value.trim();

    if (!label || !addressLine) {
        alert('Completa ambos campos.');
        return;
    }

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ label, addressLine })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo actualizar.');

        cargarDireccionesModal();

    } catch (error) {
        alert(error.message);
    }
};



