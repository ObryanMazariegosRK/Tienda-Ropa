let subastasCache = [];
let auctionStatusById = {}; // productId -> último estado conocido de su subasta
let auctionPollTimer = null;
let currentAuctionProductId = null;
let bidModalPollTimer = null;
let auctionGalleryImages = [];
const PER_PAGE_SUBASTAS = 24;
let subastasCurrentPage = 1;
let subastasTotalPages = 1;
let subastasCategoriaId = null; // null = todas las categorías

document.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("subastas-container");
    if (!container) return;

    cargarSubastas();

    document.getElementById("auction-modal-close").addEventListener("click", cerrarAuctionModal);
    document.getElementById("auction-overlay").addEventListener("click", cerrarAuctionModal);
});


// La llama el menú de categorías (main.js) cuando estamos en la página de subastas
window.filtrarSubastasPorCategoria = function(categoryId) {
    subastasCategoriaId = categoryId;
    cargarSubastas(1);
    document.getElementById("subastas-container")?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

async function cargarSubastas(page = 1) {
    const container = document.getElementById("subastas-container");

    try {
        const params = new URLSearchParams({ page, per_page: PER_PAGE_SUBASTAS });
        if (subastasCategoriaId !== null) params.set('category_id', subastasCategoriaId);

        const response = await fetch(`/api/products/on-auction?${params.toString()}`, { cache: 'no-store' });
        const data = await response.json();

        if (!data.success) throw new Error(data.message || 'No se pudieron cargar las subastas.');

        subastasCache = data.data;
        subastasCurrentPage = data.meta.current_page;
        subastasTotalPages = data.meta.last_page;

        if (subastasCache.length === 0) {
            container.innerHTML = `
                <div class="loading-text" style="grid-column: 1 / -1;">
                    <i class="fas fa-gavel fa-2x" style="margin-bottom: 0.5rem; display: block; color: #ccc;"></i>
                    ${subastasCategoriaId !== null ? 'No hay subastas activas en esta categoría.' : 'No hay subastas activas en este momento.'}
                </div>`;
            document.getElementById("subastasPagination").innerHTML = '';
            return;
        }

        await Promise.all(subastasCache.map(p => actualizarEstadoSubasta(p.id)));

        renderizarSubastas();
        renderizarPaginacionSubastas();
        iniciarPollingGrid();

    } catch (error) {
        container.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

function renderizarPaginacionSubastas() {
    const contenedor = document.getElementById('subastasPagination');
    if (!contenedor) return;

    if (subastasTotalPages <= 1) {
        contenedor.innerHTML = '';
        return;
    }

    // construirBotonesPaginacion vive en paginacion.js (se carga antes que este archivo)
    contenedor.innerHTML = construirBotonesPaginacion(subastasCurrentPage, subastasTotalPages);

    contenedor.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
        btn.addEventListener('click', () => {
            clearInterval(auctionPollTimer); // detenemos el polling de la página anterior antes de cambiar
            cargarSubastas(parseInt(btn.dataset.page, 10));
            document.getElementById("subastas-container").scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
}

async function actualizarEstadoSubasta(productId) {
    try {
        const response = await fetch(`/api/auctions/product/${productId}/status`, { cache: 'no-store' });
        const data = await response.json();
        if (data.success && data.data) {
            auctionStatusById[productId] = data.data;
        }
    } catch (error) {
        console.error(`Error al consultar subasta del producto ${productId}:`, error);
    }
}

function formatearTiempoRestante(segundos) {
    if (segundos <= 0) return "Finalizada";

    const dias = Math.floor(segundos / 86400);
    const horas = Math.floor((segundos % 86400) / 3600);
    const minutos = Math.floor((segundos % 3600) / 60);
    const segs = segundos % 60;

    if (dias > 0) return `${dias}d ${horas}h`;
    if (horas > 0) return `${horas}h ${minutos}m`;
    return `${minutos}m ${segs}s`;
}
/*
function renderizarSubastas() {
    const container = document.getElementById("subastas-container");

    container.innerHTML = subastasCache.map(product => {
        const fotos = (product.images && product.images.length > 0)
            ? product.images.map(img => `/storage/${img.url}`)
            : ['/image/ImagenNoDefinida.png'];

        const estado = auctionStatusById[product.id];
        const precioActual = estado ? estado.currentPrice.toFixed(2) : parseFloat(product.price).toFixed(2);
        const tiempoTexto = estado ? formatearTiempoRestante(estado.secondsRemaining) : '...';
        const finalizada = estado && estado.secondsRemaining <= 0;

        return `
            <div class="product-card" data-auction-product-id="${product.id}" onclick="abrirAuctionModal(${product.id})">
                <div class="product-image-wrapper">
                    <img src="${fotos[0]}" alt="${product.name}">
                    <span class="discount-badge" style="background-color:#111;">
                        <i class="fas fa-gavel"></i> Subasta
                    </span>
                </div>
                <div class="product-info">
                    <h3 class="product-title" title="${product.name}">${product.name}</h3>
                    <p class="product-price">
                        Q${precioActual}
                        <span class="auction-time-remaining ${finalizada ? 'auction-ended' : ''}" data-auction-timer="${product.id}">
                            ${finalizada ? 'Finalizada' : `⏱ ${tiempoTexto}`}
                        </span>
                    </p>
                </div>
            </div>
        `;
    }).join('');
}
    */

let subastaGalleries = {}; // igual que productGalleries, pero para las tarjetas del grid de subastas

function renderizarSubastas() {
    const container = document.getElementById("subastas-container");

    container.innerHTML = subastasCache.map(product => {
        const fotos = (product.images && product.images.length > 0)
            ? product.images.map(img => `/storage/${img.url}`)
            : ['/image/ImagenNoDefinida.png'];

        // Poblamos productGalleries (ya declarada en main.js) para que
        // hoverImagen/cambiarImagen funcionen igual que en el catálogo normal.
        productGalleries[product.id] = { images: fotos, currentIndex: 0 };

        const estado = auctionStatusById[product.id];
        const precioActual = estado ? estado.currentPrice.toFixed(2) : parseFloat(product.price).toFixed(2);
        const tiempoTexto = estado ? formatearTiempoRestante(estado.secondsRemaining) : '...';
        const finalizada = estado && estado.secondsRemaining <= 0;

        return `
            <div class="product-card" data-auction-product-id="${product.id}" onclick="abrirAuctionModal(${product.id})">
                <div class="product-image-wrapper"
                     onmouseenter="hoverImagen(${product.id}, true)"
                     onmouseleave="hoverImagen(${product.id}, false)">

                    <span class="discount-badge" style="background-color:#111;">
                        <i class="fas fa-gavel"></i> Subasta
                    </span>

                    <img id="img-${product.id}" src="${fotos[0]}" alt="${product.name}">

                    ${fotos.length > 1 ? `
                        <button class="product-carousel-btn prev" onclick="cambiarImagen(${product.id}, -1, event)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="product-carousel-btn next" onclick="cambiarImagen(${product.id}, 1, event)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    ` : ''}

                    <!-- Equivalente al "Vistazo rápido" de la tienda: aparece al pasar el mouse -->
                    <button class="product-quickview-btn" onclick="event.stopPropagation(); abrirAuctionModal(${product.id})">
                        <i class="fas fa-gavel"></i> ${finalizada ? 'Ver resultado' : 'Pujar ahora'}
                    </button>
                </div>
                <div class="product-info">
                    <h3 class="product-title" title="${product.name}">${product.name}</h3>
                    <p class="product-price" id="auctionGridPrice-${product.id}">
                        Q${precioActual}
                        <span class="auction-time-remaining ${finalizada ? 'auction-ended' : ''}" id="auctionGridTimer-${product.id}">
                            ${finalizada ? 'Finalizada' : `⏱ ${tiempoTexto}`}
                        </span>
                    </p>
                </div>
            </div>
        `;
    }).join('');
}

// Se llama cada 4s: actualiza SOLO el precio y el timer de cada tarjeta,
// sin tocar las imágenes — así no se pierde el hover ni el índice del carrusel.
function actualizarValoresGrid() {
    subastasCache.forEach(product => {
        const estado = auctionStatusById[product.id];
        if (!estado) return;

        const priceEl = document.getElementById(`auctionGridPrice-${product.id}`);
        const timerEl = document.getElementById(`auctionGridTimer-${product.id}`);
        if (!priceEl || !timerEl) return;

        const finalizada = estado.secondsRemaining <= 0;

        priceEl.childNodes[0].textContent = `Q${estado.currentPrice.toFixed(2)} `;
        timerEl.textContent = finalizada ? 'Finalizada' : `⏱ ${formatearTiempoRestante(estado.secondsRemaining)}`;
        timerEl.classList.toggle('auction-ended', finalizada);
    });
}

function iniciarPollingGrid() {
    clearInterval(auctionPollTimer);
    auctionPollTimer = setInterval(async () => {
        await Promise.all(subastasCache.map(p => actualizarEstadoSubasta(p.id)));
        actualizarValoresGrid(); // 👈 ya no llama a renderizarSubastas()
    }, 4000);
}

// =========================================================================
// INTERACTIVIDAD DEL CARRUSEL EN LA TARJETA DE SUBASTA (calcado de main.js)
// =========================================================================
window.hoverImagenSubasta = function(productId, isHovering) {
    const gallery = subastaGalleries[productId];
    if (!gallery || gallery.images.length < 2) return;

    const imgElement = document.getElementById(`img-subasta-${productId}`);
    if (isHovering && gallery.currentIndex === 0) {
        imgElement.src = gallery.images[1];
    } else if (!isHovering) {
        imgElement.src = gallery.images[gallery.currentIndex];
    }
};

window.cambiarImagenSubasta = function(productId, direction, event) {
    event.stopPropagation(); // para que no dispare abrirAuctionModal() al tocar la flecha

    const gallery = subastaGalleries[productId];
    if (!gallery || gallery.images.length < 2) return;

    gallery.currentIndex += direction;
    if (gallery.currentIndex >= gallery.images.length) {
        gallery.currentIndex = 0;
    } else if (gallery.currentIndex < 0) {
        gallery.currentIndex = gallery.images.length - 1;
    }

    const imgElement = document.getElementById(`img-subasta-${productId}`);
    imgElement.src = gallery.images[gallery.currentIndex];
};



// ==========================================
// MODAL DE PUJA
// ==========================================
window.abrirAuctionModal = function(productId) {
    currentAuctionProductId = productId;
    document.getElementById("auction-modal").classList.add("active");
    document.getElementById("auction-overlay").classList.add("active");
    document.body.style.overflow = "hidden";

    // Construye el HTML completo UNA sola vez al abrir
    renderizarAuctionModalShell();
    cargarHistorialPujas(productId);

    clearInterval(bidModalPollTimer);
    bidModalPollTimer = setInterval(async () => {
        const estabaFinalizada = auctionStatusById[productId]?.secondsRemaining <= 0;
        await actualizarEstadoSubasta(productId);

        // Si el modal se cerró (o se abrió otra subasta) mientras esperábamos la
        // respuesta, no tocamos nada: antes esto pedía /api/auctions/product/null/bids
        if (currentAuctionProductId !== productId) return;

        const ahoraFinalizada = auctionStatusById[productId]?.secondsRemaining <= 0;

        if (estabaFinalizada !== ahoraFinalizada) {
            // Cambió de "activa" a "finalizada" (o viceversa): sí hay que reconstruir el formulario
            renderizarAuctionModalShell();
        } else {
            // Solo actualizamos los números, sin tocar el input ni el mensaje de error del usuario
            actualizarValoresEnVivo();
        }
        cargarHistorialPujas(productId);
    }, 3000);
};

window.cerrarAuctionModal = function() {
    document.getElementById("auction-modal").classList.remove("active");
    document.getElementById("auction-overlay").classList.remove("active");
    document.body.style.overflow = "";
    clearInterval(bidModalPollTimer);
    currentAuctionProductId = null;
};

function renderizarAuctionModalShell() {
    const product = subastasCache.find(p => p.id === currentAuctionProductId);
    const estado = auctionStatusById[currentAuctionProductId];
    if (!product || !estado) return;

    const fotos = (product.images && product.images.length > 0)
        ? product.images.map(img => `/storage/${img.url}`)
        : ['/image/ImagenNoDefinida.png'];

    const finalizada = estado.secondsRemaining <= 0 || estado.status !== 'active';
    const minimoSiguiente = (estado.currentPrice + estado.minIncrement).toFixed(2);

    const thumbnailsHtml = fotos.length > 1 ? `
        <div class="auction-thumbnails">
            ${fotos.map((foto, index) => `
                <img src="${foto}" class="auction-thumb ${index === 0 ? 'active' : ''}"
                     onclick="cambiarImagenAuction(${index})">
            `).join('')}
        </div>
    ` : '';

    document.getElementById("auction-modal-content").innerHTML = `
        <div class="auction-modal-body">
            <div class="auction-modal-gallery">
                <div class="auction-modal-image-frame">
                    <img src="${fotos[0]}" alt="${product.name}" class="auction-modal-image zoomable" id="auctionMainImage">
                </div>
                ${thumbnailsHtml}
            </div>
            <div class="auction-modal-info">
                <h3>${product.name}</h3>
                <p class="auction-current-price" id="auctionCurrentPrice">Q${estado.currentPrice.toFixed(2)}</p>
                <p class="auction-time-label ${finalizada ? 'auction-ended' : ''}" id="auctionTimeLabel">
                    ${finalizada ? 'Esta subasta ha finalizado' : `⏱ Tiempo restante: ${formatearTiempoRestante(estado.secondsRemaining)}`}
                </p>

                ${!finalizada ? `
                    <div class="auction-bid-form">
                        <label id="auctionMinLabel">Tu oferta (mínimo Q${minimoSiguiente})</label>
                        <div class="auction-bid-input-row">
                            <input type="number" id="bidAmountInput" step="0.01" min="${minimoSiguiente}" max="10000.00" placeholder="Q${minimoSiguiente}">
                            <button type="button" onclick="enviarPuja()">Pujar</button>
                        </div>
                        <p class="auction-bid-error" id="auctionBidError" style="display:none;"></p>
                    </div>
                ` : ''}

                <p class="auction-description">${product.description || ''}</p>

                <h4 class="auction-history-title">Historial de pujas</h4>
                <div id="auctionBidHistory" class="auction-bid-history">
                    <p class="loading-text">Cargando...</p>
                </div>
            </div>
        </div>
    `;

    auctionGalleryImages = fotos;
}

window.cambiarImagenAuction = function(index) {
    const mainImg = document.getElementById("auctionMainImage");
    mainImg.src = auctionGalleryImages[index];
    // Igual que en la vista rápida: al cambiar de foto se quita el zoom
    mainImg.dataset.zoom = '1';
    mainImg.style.transform = 'scale(1)';
    mainImg.style.transformOrigin = 'center center';
    document.querySelectorAll(".auction-thumb").forEach((thumb, i) => {
        thumb.classList.toggle("active", i === index);
    });
};

// Se llama cada 3s: actualiza SOLO texto de precio/tiempo/mínimo,
// sin recrear el input (no borra lo que el usuario está escribiendo)
// ni el mensaje de error (no lo hace desaparecer a medio camino).
function actualizarValoresEnVivo() {
    const estado = auctionStatusById[currentAuctionProductId];
    if (!estado) return;

    const priceEl = document.getElementById("auctionCurrentPrice");
    const timeEl = document.getElementById("auctionTimeLabel");
    const minLabelEl = document.getElementById("auctionMinLabel");
    const inputEl = document.getElementById("bidAmountInput");

    if (priceEl) priceEl.textContent = `Q${estado.currentPrice.toFixed(2)}`;
    if (timeEl) timeEl.textContent = `⏱ Tiempo restante: ${formatearTiempoRestante(estado.secondsRemaining)}`;

    const minimoSiguiente = (estado.currentPrice + estado.minIncrement).toFixed(2);
    if (minLabelEl) minLabelEl.textContent = `Tu oferta (mínimo Q${minimoSiguiente})`;
    if (inputEl) {
        inputEl.min = minimoSiguiente;
        inputEl.placeholder = `Q${minimoSiguiente}`;
        // Nota: NO tocamos inputEl.value — así lo que el usuario esté escribiendo se conserva
    }
}

async function cargarHistorialPujas(productId) {
    try {
        const response = await fetch(`/api/auctions/product/${productId}/bids`, { cache: 'no-store' });
        const data = await response.json();
        const historyEl = document.getElementById("auctionBidHistory");
        if (!historyEl) return;

        if (!data.success || data.data.length === 0) {
            historyEl.innerHTML = `<p class="text-muted">Aún no hay pujas. ¡Sé el primero!</p>`;
            return;
        }

        historyEl.innerHTML = data.data.map(bid => `
            <div class="auction-bid-row">
                <span>${escapeHtml(bid.userName)}</span>
                <span>Q${bid.amount.toFixed(2)}</span>
            </div>
        `).join('');

    } catch (error) {
        console.error('Error al cargar historial de pujas:', error);
    }
}

window.enviarPuja = async function() {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        await mostrarAviso('Debes iniciar sesión para pujar.', 'info');
        window.location.href = '/login';
        return;
    }

    const input = document.getElementById("bidAmountInput");
    const errorEl = document.getElementById("auctionBidError");
    errorEl.style.display = 'none';

    const amount = parseFloat(input.value);
    if (!amount || amount <= 0) {
        errorEl.textContent = 'Ingresa un monto válido.';
        errorEl.style.display = 'block';
        return;
    }

    // Guardamos el producto: el usuario podría cerrar el modal mientras llega la respuesta
    const productId = currentAuctionProductId;

    try {
        const response = await fetch(`/api/auctions/product/${productId}/bid`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({ amount })
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'No se pudo registrar tu puja.');

        const key = auctionFlagKey();
        if (key) localStorage.setItem(key, 'true');

        auctionStatusById[productId] = data.data;
        if (currentAuctionProductId !== productId) return; // el modal ya se cerró

        input.value = ""; // limpiamos el campo tras una puja exitosa
        renderizarAuctionModalShell(); // aquí sí reconstruimos: el mínimo y el precio cambiaron de verdad
        cargarHistorialPujas(productId);

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = 'block';
    }
};


