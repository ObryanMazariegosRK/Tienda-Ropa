// Panel: cuenta de correo con la que se envían los códigos a los clientes
document.addEventListener("DOMContentLoaded", () => {
    const API_URL = "/api/admin/mail-senders";
    const lista = document.getElementById("mailSendersList");
    const senderModal = new bootstrap.Modal(document.getElementById("mailSenderModal"));
    const testModal = new bootstrap.Modal(document.getElementById("mailTestModal"));
    const senderForm = document.getElementById("mailSenderForm");
    const testForm = document.getElementById("mailTestForm");
    let cuentas = [];
    let correoAdmin = "";

    function headers() {
        return {
            "Accept": "application/json",
            "Content-Type": "application/json",
            "Authorization": `Bearer ${localStorage.getItem("admin_auth_token")}`
        };
    }

    // Devuelve el JSON; si la API responde error, lanza el mensaje para mostrarlo
    async function api(url, options = {}) {
        const response = await fetch(url, { ...options, headers: headers() });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const errorValidacion = data.errors ? Object.values(data.errors)[0][0] : null;
            throw new Error(errorValidacion || data.message || "Ocurrió un error inesperado.");
        }
        return data;
    }

    function formatearFecha(iso) {
        return new Date(iso).toLocaleString("es-GT", { dateStyle: "medium", timeStyle: "short" });
    }

    // ---------- Pintar ----------
    function pintar(ajustes) {
        cuentas = ajustes.senders;
        const activa = cuentas.find(c => c.isActive);

        const valor = document.getElementById("mailCurrentValue");
        const pista = document.getElementById("mailCurrentHint");
        const caja = document.getElementById("mailCurrent");
        document.getElementById("btnUsarServidor").classList.toggle("d-none", !activa);

        if (activa) {
            valor.innerHTML = `${escapeHtml(activa.fromName)} <span class="mail-current-email">&lt;${escapeHtml(activa.email)}&gt;</span>`;
            pista.textContent = activa.lastTestedAt
                ? "Cuenta escogida en este panel."
                : "Cambiaste sus datos y aún no la has probado: envía un correo de prueba.";
            caja.classList.toggle("mail-current-warning", !activa.lastTestedAt);
        } else {
            valor.innerHTML = ajustes.defaultEmail
                ? `Correo del servidor <span class="mail-current-email">&lt;${escapeHtml(ajustes.defaultEmail)}&gt;</span>`
                : "Correo del servidor";
            pista.textContent = "Es la cuenta configurada en el archivo .env. Agrega una cuenta y déjala en uso para cambiarla.";
            caja.classList.remove("mail-current-warning");
        }

        if (cuentas.length === 0) {
            lista.innerHTML = `
                <div class="mail-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Aún no hay cuentas registradas.<br>Mientras tanto se usa el correo del servidor.</p>
                </div>`;
            return;
        }

        lista.innerHTML = cuentas.map(c => {
            let estado;
            if (c.isActive && c.lastTestedAt) estado = `<span class="mail-pill mail-pill-active"><i class="fas fa-circle-check"></i> En uso</span>`;
            else if (c.isActive) estado = `<span class="mail-pill mail-pill-warning"><i class="fas fa-triangle-exclamation"></i> En uso · sin probar</span>`;
            else if (c.lastTestedAt) estado = `<span class="mail-pill mail-pill-ok">Probada</span>`;
            else estado = `<span class="mail-pill mail-pill-muted">Sin probar</span>`;

            const probada = c.lastTestedAt ? `Última prueba: ${formatearFecha(c.lastTestedAt)}` : "Envía un correo de prueba antes de usarla";
            const servidor = c.host === "smtp.gmail.com" && c.port === 587 ? "" : ` · ${escapeHtml(c.host)}:${c.port}`;

            return `
                <div class="mail-row ${c.isActive ? "mail-row-active" : ""}">
                    <div class="mail-row-info">
                        <div class="mail-row-name">${escapeHtml(c.fromName)} ${estado}</div>
                        <div class="mail-row-email">${escapeHtml(c.email)}${servidor}</div>
                        <div class="mail-row-meta">${probada}</div>
                    </div>
                    <div class="mail-row-actions">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-accion="probar" data-id="${c.id}" title="Enviar correo de prueba">
                            <i class="fas fa-paper-plane"></i><span class="d-none d-lg-inline ms-1">Probar</span>
                        </button>
                        ${c.isActive ? "" : `
                        <button type="button" class="btn btn-sm btn-success" data-accion="usar" data-id="${c.id}" ${c.lastTestedAt ? "" : "disabled"} title="${c.lastTestedAt ? "Usar esta cuenta" : "Primero envía un correo de prueba"}">
                            <i class="fas fa-check"></i><span class="d-none d-lg-inline ms-1">Usar esta cuenta</span>
                        </button>`}
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-accion="editar" data-id="${c.id}" title="Editar"><i class="fas fa-pen"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-accion="eliminar" data-id="${c.id}" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>
                </div>`;
        }).join("");
    }

    async function cargar() {
        try {
            const res = await api(API_URL);
            pintar(res.data);
        } catch (e) {
            lista.innerHTML = `<p class="text-danger text-center py-4 mb-0">${escapeHtml(e.message)}</p>`;
        }
    }

    // ---------- Agregar / editar ----------
    function abrirFormulario(cuenta = null) {
        senderForm.reset();
        document.getElementById("mailSenderError").classList.add("d-none");
        document.getElementById("senderId").value = cuenta ? cuenta.id : "";
        document.getElementById("mailSenderModalTitle").textContent = cuenta ? "Editar cuenta" : "Agregar cuenta";
        document.getElementById("senderFromName").value = cuenta ? cuenta.fromName : "";
        document.getElementById("senderEmail").value = cuenta ? cuenta.email : "";
        document.getElementById("senderHost").value = cuenta && cuenta.host !== "smtp.gmail.com" ? cuenta.host : "";
        document.getElementById("senderPort").value = cuenta && cuenta.port !== 587 ? cuenta.port : "";

        const password = document.getElementById("senderPassword");
        password.required = !cuenta;
        password.placeholder = cuenta ? "Déjala vacía para conservar la actual" : "abcd efgh ijkl mnop";
        document.querySelector(".mail-advanced").open = Boolean(cuenta && (cuenta.host !== "smtp.gmail.com" || cuenta.port !== 587));
        senderModal.show();
    }

    senderForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const id = document.getElementById("senderId").value;
        const boton = document.getElementById("mailSenderSubmit");
        const error = document.getElementById("mailSenderError");
        error.classList.add("d-none");
        boton.disabled = true;

        try {
            await api(id ? `${API_URL}/${id}` : API_URL, {
                method: id ? "PUT" : "POST",
                body: JSON.stringify({
                    fromName: document.getElementById("senderFromName").value,
                    email: document.getElementById("senderEmail").value,
                    password: document.getElementById("senderPassword").value || null,
                    host: document.getElementById("senderHost").value || null,
                    port: document.getElementById("senderPort").value || null
                })
            });
            senderModal.hide();
            await cargar();

            // Recién creada: lo siguiente es probarla
            if (!id) {
                const nueva = cuentas[cuentas.length - 1];
                if (nueva) abrirPrueba(nueva);
            }
        } catch (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        } finally {
            boton.disabled = false;
        }
    });

    // ---------- Correo de prueba ----------
    function abrirPrueba(cuenta) {
        document.getElementById("mailTestError").classList.add("d-none");
        document.getElementById("testSenderId").value = cuenta.id;
        document.getElementById("testSenderEmail").textContent = cuenta.email;
        document.getElementById("testTo").value = correoAdmin;
        testModal.show();
    }

    testForm.addEventListener("submit", async (e) => {
        e.preventDefault();
        const boton = document.getElementById("mailTestSubmit");
        const error = document.getElementById("mailTestError");
        error.classList.add("d-none");
        boton.disabled = true;
        boton.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Enviando...`;

        try {
            const res = await api(`${API_URL}/${document.getElementById("testSenderId").value}/test`, {
                method: "POST",
                body: JSON.stringify({ to: document.getElementById("testTo").value })
            });
            testModal.hide();
            pintar(res.data);
            adminAlert("El correo de prueba se envió. Si llegó a la bandeja, ya puedes usar esta cuenta.", { title: "¡Correo enviado!" });
        } catch (err) {
            error.textContent = err.message;
            error.classList.remove("d-none");
        } finally {
            boton.disabled = false;
            boton.innerHTML = `<i class="fas fa-paper-plane me-1"></i> Enviar prueba`;
        }
    });

    // ---------- Usar / dejar de usar ----------
    async function usarCuenta(id) {
        try {
            const res = await api(`${API_URL}/active`, { method: "PUT", body: JSON.stringify({ id }) });
            pintar(res.data);
        } catch (err) {
            adminAlert(err.message, { type: "error" });
        }
    }

    document.getElementById("btnUsarServidor").addEventListener("click", async () => {
        const ok = await adminConfirm("Los códigos se volverán a enviar con el correo configurado en el servidor (.env).", {
            title: "¿Volver al correo del servidor?", confirmText: "Sí, volver", confirmClass: "btn-primary", icon: "fa-rotate-left"
        });
        if (ok) usarCuenta(null);
    });

    // ---------- Acciones de cada fila ----------
    lista.addEventListener("click", async (e) => {
        const boton = e.target.closest("button[data-accion]");
        if (!boton) return;
        const cuenta = cuentas.find(c => c.id === Number(boton.dataset.id));
        if (!cuenta) return;

        if (boton.dataset.accion === "probar") abrirPrueba(cuenta);
        if (boton.dataset.accion === "editar") abrirFormulario(cuenta);
        if (boton.dataset.accion === "usar") usarCuenta(cuenta.id);

        if (boton.dataset.accion === "eliminar") {
            const mensaje = cuenta.isActive
                ? `Es la cuenta en uso: los códigos se enviarán con el correo del servidor.`
                : `Se eliminará ${cuenta.email} y su contraseña guardada.`;
            const ok = await adminConfirm(mensaje, { title: "¿Eliminar esta cuenta?", confirmText: "Eliminar", icon: "fa-trash" });
            if (!ok) return;
            try {
                await api(`${API_URL}/${cuenta.id}`, { method: "DELETE" });
                cargar();
            } catch (err) {
                adminAlert(err.message, { type: "error" });
            }
        }
    });

    // Google separa la clave en grupos con espacios (algunos invisibles): los quitamos al pegarla
    document.getElementById("senderPassword").addEventListener("input", (e) => {
        const limpia = e.target.value.replace(/[\s\u00A0\u2000-\u200D\u202F\u205F\u3000\uFEFF]+/g, "");
        if (limpia !== e.target.value) e.target.value = limpia;
    });

    document.getElementById("btnNuevaCuenta").addEventListener("click", () => abrirFormulario());

    // Correo del admin para sugerirlo como destino de la prueba
    fetch("/api/profile", { headers: headers() })
        .then(r => r.ok ? r.json() : null)
        .then(perfil => { if (perfil?.email) correoAdmin = perfil.email; })
        .catch(() => {});

    cargar();
});
