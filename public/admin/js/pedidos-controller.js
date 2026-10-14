const ORDER_STATUS_LABELS = {
    pending_payment: 'Pendiente de pago',
    confirmed: 'Confirmado',
    preparing: 'En preparación',
    on_route: 'En camino',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
};

let ordersCache = [];


const ESTADOS_POR_GRUPO = {
    active: {
        todos: 'Todos los activos',
        estados: ['pending_payment', 'confirmed', 'preparing', 'on_route']
    },
    history: {
        todos: 'Todo el historial',
        estados: ['delivered', 'cancelled']
    }
};

let grupoActual = 'active';

document.addEventListener("DOMContentLoaded", () => {
    const tableContainer = document.getElementById("ordersTableContainer");
    const filterStatus = document.getElementById("filterStatus");
    const searchCodigo = document.getElementById("searchCodigo");

    function poblarDropdown(grupo) {
        const config = ESTADOS_POR_GRUPO[grupo];
        let html = `<option value="">${config.todos}</option>`;
        config.estados.forEach(estado => {
            html += `<option value="${estado}">${ORDER_STATUS_LABELS[estado]}</option>`;
        });
        filterStatus.innerHTML = html;
    }

    // Pestañas
    document.querySelectorAll('#orderTabs .nav-link').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('#orderTabs .nav-link').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            grupoActual = tab.dataset.grupo;
            searchCodigo.value = "";
            poblarDropdown(grupoActual);
            cargarPedidos();
        });
    });

    filterStatus.addEventListener("change", () => {
        searchCodigo.value = "";
        cargarPedidos();
    });

    let searchTimeout;
    searchCodigo.addEventListener("input", () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => cargarPedidos(), 350);
    });

    // Carga inicial
    poblarDropdown(grupoActual);

    // ¿Venimos del link de WhatsApp con un código? Lo buscamos y abrimos su modal.
    const codigoUrl = new URLSearchParams(window.location.search).get('codigo');
    if (codigoUrl) {
        searchCodigo.value = codigoUrl;
        cargarPedidos().then(() => {
            const order = ordersCache.find(o => o.publicCode === codigoUrl);
            if (order) verDetalleOrden(order.id);
        });
    } else {
        cargarPedidos();
    }

    async function cargarPedidos() {
        const token = localStorage.getItem('admin_auth_token');
        const status = filterStatus.value;
        const codigo = searchCodigo.value.trim();          // 👈 nuevo
        tableContainer.innerHTML = `<p class="text-center text-muted">Cargando pedidos...</p>`;

        try {
            const params = new URLSearchParams();
            if (codigo) {
                params.set('codigo', codigo);              // 👈 búsqueda por código: ignora grupo/estado
            } else if (status) {
                params.set('status', status);
            } else {
                params.set('grupo', grupoActual);
            }

            const response = await fetch(`/api/orders/all?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
                cache: 'no-store'
            });
            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'No se pudieron cargar los pedidos.');

            ordersCache = data.data;
            renderizarTabla(ordersCache);

        } catch (error) {
            tableContainer.innerHTML = `<p class="text-center text-danger">${escapeHtml(error.message)}</p>`;
        }
    }

    const PEDIDOS_POR_LOTE = 20;   // cuántos pedidos revela cada "Ver más"
    let pedidosListaCompleta = [];
    let pedidosMostrados = 0;

    // Entrada principal: se llama al cambiar de pestaña, buscar o recargar.
    // Guarda la lista completa y reinicia el contador al primer lote.
    function renderizarTabla(orders) {
        pedidosListaCompleta = orders;
        pedidosMostrados = PEDIDOS_POR_LOTE;
        pintarLotePedidos();
    }

    // Pinta solo los pedidos visibles hasta ahora + el botón "Ver más".
    function pintarLotePedidos() {
        const orders = pedidosListaCompleta;

        if (orders.length === 0) {
            tableContainer.innerHTML = `<p class="text-center text-muted">No hay pedidos que coincidan.</p>`;
            return;
        }

        const visibles = orders.slice(0, pedidosMostrados);

        const rows = visibles.map(order => {
            const fecha = formatearFechaHoraGT(order.createdAt);
            const rowClass = order.status === 'pending_payment' ? 'order-row-pending' : '';

            // Un pedido ya confirmado no puede regresar a "Pendiente de pago" (el backend también lo valida)
            const yaConfirmado = order.status !== 'pending_payment';
            const statusOptions = Object.entries(ORDER_STATUS_LABELS).map(([value, label]) => {
                const bloqueada = value === 'pending_payment' && yaConfirmado;
                return `<option value="${value}" ${order.status === value ? 'selected' : ''} ${bloqueada ? 'disabled' : ''}>${label}</option>`;
            }).join('');

            const estaBloqueada = order.status === 'cancelled' || order.status === 'delivered';

            return `
                <tr class="${rowClass}">
                    <td>#${order.id}</td>
                    <td>${fecha}</td>
                    <td>${escapeHtml(order.shippingAddress)}</td>
                    <td>${order.total.toFixed(2)} GTQ</td>
                    <td>
                        <div class="status-select-wrapper status-${order.status}">
                            <select class="form-select form-select-sm status-select" data-order-id="${order.id}" ${estaBloqueada ? 'disabled title="Pedido finalizado: no se puede modificar"' : ''}>
                                ${statusOptions}
                            </select>
                        </div>
                    </td>
                    <td>
                        <button class="btn-detail-order" onclick="verDetalleOrden(${order.id})" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        const restantes = orders.length - visibles.length;
        const botonVerMas = restantes > 0
            ? `<div class="text-center my-3">
                <button id="btnVerMasPedidos" class="btn btn-outline-dark btn-sm">
                    Ver más (${restantes} restante${restantes === 1 ? '' : 's'})
                </button>
            </div>`
            : '';

        tableContainer.innerHTML = `
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Pedido</th>
                            <th>Fecha</th>
                            <th>Dirección</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
            ${botonVerMas}
        `;

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', (e) => actualizarEstado(e.target.dataset.orderId, e.target.value, e.target));
        });

        const btnVerMas = document.getElementById('btnVerMasPedidos');
        if (btnVerMas) {
            btnVerMas.addEventListener('click', () => {
                pedidosMostrados += PEDIDOS_POR_LOTE;
                pintarLotePedidos();
            });
        }
    }


    function formatearFechaHoraGT(fechaUtc) {
        if (!fechaUtc) return '—';
        const d = new Date(fechaUtc.replace(' ', 'T') + 'Z');
        return d.toLocaleString('es-GT', {
            timeZone: 'America/Guatemala',
            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
    }

    window.actualizarEstado = async function(orderId, newStatus, selectElement) {
        const token = localStorage.getItem('admin_auth_token');
        const order = ordersCache.find(o => o.id == orderId);

        if (newStatus === 'cancelled') {
            const productos = order ? order.items.map(i => i.productName).join(', ') : '';
            const confirmado = await adminConfirm(
                `Vas a cancelar el pedido #${orderId} (${productos}). El/los producto(s) volverán a estar disponibles en la tienda de inmediato. Esta acción no se puede deshacer.`,
                { title: 'Cancelar pedido', confirmText: 'Sí, cancelar', confirmClass: 'btn-danger', icon: 'fa-ban' }
            );
            if (!confirmado) {
                if (selectElement && order) selectElement.value = order.status;
                return;
            }
        }



        if (newStatus === 'confirmed') {
            const yaEstabaConfirmado = order && order.confirmedAt;
            const mensaje = yaEstabaConfirmado
                ? `Este pedido ya se confirmó el ${new Date(order.confirmedAt).toLocaleDateString('es-GT')}. Esa venta ya quedó registrada — no se va a duplicar ni a mover la fecha. ¿Confirmas el estado?`
                : 'Confírmalo solo si ya recibiste el dinero del cliente. Una vez confirmado, el pedido ya no podrá regresar a "Pendiente de pago" (si el pago no fuera válido, tendrías que cancelarlo). La fecha de hoy se usará en el reporte de ventas.';

            const confirmado = await adminConfirm(mensaje, {
                title: yaEstabaConfirmado ? 'Confirmar pedido' : '¿Ya recibiste el pago?',
                confirmText: yaEstabaConfirmado ? 'Sí, confirmar' : 'Sí, ya recibí el pago',
                confirmClass: 'btn-success',
                icon: 'fa-check-circle'
            });
            if (!confirmado) {
                if (selectElement && order) selectElement.value = order.status;
                return;
            }
        }

        if (newStatus === 'delivered') {
            const confirmado = await adminConfirm(
                `Vas a marcar el pedido #${orderId} como Entregado. Una vez entregado, el pedido queda finalizado y ya no se podrá modificar. ¿Continuar?`,
                { title: 'Marcar como entregado', confirmText: 'Sí, entregar', confirmClass: 'btn-success', icon: 'fa-box-open' }
            );
            if (!confirmado) {
                if (selectElement && order) selectElement.value = order.status;
                return;
            }
        }

        try {
            const response = await fetch(`/api/orders/${orderId}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`
                },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await response.json();

            if (!response.ok) throw new Error(data.message || 'No se pudo actualizar el estado.');

            //const filterStatus = document.getElementById("filterStatus");
            cargarPedidos();

        } catch (error) {
            adminAlert(error.message, { type: 'error' });
            if (selectElement && order) selectElement.value = order.status;
        }
    };

    window.verDetalleOrden = function(orderId) {
        const order = ordersCache.find(o => o.id === orderId);
        if (!order) return;

        const fecha = formatearFechaHoraGT(order.createdAt);

        const itemsHtml = order.items.map(item => `
            <tr>
                <td>
                    <img src="${item.productImage ? '/storage/' + item.productImage : '/image/ImagenNoDefinida.png'}"
                        style="width:50px;height:65px;object-fit:cover;border-radius:4px;">
                </td>
                <td>${escapeHtml(item.productName)}</td>
                <td>${item.unitPrice.toFixed(2)} GTQ</td>
            </tr>
        `).join('');

        document.getElementById("orderDetailBody").innerHTML = `
            <div class="order-detail-meta">
                <p><strong>Pedido:</strong> #${order.id}</p>
                <p><strong>Código:</strong> ${escapeHtml(order.publicCode || '—')}</p>
                <p><strong>Fecha:</strong> ${fecha}</p>
                <p><strong>Cliente:</strong> ${escapeHtml(order.customerName || 'No disponible')}</p>
                <p><strong>Teléfono:</strong> ${order.contactPhone
                    ? `<a href="https://wa.me/502${encodeURIComponent(order.contactPhone)}" target="_blank" rel="noopener">${escapeHtml(order.contactPhone)}</a>`
                    : 'No registrado'}</p>
                <p><strong>Dirección de envío:</strong> ${escapeHtml(order.shippingAddress)}</p>
                <p><strong>Estado:</strong>
                    <span class="order-detail-status-pill status-${order.status}">${ORDER_STATUS_LABELS[order.status]}</span>
                </p>
            </div>
            <table class="table order-detail-table">
                <thead><tr><th></th><th>Producto</th><th>Precio</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            <p class="text-end fw-bold fs-5 order-detail-total">Total: ${order.total.toFixed(2)} GTQ</p>
        `;

        new bootstrap.Modal(document.getElementById('modalOrderDetail')).show();
    };
});