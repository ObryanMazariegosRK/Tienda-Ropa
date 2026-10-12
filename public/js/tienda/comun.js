// =========================================================================
// COMÚN: utilidades y comportamiento compartido por todas las páginas de la tienda
// (escapar HTML, avisos/confirmaciones, menú móvil, zoom de imágenes)
// Se carga primero: los demás archivos de public/js/tienda/ usan estas funciones.
// =========================================================================

// Escapa texto antes de meterlo en innerHTML. Úsalo con cualquier dato que
// escriba un cliente (nombre, dirección, teléfono...) para evitar XSS.
function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

document.addEventListener("DOMContentLoaded", () => {

    // Guardamos la página actual como "última página de la tienda visitada",
    // para que "Mis Pedidos" pueda regresar exactamente a donde estabas.
    // Se excluye a sí misma para no sobreescribir el valor al entrar/salir de ahí
    if (!window.location.pathname.startsWith('/mis-pedidos')) {
        sessionStorage.setItem('last_store_page', window.location.pathname);
    }

    //Interactividad de la sección de galerías estáticas ("Hombres", "Mujeres", "Minimalista")
    const container = document.querySelectorAll(".gallery-layout");


    const showClothes = (category) => {
        container.forEach(container => container.style.display = "none");

        let filterContainer;
        if(category === "minimal"){
            filterContainer = Array.from(container).filter(container => ["layout-2"].includes(container.id));
        } else {
            filterContainer = Array.from(container).filter(container => container.getAttribute("data-category") === category).slice(0, 1);
        }

        filterContainer.forEach(container => container.style.display = "grid");
    };

    document.querySelectorAll(".gallery-lists li").forEach(item => {
        item.addEventListener("click", (e) => {
            document.querySelectorAll(".gallery-lists li").forEach(li => li.classList.remove("active"));
            e.target.classList.add("active");

            let category = e.target.getAttribute("data-category");
            showClothes(category);
        });
    });

    const defaultItem = document.querySelector(".gallery-lists li[data-category='minimal']");
    if (defaultItem) {
        showClothes(defaultItem.getAttribute("data-category"));
    }

    //Código para el formulario de la suscripción
    const subscriberInput = document.querySelector(".subscriber-input");
    const subscriberBtn = document.querySelector(".subscriber-btn");
    const subscriberThanks = document.querySelector(".subscriber-thanks");

    let timeOutId;
    if (subscriberBtn) {
        subscriberBtn.disabled = true;
    }

    if (subscriberInput && subscriberBtn) {
        subscriberInput.addEventListener("input", () => {
            if(subscriberInput.value.length > 0){
                subscriberBtn.disabled = false;
            } else {
                subscriberBtn.disabled = true;
            }
        });

        subscriberBtn.addEventListener("click", ()=> {
            subscriberInput.value = "";
            subscriberThanks.style.display = "block";

            clearTimeout(timeOutId);

            timeOutId = setTimeout(()=> {
                subscriberThanks.style.display = "none";
            }, 3000);
        });
    }

    //Interactividad del menú móvil (Abrir y cerrar menú lateral)
    //const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    //const mainNav = document.getElementById("main-nav");

    //if (mobileMenuBtn && mainNav) {
        //mobileMenuBtn.addEventListener("click", () => {
        //    mainNav.classList.toggle("active");
        //});
    //}
    const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    const mainNav = document.getElementById("main-nav");

    /*Creamos el fondo oscuro sin modificar el archivo Blade */
    const mobileNavOverlay = document.createElement("div");
    mobileNavOverlay.id = "mobile-nav-overlay";
    document.body.appendChild(mobileNavOverlay);

    /*Garantiza que la tienda cargue con el scroll desbloqueado */
    document.body.classList.remove("mobile-menu-open");

    function cerrarMenuMovil() {
        if (!mainNav) return;

        mainNav.classList.remove("active");
        mobileNavOverlay.classList.remove("active");
        document.body.classList.remove("mobile-menu-open");
    }

    function abrirMenuMovil() {
        if (!mainNav) return;

        mainNav.classList.add("active");
        mobileNavOverlay.classList.add("active");
        document.body.classList.add("mobile-menu-open");
    }

    if (mobileMenuBtn && mainNav) {
        mobileMenuBtn.addEventListener("click", () => {
            const menuAbierto = mainNav.classList.contains("active");

            if (menuAbierto) {
                cerrarMenuMovil();
            } else {
                abrirMenuMovil();
            }
        });
    }

    /* Cerrar al tocar la parte oscura de la tienda */
    mobileNavOverlay.addEventListener("click", cerrarMenuMovil);

    /* Cerrar con Escape */
    document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            cerrarMenuMovil();
        }
    });

    window.addEventListener('load', ajustarOffsetSecondaryNav);
    window.addEventListener('resize', ajustarOffsetSecondaryNav);

});

//Para dejar el submenu fijo xd
function ajustarOffsetSecondaryNav() {
    const header = document.querySelector('header');
    if (!header) return;
    document.documentElement.style.setProperty('--header-height', `${header.offsetHeight}px`);
}

// ==========================================
// ZOOM EN EL LUGAR (sin agrandar el espacio)
// Lo usan la vista rápida de la tienda y el modal de subastas (img.zoomable)
// ==========================================
document.addEventListener('click', (e) => {
    const img = e.target.closest('img.zoomable');
    if (!img) return;

    let nivel = parseInt(img.dataset.zoom || '1', 10);
    nivel = nivel >= 3 ? 1 : nivel + 1;   // cicla 1x → 2x → 3x → 1x
    img.dataset.zoom = nivel;

    if (nivel === 1) {
        img.style.transform = 'scale(1)';
        img.style.transformOrigin = 'center center';
    } else {
        // Solo al primer zoom fijamos el punto hacia dónde acercar (donde se hizo clic)
        if (nivel === 2) {
            const rect = img.parentElement.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            img.style.transformOrigin = `${x}% ${y}%`;
        }
        img.style.transform = `scale(${nivel})`;
    }
});

// ==========================================
// MODALES DE AVISO Y CONFIRMACIÓN
// ==========================================
// Aviso con el estilo de la tienda (reemplaza a alert()).
// Devuelve una promesa que se resuelve al cerrarlo, por si hay que hacer algo después.
function mostrarAviso(mensaje, tipo = 'error') {
    let overlay = document.getElementById('store-alert-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'store-alert-overlay';
        overlay.className = 'store-alert-overlay';
        overlay.innerHTML = `
            <div class="store-alert-box" role="alertdialog" aria-modal="true">
                <div class="store-alert-icon"></div>
                <p class="store-alert-message"></p>
                <button type="button" class="store-alert-btn">Entendido</button>
            </div>`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay || e.target.classList.contains('store-alert-btn')) {
                overlay.classList.remove('active');
                if (overlay._alCerrar) overlay._alCerrar();
            }
        });
    }

    const iconos = { success: '✓', error: '✕', warning: '!', info: 'i' };
    const iconEl = overlay.querySelector('.store-alert-icon');
    iconEl.textContent = iconos[tipo] || 'i';
    iconEl.className = `store-alert-icon store-alert-icon-${tipo}`;
    overlay.querySelector('.store-alert-message').textContent = mensaje;
    overlay.classList.add('active');
    overlay.querySelector('.store-alert-btn').focus();

    return new Promise(resolve => { overlay._alCerrar = resolve; });
}

// Confirmación con el estilo de la tienda (reemplaza a confirm()).
// Uso: if (!await mostrarConfirmacion('¿Seguro?')) return;
function mostrarConfirmacion(mensaje, opciones = {}) {
    const {
        tipo = 'warning',
        textoConfirmar = 'Aceptar',
        textoCancelar = 'Cancelar',
        peligro = false,
    } = opciones;

    let overlay = document.getElementById('store-confirm-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'store-confirm-overlay';
        overlay.className = 'store-alert-overlay';
        overlay.innerHTML = `
            <div class="store-alert-box" role="alertdialog" aria-modal="true">
                <div class="store-alert-icon"></div>
                <p class="store-alert-message"></p>
                <div class="store-alert-actions">
                    <button type="button" class="store-alert-btn store-alert-btn-secondary" data-respuesta="no"></button>
                    <button type="button" class="store-alert-btn" data-respuesta="si"></button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
        overlay.addEventListener('click', (e) => {
            const respuesta = e.target.dataset ? e.target.dataset.respuesta : undefined;
            if (e.target === overlay || respuesta) {
                overlay.classList.remove('active');
                if (overlay._responder) overlay._responder(respuesta === 'si');
            }
        });
    }

    const iconos = { success: '✓', error: '✕', warning: '!', info: 'i' };
    const iconEl = overlay.querySelector('.store-alert-icon');
    iconEl.textContent = iconos[tipo] || '?';
    iconEl.className = `store-alert-icon store-alert-icon-${tipo}`;
    overlay.querySelector('.store-alert-message').textContent = mensaje;

    const btnSi = overlay.querySelector('[data-respuesta="si"]');
    btnSi.textContent = textoConfirmar;
    btnSi.classList.toggle('store-alert-btn-danger', peligro);
    overlay.querySelector('[data-respuesta="no"]').textContent = textoCancelar;

    overlay.classList.add('active');
    btnSi.focus();

    return new Promise(resolve => { overlay._responder = resolve; });
}
