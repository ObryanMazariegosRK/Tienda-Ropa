// Panel: bitácora (solo lectura). Los registros vienen de la base tienda_dw.
document.addEventListener("DOMContentLoaded", () => {
    const API = "/api/admin/bitacora";
    const tabla = document.getElementById("tablaBitacora");
    const paginacion = document.getElementById("paginacionBitacora");
    const selectorAccion = document.getElementById("filtroAccion");
    const modal = new bootstrap.Modal(document.getElementById("modalBitacora"));
    let registros = [];
    let totalRegistros = 0;
    let filtrosActuales = {};
    let pagina = 1;

    // Nombre legible de cada acción para el filtro y las etiquetas
    const NOMBRES = {
        "sesion.inicio": "Inicio de sesión", "sesion.inicio_fallida": "Inicio de sesión fallido",
        "sesion.inicio_google": "Inicio de sesión con Google", "sesion.inicio_google_fallida": "Inicio con Google fallido",
        "sesion.cierre": "Cierre de sesión", "cuenta.registro": "Registro de cuenta",
        "cuenta.contrasena_restablecida": "Contraseña restablecida",
        "pedido.creado": "Pedido realizado", "pedido.estado_cambiado": "Cambio de estado de pedido",
        "subasta.pedido_confirmado": "Subasta ganada confirmada", "subasta.rechazada_por_ganador": "Subasta ganada rechazada",
        "subasta.duracion_extendida": "Subasta extendida",
        "producto.creado": "Producto creado", "producto.editado": "Producto editado", "producto.eliminado": "Producto eliminado",
        "categoria.creada": "Categoría creada", "categoria.editada": "Categoría editada", "categoria.eliminada": "Categoría eliminada",
        "banner.creado": "Banner creado", "banner.archivos_agregados": "Archivos de banner agregados",
        "banner.archivo_quitado": "Archivo de banner quitado", "banner.renombrado": "Banner renombrado",
        "banner.eliminado": "Banner eliminado", "banner.activacion": "Banner activado/desactivado",
        "correo.cuenta_agregada": "Cuenta de correo agregada", "correo.cuenta_editada": "Cuenta de correo editada",
        "correo.cuenta_eliminada": "Cuenta de correo eliminada", "correo.cuenta_en_uso": "Cuenta de correo en uso"
    };
    const nombreAccion = (accion) => NOMBRES[accion] || accion;
    // Color de la etiqueta según el tipo de acción
    const claseAccion = (accion) => accion.startsWith("sesion.") || accion.startsWith("cuenta.") ? "dw-accion-sesion"
        : accion.endsWith("eliminado") || accion.endsWith("eliminada") || accion.endsWith("fallida") ? "dw-accion-peligro"
        : accion.startsWith("pedido.") || accion.startsWith("subasta.") ? "dw-accion-pedido" : "dw-accion-catalogo";

    // Fecha de HOY según el reloj del navegador (igual que el filtro de ingresos del dashboard)
    function fechaLocalHoy() {
        const hoy = new Date();
        return `${hoy.getFullYear()}-${String(hoy.getMonth() + 1).padStart(2, "0")}-${String(hoy.getDate()).padStart(2, "0")}`;
    }

    // Mismas reglas que el filtro de ingresos del dashboard
    function rangoValido() {
        const desde = document.getElementById("filtroDesde").value;
        const hasta = document.getElementById("filtroHasta").value;

        if ((desde && desde > fechaLocalHoy()) || (hasta && hasta > fechaLocalHoy())) {
            adminAlert('Las fechas no pueden ser futuras.', { type: 'error', title: 'Rango inválido' });
            return false;
        }
        if (desde && hasta && desde > hasta) {
            adminAlert('La fecha "Desde" no puede ser posterior a la fecha "Hasta".', { type: 'error', title: 'Rango inválido' });
            return false;
        }
        return true;
    }

    function fechaGT(utc) {
        return new Date(utc.replace(" ", "T") + "Z").toLocaleString("es-GT", {
            timeZone: "America/Guatemala", year: "numeric", month: "short", day: "numeric", hour: "2-digit", minute: "2-digit", second: "2-digit"
        });
    }

    // Página 1 reemplaza la tabla; "Ver más" agrega la siguiente debajo con los mismos filtros
    async function cargar(nuevaPagina = 1) {
        const agregar = nuevaPagina > 1;
        if (!agregar) {
            filtrosActuales = {
                desde: document.getElementById("filtroDesde").value,
                hasta: document.getElementById("filtroHasta").value,
                accion: selectorAccion.value,
                usuario: document.getElementById("filtroUsuario").value.trim()
            };
        }
        pagina = nuevaPagina;
        const params = new URLSearchParams({ page: pagina });
        Object.entries(filtrosActuales).forEach(([k, v]) => { if (v) params.set(k, v); });

        const botonVerMas = paginacion.querySelector("button");
        if (agregar && botonVerMas) {
            botonVerMas.disabled = true;
            botonVerMas.textContent = "Cargando…";
        } else {
            tabla.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Cargando…</td></tr>`;
            paginacion.innerHTML = "";
        }

        try {
            const respuesta = await fetch(`${API}?${params}`, {
                headers: { "Accept": "application/json", "Authorization": `Bearer ${localStorage.getItem("admin_auth_token")}` }
            });
            const datos = await respuesta.json();
            if (!respuesta.ok) throw new Error(datos.message || "No se pudo cargar la bitácora.");
            pintarAcciones(datos.acciones);
            pintar(datos.data, datos.meta, agregar);
        } catch (e) {
            if (agregar) {
                adminAlert(e.message, { type: "error" });
                pintarVerMas(totalRegistros);
            } else {
                tabla.innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">${escapeHtml(e.message)}</td></tr>`;
            }
        }
    }

    function pintarAcciones(acciones) {
        const elegida = selectorAccion.value;
        selectorAccion.innerHTML = `<option value="">Todas</option>` + acciones
            .map(a => `<option value="${escapeHtml(a)}" ${a === elegida ? "selected" : ""}>${escapeHtml(nombreAccion(a))}</option>`).join("");
    }

    function filaHtml(r, indice) {
        return `
            <tr class="${r.resultado === "rechazado" ? "dw-fila-rechazada" : ""}">
                <td class="small text-nowrap">${fechaGT(r.ocurrido_en)}</td>
                <td class="small">${r.usuario_correo ? escapeHtml(r.usuario_correo) : '<span class="text-muted">Visitante</span>'}
                    ${r.usuario_rol === "admin" ? '<span class="dw-rol">admin</span>' : ""}</td>
                <td><span class="dw-accion ${claseAccion(r.accion)}">${escapeHtml(nombreAccion(r.accion))}</span></td>
                <td class="small">${escapeHtml(r.descripcion)}</td>
                <td class="text-end"><button type="button" class="btn btn-sm btn-outline-secondary" data-indice="${indice}" title="Ver detalle"><i class="fas fa-eye"></i></button></td>
            </tr>`;
    }

    function pintar(filas, meta, agregar) {
        if (!agregar) registros = [];
        const inicio = registros.length;
        registros = registros.concat(filas);
        totalRegistros = meta.total;

        if (registros.length === 0) {
            tabla.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">No hay registros con estos filtros.</td></tr>`;
            paginacion.innerHTML = "";
            return;
        }

        const html = filas.map((r, i) => filaHtml(r, inicio + i)).join("");
        if (agregar) {
            tabla.insertAdjacentHTML("beforeend", html);
        } else {
            tabla.innerHTML = html;
        }
        pintarVerMas(meta.total);
    }

    function pintarVerMas(total) {
        const restantes = total - registros.length;
        paginacion.innerHTML = restantes > 0
            ? `<button type="button" class="btn btn-outline-success">Ver más (${restantes} restante${restantes === 1 ? "" : "s"})</button>
               <p class="small text-muted mt-2 mb-0">Mostrando ${registros.length} de ${total} registros</p>`
            : `<p class="small text-muted mb-0">Mostrando los ${total} registro${total === 1 ? "" : "s"}</p>`;
        paginacion.querySelector("button")?.addEventListener("click", () => cargar(pagina + 1));
    }

    function bloqueDatos(titulo, datos) {
        if (!datos) return "";
        const filas = Object.entries(datos).map(([k, v]) => `<tr><th class="small fw-normal text-muted">${escapeHtml(k)}</th><td class="small">${escapeHtml(v === null ? "—" : String(v))}</td></tr>`).join("");
        return `<h6 class="mt-3">${titulo}</h6><table class="table table-sm mb-0">${filas}</table>`;
    }

    tabla.addEventListener("click", (e) => {
        const boton = e.target.closest("button[data-indice]");
        if (!boton) return;
        const r = registros[Number(boton.dataset.indice)];
        document.getElementById("detalleBitacora").innerHTML = `
            <p class="mb-1"><strong>${escapeHtml(r.descripcion)}</strong></p>
            <p class="small text-muted mb-2">${fechaGT(r.ocurrido_en)} · ${escapeHtml(nombreAccion(r.accion))} · ${r.resultado === "exito" ? "Realizado" : "Rechazado"}</p>
            <table class="table table-sm mb-0">
                <tr><th class="small fw-normal text-muted">Usuario</th><td class="small">${escapeHtml(r.usuario_correo || "Visitante")} ${r.usuario_rol ? "(" + escapeHtml(r.usuario_rol) + ")" : ""}</td></tr>
                <tr><th class="small fw-normal text-muted">Registro afectado</th><td class="small">${escapeHtml((r.entidad || "—") + (r.entidad_id ? " #" + r.entidad_id : ""))}</td></tr>
                <tr><th class="small fw-normal text-muted">IP</th><td class="small">${escapeHtml(r.ip || "—")}</td></tr>
                <tr><th class="small fw-normal text-muted">Navegador</th><td class="small">${escapeHtml(r.user_agent || "—")}</td></tr>
            </table>
            ${bloqueDatos("Antes", r.datos_antes)}
            ${bloqueDatos("Datos enviados", r.datos_despues)}`;
        modal.show();
    });

    // El calendario no deja elegir días futuros
    document.getElementById("filtroDesde").max = fechaLocalHoy();
    document.getElementById("filtroHasta").max = fechaLocalHoy();

    document.getElementById("filtrosBitacora").addEventListener("submit", (e) => {
        e.preventDefault();
        if (rangoValido()) cargar(1);
    });
    document.getElementById("limpiarFiltros").addEventListener("click", () => {
        document.getElementById("filtrosBitacora").reset();
        cargar(1);
    });

    cargar();
});
