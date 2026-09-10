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
            poblarDropdown(grupoActual);
            cargarPedidos();
        });
    });

    filterStatus.addEventListener("change", () => {
        cargarPedidos();
    });

    // Carga inicial
    poblarDropdown(grupoActual);
    cargarPedidos();

    async function cargarPedidos() {
        const token = localStorage.getItem('admin_auth_token');
        const status = filterStatus.value;
        tableContainer.innerHTML = `<p class="text-center text-muted">Cargando pedidos...</p>`;

        try {
            const params = new URLSearchParams();
            if (status) {
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
            tableContainer.innerHTML = `<p class="text-center text-danger">${error.message}</p>`;
        }
    }

    function renderizarTabla(orders) {
        if (orders.length === 0) {
            tableContainer.innerHTML = `<p class="text-center text-muted">No hay pedidos que coincidan.</p>`;
            return;
        }

        const rows = orders.map(order => {
            const fecha = new Date(order.createdAt).toLocaleDateString('es-GT', {
                year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });

            const rowClass = order.status === 'pending_payment' ? 'order-row-pending' : '';

            const statusOptions = Object.entries(ORDER_STATUS_LABELS).map(([value, label]) =>
                `<option value="${value}" ${order.status === value ? 'selected' : ''}>${label}</option>`
            ).join('');

            const estaBloqueada = order.status === 'cancelled' || order.status === 'delivered';

            return `
                <tr class="${rowClass}">
                    <td>#${order.id}</td>
                    <td>${fecha}</td>
                    <td>${order.shippingAddress}</td>
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
        `;

        document.querySelectorAll('.status-select').forEach(select => {
            select.addEventListener('change', (e) => actualizarEstado(e.target.dataset.orderId, e.target.value, e.target));
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

        // Retroceder a "pendiente de pago" una venta que ya estaba confirmada:
        // el admin debe saber que esa venta saldrá del reporte.
        if (newStatus === 'pending_payment' && order && order.confirmedAt) {
            const confirmado = await adminConfirm(
                `Este pedido ya estaba confirmado como venta. Si lo regresas a "Pendiente de pago", dejará de contar en el reporte de ventas mientras esté en ese estado. ¿Continuar?`,
                { title: 'Regresar a pendiente de pago', confirmText: 'Sí, regresar', confirmClass: 'btn-warning', icon: 'fa-triangle-exclamation' }
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
                : '¿Confirmas que ya recibiste el pago de este pedido? Se tomará la fecha de hoy para el reporte de ventas.';

            const confirmado = await adminConfirm(mensaje, {
                title: 'Confirmar pedido',
                confirmText: 'Sí, confirmar',
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

        const fecha = new Date(order.createdAt).toLocaleDateString('es-GT', {
            year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });

        const itemsHtml = order.items.map(item => `
            <tr>
                <td>
                    <img src="${item.productImage ? '/storage/' + item.productImage : '/image/ImagenNoDefinida.png'}"
                        style="width:50px;height:65px;object-fit:cover;border-radius:4px;">
                </td>
                <td>${item.productName}</td>
                <td>${item.unitPrice.toFixed(2)} GTQ</td>
            </tr>
        `).join('');

        document.getElementById("orderDetailBody").innerHTML = `
            <div class="order-detail-meta">
                <p><strong>Pedido:</strong> #${order.id}</p>
                <p><strong>Fecha:</strong> ${fecha}</p>
                <p><strong>Dirección de envío:</strong> ${order.shippingAddress}</p>
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