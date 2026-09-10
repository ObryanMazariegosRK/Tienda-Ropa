let revenueChartInstance = null;
let ordersStatusChartInstance = null;
let rangoActual = { start: null, end: null };

const ORDER_STATUS_LABELS_DASHBOARD = {
    pending_payment: 'Pendiente de pago',
    confirmed: 'Confirmado',
    preparing: 'En preparación',
    on_route: 'En camino',
    delivered: 'Entregado',
    cancelled: 'Cancelado'
};

document.addEventListener("DOMContentLoaded", () => {
    cargarResumen();
    cargarPedidosPorEstado();

    document.getElementById("revenueRangeButtons").addEventListener("click", (e) => {
        const btn = e.target.closest("button[data-range]");
        if (!btn) return;

        document.querySelectorAll("#revenueRangeButtons button").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");

        const customInputs = document.getElementById("customRangeInputs");
        if (btn.dataset.range === "custom") {
            customInputs.classList.remove("d-none");
        } else {
            customInputs.classList.add("d-none");
            cargarIngresos(btn.dataset.range);
        }
    });

    document.getElementById("applyCustomRangeBtn").addEventListener("click", () => {
        const start = document.getElementById("customStartDate").value;
        const end = document.getElementById("customEndDate").value;
        if (!start || !end) {
            adminAlert("Selecciona ambas fechas.", { type: 'warning', title: 'Falta información' });
            return;
        }
        cargarIngresos("custom", start, end);
    });

    document.getElementById("exportOrdersExcelBtn").addEventListener("click", () => {
        if (!rangoActual.start || !rangoActual.end) {
            adminAlert('Primero selecciona un rango de fechas.', { type: 'warning' });
            return;
        }
        const url = `/api/dashboard/export/orders-excel?start=${rangoActual.start}&end=${rangoActual.end}`;
        const nombre = `pedidos_${formatearFechaArchivo(rangoActual.start)}_a_${formatearFechaArchivo(rangoActual.end)}.xlsx`;
        descargarReporte(url, nombre);
    });

    document.getElementById("exportSalesExcelBtn").addEventListener("click", () => {
        if (!rangoActual.start || !rangoActual.end) {
            adminAlert('Primero selecciona un rango de fechas.', { type: 'warning' });
            return;
        }
        const url = `/api/dashboard/export/sales-excel?start=${rangoActual.start}&end=${rangoActual.end}`;
        const nombre = `ventas_${formatearFechaArchivo(rangoActual.start)}_a_${formatearFechaArchivo(rangoActual.end)}.xlsx`;
        descargarReporte(url, nombre);
    });

    document.getElementById("exportSalesPdfBtn").addEventListener("click", () => {
        if (!rangoActual.start || !rangoActual.end) {
            adminAlert('Primero selecciona un rango de fechas.', { type: 'warning' });
            return;
        }
        const url = `/api/dashboard/export/sales-pdf?start=${rangoActual.start}&end=${rangoActual.end}`;
        const nombre = `ventas_${formatearFechaArchivo(rangoActual.start)}_a_${formatearFechaArchivo(rangoActual.end)}.pdf`;
        descargarReporte(url, nombre);
    });

    document.getElementById("exportOrdersPdfBtn").addEventListener("click", () => {
        if (!rangoActual.start || !rangoActual.end) {
            adminAlert('Primero selecciona un rango de fechas.', { type: 'warning' });
            return;
        }
        const url = `/api/dashboard/export/orders-pdf?start=${rangoActual.start}&end=${rangoActual.end}`;
        const nombre = `pedidos_${formatearFechaArchivo(rangoActual.start)}_a_${formatearFechaArchivo(rangoActual.end)}.pdf`;
        descargarReporte(url, nombre);
    });

    cargarIngresos("30d");
});

function authHeadersAdmin() {
    return { 'Accept': 'application/json', 'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}` };
}

// ==========================================
// BLOQUE 1: RESUMEN
// ==========================================
async function cargarResumen() {
    try {
        const response = await fetch('/api/dashboard/summary', { headers: authHeadersAdmin(), cache: 'no-store' });
        const data = await response.json();
        if (!data.success) return;

        const s = data.data;
        document.getElementById("summaryMonthlyRevenue").textContent = `Q ${s.monthlyRevenue.toFixed(2)}`;
        document.getElementById("summaryPendingOrders").textContent = s.pendingOrders;
        document.getElementById("summaryActiveProducts").textContent = s.activeProducts;
        document.getElementById("summaryActiveAuctions").textContent = s.activeAuctions;

    } catch (error) {
        console.error('Error al cargar resumen:', error);
    }
}

// ==========================================
// BLOQUE 2: INGRESOS
// ==========================================
function formatearFechaLocal(d) {
    const año = d.getFullYear();
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const dia = String(d.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}

function formatearFechaLocal(d) {
    const año = d.getFullYear();
    const mes = String(d.getMonth() + 1).padStart(2, '0');
    const dia = String(d.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}

function calcularRangoFechas(range) {
    const hoy = new Date();

    if (range === 'today') {
        return { start: formatearFechaLocal(hoy), end: formatearFechaLocal(hoy) };
    }
    if (range === '7d') {
        const inicio = new Date(hoy);
        inicio.setDate(inicio.getDate() - 6);
        return { start: formatearFechaLocal(inicio), end: formatearFechaLocal(hoy) };
    }
    if (range === '30d') {
        const inicio = new Date(hoy);
        inicio.setDate(inicio.getDate() - 29);
        return { start: formatearFechaLocal(inicio), end: formatearFechaLocal(hoy) };
    }
    return null;
}

async function cargarIngresos(range, customStart = null, customEnd = null) {
    let start, end;

    if (range === 'custom') {
        start = customStart;
        end = customEnd;

        const hoy = obtenerFechaLocalHoy();

        if (end > hoy) {
            adminAlert('La fecha "Hasta" no puede ser una fecha futura.', { type: 'error', title: 'Rango inválido' });
            return;
        }

        if (start > end) {
            adminAlert('La fecha "Desde" no puede ser posterior a la fecha "Hasta".', { type: 'error', title: 'Rango inválido' });
            return;
        }
    } else {
        const rango = calcularRangoFechas(range);
        start = rango.start;
        end = rango.end;
    }

    rangoActual = { start, end };

    try {
        const response = await fetch(`/api/dashboard/revenue?start=${start}&end=${end}`, {
            headers: authHeadersAdmin(),
            cache: 'no-store'
        });
        const data = await response.json();
        if (!data.success) return;

        const r = data.data;
        document.getElementById("revenueTotalAmount").textContent = `Q ${r.totalRevenue.toFixed(2)}`;
        document.getElementById("revenueTotalProfit").textContent = `Ganancia: Q ${r.totalProfit.toFixed(2)}`;
        renderizarGraficaIngresos(r.dailyBreakdown);
        cargarTopCategorias(start, end);
        document.getElementById("topCategoriesPeriodLabel").textContent = describirPeriodo(range, start, end);

        cargarIngresosPorTipoVenta(start, end);
        document.getElementById("saleTypePeriodLabel").textContent = describirPeriodo(range, start, end);
        

    } catch (error) {
        console.error('Error al cargar ingresos:', error);
    }
}

function renderizarGraficaIngresos(dailyBreakdown) {
    const ctx = document.getElementById('revenueChart');

    const labels = dailyBreakdown.map(d => d.date.slice(5)); // MM-DD
    const valores = dailyBreakdown.map(d => d.revenue);

    if (revenueChartInstance) {
        revenueChartInstance.data.labels = labels;
        revenueChartInstance.data.datasets[0].data = valores;
        revenueChartInstance.update();
        return;
    }

    revenueChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Ingresos (Q)',
                data: valores,
                borderColor: '#16A34A',
                backgroundColor: 'rgba(22, 163, 74, 0.12)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { maxTicksLimit: 7 }
                }
            }
        }
    });
}

// Fecha de HOY según el reloj local del navegador — nunca uses
// toISOString() para esto, convierte a UTC y puede dar el día
// equivocado según la zona horaria del servidor/navegador.
function obtenerFechaLocalHoy() {
    const hoy = new Date();
    const año = hoy.getFullYear();
    const mes = String(hoy.getMonth() + 1).padStart(2, '0');
    const dia = String(hoy.getDate()).padStart(2, '0');
    return `${año}-${mes}-${dia}`;
}

document.getElementById('customEndDate').max = obtenerFechaLocalHoy();

// ==========================================
// BLOQUE 3: PEDIDOS
// ==========================================
async function cargarPedidosPorEstado() {
    try {
        const response = await fetch('/api/dashboard/order-stats', { headers: authHeadersAdmin(), cache: 'no-store' });
        const data = await response.json();
        if (!data.success) return;

        const s = data.data;

        renderizarGraficaEstados(s.statusCounts);
        renderizarPedidosAtascados(s.stuckOrders);

    } catch (error) {
        console.error('Error al cargar estadísticas de pedidos:', error);
    }
}

function renderizarGraficaEstados(statusCounts) {
    const ctx = document.getElementById('ordersStatusChart');
    const labels = Object.keys(statusCounts).map(k => ORDER_STATUS_LABELS_DASHBOARD[k] || k);
    const valores = Object.values(statusCounts);

    if (ordersStatusChartInstance) {
        ordersStatusChartInstance.data.datasets[0].data = valores;
        ordersStatusChartInstance.update();
        return;
    }

    ordersStatusChartInstance = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: valores,
                backgroundColor: ['#ffc107', '#0dcaf0', '#6f42c1', '#fd7e14', '#198754', '#dc3545']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });
}

function renderizarPedidosAtascados(stuckOrders) {
    const container = document.getElementById("stuckOrdersList");

    if (stuckOrders.length === 0) {
        container.innerHTML = `<div class="list-group-item text-muted">No hay pedidos atascados. 🎉</div>`;
        return;
    }

    container.innerHTML = stuckOrders.map(o => `
        <a href="/admin/pedidos" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            <span>Pedido #${o.id} — ${ORDER_STATUS_LABELS_DASHBOARD[o.status] || o.status}</span>
            <span class="badge bg-danger rounded-pill">${o.daysSinceUpdate} día(s)</span>
        </a>
    `).join('');
}

async function cargarTopCategorias(start, end) {
    try {
        const response = await fetch(`/api/dashboard/top-categories?start=${start}&end=${end}`, {
            headers: authHeadersAdmin(),
            cache: 'no-store'
        });
        const data = await response.json();
        if (!data.success) return;

        renderizarTopCategorias(data.data);

    } catch (error) {
        console.error('Error al cargar top de categorías:', error);
    }
}

function renderizarTopCategorias(categorias) {
    const container = document.getElementById("topCategoriesList");

    if (categorias.length === 0) {
        container.innerHTML = `<div class="list-group-item text-muted">No hay ventas registradas en este periodo.</div>`;
        return;
    }

    const medallas = ['🥇', '🥈', '🥉', '4°', '5°'];
    const maxIngreso = Math.max(...categorias.map(c => c.revenue));

    container.innerHTML = categorias.map((cat, index) => `
        <div class="list-group-item">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span><strong>${medallas[index] || (index + 1) + '°'}</strong> ${cat.categoryName}</span>
                <span class="fw-bold text-success">Q ${cat.revenue.toFixed(2)}</span>
            </div>
            <div class="progress" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: ${(cat.revenue / maxIngreso * 100).toFixed(0)}%"></div>
            </div>
        </div>
    `).join('');
}

function describirPeriodo(range, start, end) {
    if (range === 'today') return 'Hoy';
    if (range === '7d') return 'Últimos 7 días';
    if (range === '30d') return 'Últimos 30 días';
    // Personalizado: convertimos YYYY-MM-DD a DD/MM/YY para mostrar
    return `${formatearFechaLegible(start)} al ${formatearFechaLegible(end)}`;
}

function formatearFechaLegible(fechaISO) {
    // fechaISO viene como "2026-09-07"
    const [año, mes, dia] = fechaISO.split('-');
    return `${dia}/${mes}/${año.slice(2)}`; // 07/09/26
}

function formatearFechaArchivo(fechaISO) {
    // "2026-09-07" → "07-09-26" (con guiones, no barras, por ser nombre de archivo)
    const [año, mes, dia] = fechaISO.split('-');
    return `${dia}-${mes}-${año.slice(2)}`;
}


//GRAFICA DE INGRESOS DE SUVASTAS VS VENTA DIRECTAAAAA
async function cargarIngresosPorTipoVenta(start, end) {
    try {
        const response = await fetch(`/api/dashboard/revenue-by-sale-type?start=${start}&end=${end}`, {
            headers: authHeadersAdmin(),
            cache: 'no-store'
        });
        const data = await response.json();
        if (!data.success) return;

        document.getElementById("revenueDirect").textContent = `Q ${data.data.directRevenue.toFixed(2)}`;
        document.getElementById("revenueAuction").textContent = `Q ${data.data.auctionRevenue.toFixed(2)}`;

    } catch (error) {
        console.error('Error al cargar ingresos por tipo de venta:', error);
    }
}


//PARA LOS REPORTES
async function descargarReporte(url, nombrePorDefecto) {
    try {
        const response = await fetch(url, {
            headers: authHeadersAdmin(),
            cache: 'no-store'
        });

        if (!response.ok) {
            adminAlert('No se pudo generar el reporte. Intenta de nuevo.', { type: 'error' });
            return;
        }

        // Recibimos el archivo como "blob" (datos binarios) y forzamos la descarga
        const blob = await response.blob();
        const urlBlob = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = urlBlob;
        a.download = nombrePorDefecto;
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(urlBlob);

    } catch (error) {
        console.error('Error al descargar reporte:', error);
        adminAlert('Ocurrió un error al descargar el reporte.', { type: 'error' });
    }
}



