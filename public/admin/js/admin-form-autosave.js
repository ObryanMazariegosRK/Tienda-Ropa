// Utilidad reutilizable: guarda el estado de un formulario en localStorage
// mientras el usuario escribe, y lo restaura si vuelve a esa vista.
window.habilitarAutoguardado = function(form, storageKey, opciones = {}) {
    const KEY = `admin_draft_${storageKey}`;
    const camposExcluir = opciones.excluir || [];

    function leerCampos() {
        const datos = {};
        form.querySelectorAll('input, select, textarea').forEach(el => {
            if (!el.id || el.type === 'file' || camposExcluir.includes(el.id)) return;
            datos[el.id] = el.type === 'checkbox' ? el.checked : el.value;
        });
        return datos;
    }

    function guardarBorrador() {
        localStorage.setItem(KEY, JSON.stringify(leerCampos()));
    }

    function aplicarValores(datos) {
        Object.entries(datos).forEach(([id, valor]) => {
            const el = form.querySelector(`#${id}`);
            if (!el) return; // el campo puede no existir todavía (ej. un <select> que carga async)
            if (el.type === 'checkbox') el.checked = valor;
            else el.value = valor;
        });
    }

    // Restauración inicial (campos que ya existen al cargar la página)
    const guardado = localStorage.getItem(KEY);
    let datosGuardados = null;
    if (guardado) {
        try {
            datosGuardados = JSON.parse(guardado);
            aplicarValores(datosGuardados);
        } catch (e) {
            console.error('No se pudo leer el borrador guardado:', e);
        }
    }

    form.addEventListener('input', guardarBorrador);
    form.addEventListener('change', guardarBorrador);

    return {
        // Llama esto cuando el formulario se guardó con éxito en el servidor
        limpiarBorrador: () => localStorage.removeItem(KEY),
        // Llama esto DESPUÉS de poblar un <select> con datos que llegaron por fetch
        // (categorías, etc.), para recién ahí intentar seleccionar el valor guardado
        reintentarCampo: (id) => {
            if (datosGuardados && datosGuardados[id] !== undefined) {
                const el = form.querySelector(`#${id}`);
                if (el) el.value = datosGuardados[id];
            }
        }
    };
};