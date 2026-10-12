// =========================================================================
// CARRITO Y CHECKOUT: agregar productos, panel lateral y confirmación del pedido
// Requiere: comun.js, catalogo.js (quita de la vista los productos recién comprados)
// =========================================================================

document.addEventListener("DOMContentLoaded", () => {
    cargarContadorCarritoInicial();
});

// ==========================================
// CARRITO DE COMPRAS
// ==========================================
window.agregarAlCarrito = async function(productId, event) {
    if (event) event.stopPropagation();

    const token = localStorage.getItem('auth_token');
    if (!token) {
        await mostrarAviso('Debes iniciar sesión para agregar productos al carrito.', 'info');
        window.location.href = '/login';
        return;
    }

    try {
        const response = await fetch('/api/cart', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store',
            body: JSON.stringify({ productId })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || 'No se pudo agregar el producto al carrito.');
        }

        actualizarContadorCarrito(data.data.itemCount);
        await abrirCarrito(); //antes tenías alert(), ahora abre el drawer directamente

    } catch (error) {
        mostrarAviso(error.message, 'warning');
    }
};

function actualizarContadorCarrito(count) {
    const badge = document.getElementById('cart-count-badge');
    if (!badge) return;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
}

async function cargarContadorCarritoInicial() {
    const token = localStorage.getItem('auth_token');
    const badge = document.getElementById('cart-count-badge');
    if (!token || !badge) return;

    try {
        const response = await fetch('/api/cart', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        if (!response.ok) return;

        const data = await response.json();
        actualizarContadorCarrito(data.data.itemCount);
    } catch (error) {
        console.error('Error al cargar el contador del carrito:', error);
    }
}



// ==========================================
// DRAWER DEL CARRITO (panel lateral)
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    const cartIconBtn = document.getElementById("cart-icon-btn");
    const cartOverlay = document.getElementById("cart-overlay");
    const cartCloseBtn = document.getElementById("cart-drawer-close");

    if (cartIconBtn) {
        cartIconBtn.addEventListener("click", abrirCarrito);
    }
    if (cartOverlay) {
        cartOverlay.addEventListener("click", cerrarCarrito);
    }
    if (cartCloseBtn) {
        cartCloseBtn.addEventListener("click", cerrarCarrito);
    }
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") cerrarCarrito();
    });
});

window.abrirCarrito = async function() {
    const drawer = document.getElementById("cart-drawer");
    const overlay = document.getElementById("cart-overlay");
    if (!drawer || !overlay) return;

    drawer.classList.add("active");
    overlay.classList.add("active");
    document.body.style.overflow = "hidden"; // evita scroll de fondo mientras está abierto

    await cargarCarritoEnDrawer();
};

window.cerrarCarrito = function() {
    const drawer = document.getElementById("cart-drawer");
    const overlay = document.getElementById("cart-overlay");
    if (!drawer || !overlay) return;

    drawer.classList.remove("active");
    overlay.classList.remove("active");
    document.body.style.overflow = "";
};

async function cargarCarritoEnDrawer() {
    const itemsContainer = document.getElementById("cart-drawer-items");
    const token = localStorage.getItem('auth_token');

    if (!token) {
        itemsContainer.innerHTML = `
            <div class="cart-drawer-empty">
                <p>Debes iniciar sesión para ver tu carrito.</p>
            </div>`;
        document.getElementById("cart-drawer-footer").innerHTML = "";
        return;
    }

    itemsContainer.innerHTML = `<p class="loading-text">Cargando...</p>`;

    try {
        const response = await fetch('/api/cart', {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'No se pudo cargar tu carrito.');

        renderizarCarritoDrawer(data.data);
        actualizarContadorCarrito(data.data.itemCount);

    } catch (error) {
        itemsContainer.innerHTML = `<p class="loading-text">Ocurrió un error al cargar tu carrito.</p>`;
    }
}

function renderizarCarritoDrawer(cart) {
    const titleEl = document.getElementById("cart-drawer-title");
    const itemsContainer = document.getElementById("cart-drawer-items");
    const footerContainer = document.getElementById("cart-drawer-footer");

    titleEl.textContent = `Su carrito (${cart.itemCount})`;

    if (cart.items.length === 0) {
        itemsContainer.innerHTML = `
            <div class="cart-drawer-empty">
                <p>Tu carrito está vacío.</p>
            </div>`;
        footerContainer.innerHTML = "";
        return;
    }

    itemsContainer.innerHTML = cart.items.map(item => `
        <div class="cart-drawer-item" data-cart-item-id="${item.id}">
            <img src="${item.productImage ? '/storage/' + item.productImage : '/image/ImagenNoDefinida.png'}" 
                 alt="${item.productName}" class="cart-drawer-item-image">
            <div class="cart-drawer-item-info">
                <h3 class="cart-drawer-item-name">${item.productName}</h3>
                <div class="cart-drawer-item-price">
                    <span class="price-final">${item.price.toFixed(2)} GTQ</span>
                    ${item.originalPrice ? `<span class="price-original">Q${item.originalPrice.toFixed(2)}</span>` : ''}
                </div>
            </div>
            <button class="cart-drawer-item-remove" onclick="eliminarDelCarritoDrawer(${item.id})" aria-label="Eliminar producto"></button>
        </div>
    `).join('');

    footerContainer.innerHTML = `
        <div class="cart-drawer-subtotal-row">
            <span>Subtotal:</span>
            <span>${cart.total.toFixed(2)} GTQ</span>
        </div>
        <button class="cart-drawer-checkout-btn" onclick="iniciarCheckout()">
            PAGAR
        </button>
    `;
}

window.eliminarDelCarritoDrawer = async function(cartItemId) {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const response = await fetch(`/api/cart/${cartItemId}`, {
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!response.ok) throw new Error(data.message || 'No se pudo eliminar el producto.');

        renderizarCarritoDrawer(data.data);
        actualizarContadorCarrito(data.data.itemCount);

    } catch (error) {
        mostrarAviso(error.message);
    }
};

// ==========================================
// MODAL DE CHECKOUT (dirección + confirmación)
// ==========================================
let checkoutState = { addresses: [] };

document.addEventListener("DOMContentLoaded", () => {
    const closeBtn = document.getElementById("checkout-modal-close");
    const overlay = document.getElementById("checkout-overlay");
    if (closeBtn) closeBtn.addEventListener("click", cerrarCheckoutModal);
    if (overlay) overlay.addEventListener("click", cerrarCheckoutModal);
});

window.iniciarCheckout = async function() {
    cerrarCarrito();
    abrirCheckoutModal();
    await cargarPasoDireccion();
};

function abrirCheckoutModal() {
    document.getElementById("checkout-modal").classList.add("active");
    document.getElementById("checkout-overlay").classList.add("active");
    document.body.style.overflow = "hidden";
}

window.cerrarCheckoutModal = function() {
    document.getElementById("checkout-modal").classList.remove("active");
    document.getElementById("checkout-overlay").classList.remove("active");
    document.body.style.overflow = "";
};

async function cargarPasoDireccion() {
    const content = document.getElementById("checkout-modal-content");
    const token = localStorage.getItem('auth_token');

    if (!token) {
        content.innerHTML = `
            <div class="checkout-step">
                <h3>Inicia sesión para continuar</h3>
                <a href="/login" class="checkout-primary-btn">Iniciar sesión</a>
            </div>`;
        return;
    }

    content.innerHTML = `<p class="loading-text">Cargando tus direcciones...</p>`;

    try {
        // Traemos direcciones y perfil a la vez
        const [addrRes, profileRes] = await Promise.all([
            fetch('/api/addresses', {
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
                cache: 'no-store'
            }),
            fetch('/api/profile', {
                headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
                cache: 'no-store'
            })
        ]);

        const addrData = await addrRes.json();
        if (!addrRes.ok) throw new Error(addrData.message || 'No se pudieron cargar tus direcciones.');

        const profileData = await profileRes.json();
        // Soportamos que el perfil venga como {data:{...}} o directo
        const phone = profileRes.ok ? (profileData.data?.phone ?? profileData.phone ?? null) : null;

        checkoutState.addresses = addrData.data;
        checkoutState.needsPhone = !phone;  

        renderizarPasoDireccion();

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

function renderizarPasoDireccion() {
    const content = document.getElementById("checkout-modal-content");
    const addresses = checkoutState.addresses;

    const listHtml = addresses.map(addr => `
        <label class="checkout-address-option">
            <input type="radio" name="checkout-address" value="${addr.id}" ${addr.isDefault ? 'checked' : ''}>
            <div class="checkout-address-info">
                <strong>${addr.label}</strong>
                <span>${addr.addressLine}</span>
            </div>
        </label>
    `).join('');

    content.innerHTML = `
        <div class="checkout-step">
            <h3>¿A dónde enviamos tu pedido?</h3>

            ${addresses.length > 0 ? `
                <div class="checkout-address-list">${listHtml}</div>
                <button type="button" class="checkout-link-btn" id="toggle-new-address-btn">+ Agregar nueva dirección</button>
            ` : `<p class="checkout-empty-note">Aún no tienes direcciones guardadas. Agrega una para continuar.</p>`}

            <form id="new-address-form" class="checkout-new-address-form ${addresses.length > 0 ? 'hidden' : ''}">
                <div class="checkout-form-row">
                    <label>Etiqueta</label>
                    <input type="text" id="new-address-label" placeholder="Ej. Casa, Trabajo" maxlength="50">
                </div>
                <div class="checkout-form-row">
                    <label>Dirección completa</label>
                    <textarea id="new-address-line" rows="2" placeholder="Zona, calle, referencia..." maxlength="500"></textarea>
                </div>
                ${addresses.length > 0 ? `
                    <label class="checkout-checkbox-row">
                        <input type="checkbox" id="new-address-default">
                        Usar como dirección predeterminada
                    </label>
                ` : ''}
            </form>

            ${checkoutState.needsPhone ? `
                <div class="checkout-form-row">
                    <label>Teléfono de contacto</label>
                    <input type="tel" id="checkout-phone" inputmode="numeric"
                           pattern="[0-9]{8}" maxlength="8" placeholder="Ej: 55123456">
                    <small class="checkout-empty-note">Lo usaremos para coordinar la entrega de tu pedido.</small>
                </div>
            ` : ''}

            <p class="checkout-error" id="checkout-error" style="display:none;"></p>
            <button type="button" class="checkout-primary-btn" id="checkout-confirm-btn">Confirmar pedido</button>
        </div>
    `;

    const toggleBtn = document.getElementById("toggle-new-address-btn");
    const form = document.getElementById("new-address-form");
    if (toggleBtn) {
        toggleBtn.addEventListener("click", () => {
            form.classList.toggle("hidden");
            toggleBtn.textContent = form.classList.contains("hidden") ? "+ Agregar nueva dirección" : "Cancelar nueva dirección";
        });
    }

    document.getElementById("checkout-confirm-btn").onclick = procesarConfirmacionCheckout;
}

async function procesarConfirmacionCheckout() {
    const errorEl = document.getElementById("checkout-error");
    errorEl.style.display = "none";

    const token = localStorage.getItem('auth_token');
    const seleccionado = document.querySelector('input[name="checkout-address"]:checked');
    const labelInput = document.getElementById("new-address-label");
    const lineInput = document.getElementById("new-address-line");
    const formVisible = labelInput && !document.getElementById("new-address-form").classList.contains("hidden");

    // Desactivamos el botón mientras se procesa para evitar pedidos dobles
    const confirmBtn = document.getElementById("checkout-confirm-btn");
    if (confirmBtn.disabled) return;
    confirmBtn.disabled = true;
    confirmBtn.textContent = "Procesando...";

    try {

        // Si el usuario no tenía teléfono, lo pedimos y validamos aquí
        let phone = null;
        if (checkoutState.needsPhone) {
            const phoneInput = document.getElementById("checkout-phone");
            phone = phoneInput.value.trim();
            if (!/^[0-9]{8}$/.test(phone)) {
                errorEl.textContent = "Ingresa un teléfono de contacto válido (8 dígitos).";
                errorEl.style.display = "block";
                return;
            }
        }

        let addressId;

        if (formVisible && labelInput.value.trim() && lineInput.value.trim()) {
            const isDefaultCheckbox = document.getElementById("new-address-default");
            const isDefault = isDefaultCheckbox ? isDefaultCheckbox.checked : true;
            const nueva = await crearDireccion(token, labelInput.value.trim(), lineInput.value.trim(), isDefault);
            addressId = nueva.id;
        } else if (seleccionado) {
            addressId = parseInt(seleccionado.value, 10);
        } else {
            errorEl.textContent = "Selecciona una dirección o agrega una nueva.";
            errorEl.style.display = "block";
            return;
        }

        
        const resultado = await confirmarCheckout(token, addressId, phone);
        renderizarPasoConfirmacion(resultado);

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = "block";
    } finally {
        // Si hubo éxito el botón ya no existe en pantalla; si hubo error, se puede reintentar
        confirmBtn.disabled = false;
        confirmBtn.textContent = "Confirmar pedido";
    }
}

async function crearDireccion(token, label, addressLine, isDefault) {
    const response = await fetch('/api/addresses', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify({ label, addressLine, isDefault })
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'No se pudo guardar la dirección.');
    return data.data;
}

async function confirmarCheckout(token, addressId, phone) {
    const body = { addressId };
    if (phone) body.phone = phone;   

    const response = await fetch('/api/orders/checkout', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify(body)
    });
    const data = await response.json();
    if (!response.ok) throw new Error(data.message || 'No se pudo generar tu pedido.');

    actualizarContadorCarrito(0);
    quitarProductosDeLaTienda(data.data.order.items.map(item => item.productId));
    return data.data;
}

// Quita de la vista los productos que el cliente acaba de comprar, sin recargar la página
function quitarProductosDeLaTienda(productIds) {
    allProducts = allProducts.filter(p => !productIds.includes(p.id));
    productIds.forEach(id => {
        document.querySelectorAll(`.product-card[data-id="${id}"]`).forEach(card => card.remove());
    });

    if (productsContainer && !productsContainer.querySelector('.product-card')) {
        renderizarProductos([]);
    }
}

function renderizarPasoConfirmacion(resultado) {
    const content = document.getElementById("checkout-modal-content");
    const { order, whatsappUrl } = resultado;

    content.innerHTML = `
        <div class="checkout-step checkout-confirmation">
            <div class="checkout-success-icon">✓</div>
            <h3>¡Tu pedido #${order.id} fue registrado!</h3>
            <p>Total: <strong>${order.total.toFixed(2)} GTQ</strong></p>
            <p class="checkout-confirmation-note">
                Te vamos a abrir WhatsApp con tu pedido ya escrito.
                <strong>Asegúrate de presionar "Enviar"</strong> para confirmarlo con nosotros.
            </p>
            <a href="${whatsappUrl}" target="_blank" class="checkout-whatsapp-btn">
                <i class="fab fa-whatsapp"></i> Abrir WhatsApp
            </a>
            <p class="checkout-fallback-note">
                ¿No se abrió? Escríbenos directamente al <strong>+502 3666-6075</strong>
                mencionando tu número de pedido <strong>#${order.id}</strong>.
            </p>
            <button type="button" class="checkout-link-btn" onclick="cerrarCheckoutModal(); window.location.href='/mis-pedidos';">
                Ver mis pedidos
            </button>
        </div>
    `;

    window.open(whatsappUrl, '_blank');
}
