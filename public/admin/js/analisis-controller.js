// Panel: reportes del data warehouse (Análisis)
document.addEventListener("DOMContentLoaded", () => {
    const API = "/api/admin/analisis";
    const selectorMeses = document.getElementById("analisisMeses");
    const botonActualizar = document.getElementById("btnActualizarDw");
    const graficas = {};

    const quetzales = (n) => "Q" + Number(n || 0).toLocaleString("es-GT", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function headers() {
        return {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": `Bearer ${localStorage.getItem("admin_auth_token")}`
        };
    }

    async function api(url, opciones = {}) {
        const respuesta = await fetch(url, { ...opciones, headers: headers() });
        const datos = await respuesta.json().catch(() => ({}));
        if (!respuesta.ok) throw new Error(datos.message || "No se pudo cargar el análisis.");
        return datos.data;
    }

    function hace(fechaUtc) {
        const minutos = Math.round((Date.now() - new Date(fechaUtc.replace(" ", "T") + "Z")) / 60000);
        if (minutos < 1) return "hace un momento";
        if (minutos < 60) return `hace ${minutos} min`;
        const horas = Math.round(minutos / 60);
        return horas < 24 ? `hace ${horas} h` : `hace ${Math.round(horas / 24)} día(s)`;
    }

    function grafica(id, config) {
        if (graficas[id]) graficas[id].destroy();
        graficas[id] = new Chart(document.getElementById(id), config);
    }

    // ---------- Pintar ----------
    function pintar(d) {
        const carga = document.getElementById("dwUltimaCarga");
        if (!d.ultimaCarga) {
            carga.textContent = "El data warehouse todavía no tiene cargas.";
        } else {
            const error = d.ultimaCarga.estado === "error" ? ` · ⚠ la última carga falló: ${d.ultimaCarga.mensaje || ""}` : "";
            carga.textContent = `Última carga del data warehouse: ${hace(d.ultimaCarga.terminadoEn)} (se actualiza cada hora)${error}`;
        }

        const hayDatos = d.tendencia.some(m => m.ingreso !== 0 || m.ingresoAnioAnterior !== 0) || d.resumen.pedidos > 0;
        document.getElementById("analisisVacio").classList.toggle("d-none", hayDatos);
        document.getElementById("analisisContenido").classList.toggle("d-none", !hayDatos);
        if (!hayDatos) return;

        document.getElementById("kpiIngreso").textContent = quetzales(d.resumen.ingreso);
        document.getElementById("kpiGanancia").textContent = quetzales(d.resumen.ganancia);
        document.getElementById("kpiPedidos").textContent = d.resumen.pedidos;
        document.getElementById("kpiTicket").textContent = quetzales(d.resumen.ticketPromedio);

        grafica("chartTendencia", {
            type: "bar",
            data: {
                labels: d.tendencia.map(m => m.etiqueta),
                datasets: [
                    { label: "Este año", data: d.tendencia.map(m => m.ingreso), backgroundColor: "#16A34A", borderRadius: 4 },
                    { label: "Año anterior", data: d.tendencia.map(m => m.ingresoAnioAnterior), backgroundColor: "#CBD5E1", borderRadius: 4 }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: "bottom" }, tooltip: { callbacks: { label: c => `${c.dataset.label}: ${quetzales(c.raw)}` } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => quetzales(v) } } }
            }
        });

        grafica("chartDias", {
            type: "bar",
            data: {
                labels: d.diasSemana.map(x => x.dia.slice(0, 3)),
                datasets: [{ label: "Ingreso", data: d.diasSemana.map(x => x.ingreso), backgroundColor: "#22C55E", borderRadius: 4 }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: c => quetzales(c.raw) } } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => quetzales(v) } } }
            }
        });

        pintarMargen(d.categorias);
        pintarSubastas(d.subastas);

        grafica("chartClientes", {
            type: "doughnut",
            data: {
                labels: ["Nuevos", "Recurrentes"],
                datasets: [{ data: [d.clientes.nuevos, d.clientes.recurrentes], backgroundColor: ["#22C55E", "#0EA5E9"] }]
            },
            options: { responsive: true, plugins: { legend: { position: "bottom" } } }
        });

        pintarTiempoVenta(d.tiempoVenta);
    }

    function pintarMargen(categorias) {
        const cuerpo = document.getElementById("tablaMargen");
        if (categorias.length === 0) {
            cuerpo.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Sin ventas en este período.</td></tr>`;
            return;
        }
        cuerpo.innerHTML = categorias.map(c => {
            const margen = c.margenPct === null ? null : Math.max(0, Math.min(100, c.margenPct));
            const nombre = c.principal === c.categoria ? escapeHtml(c.categoria) : `${escapeHtml(c.principal)} <span class="text-muted">→ ${escapeHtml(c.categoria)}</span>`;
            return `
                <tr>
                    <td>${nombre}</td>
                    <td class="text-end">${c.unidades}</td>
                    <td class="text-end">${quetzales(c.ingreso)}</td>
                    <td class="text-end">${c.ganancia === null ? '<span class="text-muted">—</span>' : quetzales(c.ganancia)}</td>
                    <td>${margen === null ? '<span class="text-muted small">Sin costo registrado</span>' : `
                        <div class="dw-barra"><span style="width:${margen}%"></span></div>
                        <small class="fw-bold">${c.margenPct}%</small>`}
                    </td>
                </tr>`;
        }).join("");
    }

    function pintarSubastas(s) {
        const bloque = document.getElementById("bloqueSubastas");
        if (s.total === 0) {
            bloque.innerHTML = `<p class="text-muted text-center my-4">No terminaron subastas en este período.</p>`;
            return;
        }
        const r = s.porResultado;
        bloque.innerHTML = `
            <div class="dw-subasta-precios">
                <div><span>Precio inicial</span><strong>${quetzales(s.precioInicialPromedio)}</strong></div>
                <i class="fas fa-arrow-right"></i>
                <div><span>Precio final</span><strong>${quetzales(s.precioFinalPromedio)}</strong></div>
                <div class="dw-subasta-subida">+${s.incrementoPromedioPct}%</div>
            </div>
            <p class="small text-muted mb-3">Promedio de las subastas vendidas · ${s.pujasPromedio} pujas y ${s.postoresPromedio} postores por subasta</p>
            <div class="dw-subasta-resultados">
                <div><strong>${r.vendida}</strong><span>Vendidas</span></div>
                <div><strong>${r.sin_pujas}</strong><span>Sin pujas</span></div>
                <div><strong>${r.no_reclamada}</strong><span>No reclamadas</span></div>
                <div><strong>${r.cancelada}</strong><span>Canceladas</span></div>
            </div>`;
    }

    function pintarTiempoVenta(filas) {
        const bloque = document.getElementById("bloqueTiempoVenta");
        if (filas.length === 0) {
            bloque.innerHTML = `<p class="text-muted text-center my-4">Sin ventas en este período.</p>`;
            return;
        }
        const maximo = Math.max(...filas.map(f => f.dias), 1);
        bloque.innerHTML = filas.map(f => `
            <div class="dw-tiempo">
                <div class="d-flex justify-content-between small"><span>${escapeHtml(f.categoria)}</span><strong>${f.dias} días</strong></div>
                <div class="dw-barra dw-barra-azul"><span style="width:${(f.dias / maximo) * 100}%"></span></div>
            </div>`).join("") + `<p class="small text-muted mt-2 mb-0">Desde que se publica un producto hasta que se confirma su venta.</p>`;
    }

    // ---------- Cargar ----------
    async function cargar() {
        try {
            pintar(await api(`${API}?meses=${selectorMeses.value}`));
        } catch (e) {
            document.getElementById("dwUltimaCarga").textContent = e.message;
        }
    }

    botonActualizar.addEventListener("click", async () => {
        botonActualizar.disabled = true;
        botonActualizar.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Actualizando…`;
        try {
            const r = await api(`${API}/actualizar`, { method: "POST" });
            await cargar();
            adminAlert(`Se cargaron ${r.ventas} venta(s), ${r.anulaciones} anulación(es) y ${r.subastas} subasta(s).`, { title: "Data warehouse actualizado" });
        } catch (e) {
            adminAlert(e.message, { type: "error" });
        } finally {
            botonActualizar.disabled = false;
            botonActualizar.innerHTML = `<i class="fas fa-rotate me-1"></i> Actualizar ahora`;
        }
    });

    selectorMeses.addEventListener("change", cargar);
    cargar();
});
