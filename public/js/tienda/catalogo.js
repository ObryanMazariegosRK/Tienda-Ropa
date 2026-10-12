// =========================================================================
// CATÁLOGO: menú de categorías, tarjetas de productos, paginación, carrusel
// de cada tarjeta, vista rápida y filtro por categoría.
// Requiere: comun.js, paginacion.js. subastas.js reutiliza hoverImagen/cambiarImagen.
// =========================================================================

let allProducts = [];         // Almacena todos los productos cargados del servidor
let globalCategories = [];    // Copia en memoria para resolver la jerarquía (Padres e Hijas)
let productGalleries = {};    // Guarda el índice de imagen actual por cada producto

// Referencias del DOM globales (se resolverán al cargar el DOM)
let navCategoriesContainer;
let productsContainer;
let quickviewGalleries = {}; // Índice de imagen actual dentro del panel de vistazo rápido

document.addEventListener("DOMContentLoaded", () => {
    //Resolver las referencias del DOM correctamente para evitar errores de duplicación
    navCategoriesContainer = document.getElementById("nav-categories");
    productsContainer = document.getElementById("products-container")
        || document.getElementById("ofertas-container");

    loadCategories();

    // Decide UNA sola fuente de productos según la página, evitando la carrera
    if (document.getElementById("products-container")) {
        loadFeaturedProducts();
    } else if (document.getElementById("ofertas-container")) {
        modoSoloOfertas = true;
        loadFeaturedProducts();
    }
});

// =========================================================================
// CARGAR MENÚ DE CATEGORÍAS (Sincronizado con filtros)
// =========================================================================
const loadCategories = async () => {
    // Subastas y Mis pedidos no tienen menú de categorías
    if (!navCategoriesContainer) return;

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
                        dropdownHtml += `<li><a href="#" data-nombre="${escapeHtml(hijo.name)}" onclick="filtrarPorCategoria(${hijo.id}, this.dataset.nombre, event)">${escapeHtml(hijo.name)}</a></li>`;
                    });
                    dropdownHtml += `</ul>`;
                }

                const liPadreHtml = `
                    <li class="nav-item">
                        <a href="#" data-nombre="${escapeHtml(padre.name)}" onclick="filtrarPorCategoria(${padre.id}, this.dataset.nombre, event)">${escapeHtml(padre.name)}</a>
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
// FUNCIÓN DE ARRASTRE HORIZONTAL (Drag to scroll con mouse)
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
// CARGAR PRODUCTOS DESDE LA API
// =========================================================================

const PER_PAGE = 24;
let currentPage = 1;
let totalPages = 1;
let modoSoloOfertas = false;
let categoriaActualId = null; // null = todas las categorías

const loadFeaturedProducts = async (page = 1, hacerScroll = false) => {
    try {
        const params = new URLSearchParams({ page, per_page: PER_PAGE });
        if (modoSoloOfertas) params.set('on_offer', 'true');
        // El backend filtra por la categoría y sus subcategorías en TODO el catálogo
        if (categoriaActualId !== null) params.set('category_id', categoriaActualId);

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
            allProducts = [];
            if (categoriaActualId !== null) {
                renderizarProductos([]); // "No se encontraron productos en esta categoría."
            } else {
                productsContainer.innerHTML = `<p class="loading-text">No hay productos disponibles en este momento.</p>`;
            }
            document.getElementById('pagination').innerHTML = '';

            if (hacerScroll) {
                productsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
    } catch (error) {
        console.error("Error al cargar los productos:", error);
        productsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar el catálogo de productos.</p>`;
    }
};

// construirBotonesPaginacion() vive en paginacion.js (compartido con el panel del admin)
function renderizarPaginacion() {
    const contenedor = document.getElementById('pagination');
    if (!contenedor) return;

    if (totalPages <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    contenedor.innerHTML = construirBotonesPaginacion(currentPage, totalPages);

    contenedor.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => loadFeaturedProducts(parseInt(btn.dataset.page, 10), true));
    });   
}

// =========================================================================
// RENDERIZAR LAS TARJETAS EN PANTALLA (Borderless UI)
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
// INTERACTIVIDAD DEL CARRUSEL EN LA TARJETA
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
    // En computadora la imagen del panel mide lo mismo que la de la tarjeta
    panel.style.setProperty('--card-width', `${card.getBoundingClientRect().width}px`);

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
                <img id="quickview-img-${product.id}" src="${fotos[0]}" alt="${product.name}" class="zoomable">
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

    //Cambiamos la imagen Y reiniciamos el zoom al nivel 1
    const mainImg = document.getElementById(`quickview-img-${productId}`);
    mainImg.src = gallery.images[gallery.currentIndex];
    mainImg.style.transform = 'scale(1)';
    mainImg.dataset.zoom = '1';

    document.querySelectorAll(`#quickview-${productId} .quickview-thumb`).forEach((thumb, index) => {
        thumb.classList.toggle('active', index === gallery.currentIndex);
    });
};


// =========================================================================
// FILTRO DE PRODUCTOS INTELIGENTE (Soporte Móvil + Jerarquías)
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

    // Pedimos al servidor la página 1 de esa categoría (incluye sus subcategorías).
    // Antes se filtraba solo la página cargada y se perdían productos de otras páginas.
    categoriaActualId = categoryId === 'all' ? null : categoryId;
    if (productsContainer) {
        loadFeaturedProducts(1, true); // true = al terminar de cargar, sube al inicio de las tarjetas
    } else if (typeof window.filtrarSubastasPorCategoria === 'function') {
        window.filtrarSubastasPorCategoria(categoriaActualId);
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



};
