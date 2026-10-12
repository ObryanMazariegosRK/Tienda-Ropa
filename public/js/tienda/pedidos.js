// =========================================================================
// MIS PEDIDOS: lista de pedidos del cliente con su estado y "Ver más" (solo en /mis-pedidos)
// =========================================================================

// ==========================================
// VISTA "MIS PEDIDOS"
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

const PEDIDOS_POR_PAGINA = 10;
let misPedidosPagina = 0;

// Carga los pedidos por lotes: page 1 reemplaza el contenido, las siguientes
// páginas se agregan debajo al presionar "Ver más"
async function cargarMisPedidos(container, page = 1) {
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
        const response = await fetch(`/api/orders/mine?page=${page}&per_page=${PEDIDOS_POR_PAGINA}`, {
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

        misPedidosPagina = data.meta.current_page;
        renderizarMisPedidos(container, data.data, data.meta);

    } catch (error) {
        console.error(error);
        if (page === 1) {
            container.innerHTML = `<p class="loading-text">Ocurrió un error al cargar tus pedidos.</p>`;
        } else {
            mostrarAviso('No se pudieron cargar más pedidos. Intenta de nuevo.');
            const btn = container.querySelector('.orders-load-more-btn');
            if (btn) { btn.disabled = false; btn.textContent = 'Ver más'; }
        }
    }
}

function renderizarMisPedidos(container, orders, meta) {
    const esPrimeraPagina = meta.current_page === 1;

    if (esPrimeraPagina && orders.length === 0) {
        container.innerHTML = `
            <div class="orders-empty">
                <p>Aún no tienes pedidos realizados.</p>
                <a href="/" class="orders-empty-link">Ver catálogo</a>
            </div>`;
        return;
    }

    if (esPrimeraPagina) {
        container.innerHTML = `<div class="orders-list"></div><div class="orders-load-more"></div>`;
    }

    container.querySelector('.orders-list')
        .insertAdjacentHTML('beforeend', orders.map(construirTarjetaPedido).join(''));

    // Botón "Ver más" con los pedidos que faltan por cargar
    const cargados = Math.min(meta.current_page * meta.per_page, meta.total);
    const restantes = meta.total - cargados;
    const loadMore = container.querySelector('.orders-load-more');
    loadMore.innerHTML = restantes > 0
        ? `<button type="button" class="orders-load-more-btn">Ver más (${restantes} restante${restantes === 1 ? '' : 's'})</button>`
        : '';

    const btn = loadMore.querySelector('.orders-load-more-btn');
    if (btn) {
        btn.addEventListener('click', () => {
            btn.disabled = true;
            btn.textContent = 'Cargando...';
            cargarMisPedidos(container, misPedidosPagina + 1);
        });
    }
}

function construirTarjetaPedido(order) {
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
