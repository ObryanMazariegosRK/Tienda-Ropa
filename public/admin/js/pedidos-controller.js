const ORDER_STATUS_LABELS = {
    pending_payment: 'Pendiente de pago',
    confirmed: 'Confirmado',
    preparing: 'En preparación',
    on_route: 'En camino',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
};

let ordersCache = [];

document.addEventListener("DOMContentLoaded", () => {
    const tableContainer = document.getElementById("ordersTableContainer");
    const filterStatus = document.getElementById("filterStatus");

    cargarPedidos();

    filterStatus.addEventListener("change", () => {
        cargarPedidos(filterStatus.value || null);
    });

    async function cargarPedidos(status = null) {
        const token = localStorage.getItem('admin_auth_token');
        tableContainer.innerHTML = `<p class="text-center text-muted">Cargando pedidos...</p>`;

        try {
            const url = status ? `/api/orders/all?status=${status}` : `/api/orders/all`;
            const response = await fetch(url, {
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

            return `
                <tr class="${rowClass}">
                    <td>#${order.id}</td>
                    <td>${fecha}</td>
                    <td>${order.shippingAddress}</td>
                    <td>${order.total.toFixed(2)} GTQ</td>
                    <td>
                        <select class="form-select form-select-sm status-select" data-order-id="${order.id}">
                            ${statusOptions}
                        </select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="verDetalleOrden(${order.id})">
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
            select.addEventListener('change', (e) => actualizarEstado(e.target.dataset.orderId, e.target.value));
        });
    }

    window.actualizarEstado = async function(orderId, newStatus) {
        const token = localStorage.getItem('admin_auth_token');

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

            const filterStatus = document.getElementById("filterStatus");
            cargarPedidos(filterStatus.value || null);

        } catch (error) {
            alert(error.message);
        }
    };

    window.verDetalleOrden = function(orderId) {
        const order = ordersCache.find(o => o.id === orderId);
        if (!order) return;

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
            <p><strong>Pedido:</strong> #${order.id}</p>
            <p><strong>Dirección de envío:</strong> ${order.shippingAddress}</p>
            <p><strong>Estado:</strong> ${ORDER_STATUS_LABELS[order.status]}</p>
            <table class="table">
                <thead><tr><th></th><th>Producto</th><th>Precio</th></tr></thead>
                <tbody>${itemsHtml}</tbody>
            </table>
            <p class="text-end fw-bold fs-5">Total: ${order.total.toFixed(2)} GTQ</p>
        `;

        new bootstrap.Modal(document.getElementById('modalOrderDetail')).show();
    };
});