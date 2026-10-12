// =========================================================================
// SUBASTA GANADA: aviso al ganar, rechazo y confirmación del pedido
// Requiere: comun.js, carrito.js (reutiliza el modal de checkout)
// subastas.js usa auctionFlagKey() para avisar que el usuario tiene pujas activas.
// =========================================================================

document.addEventListener("DOMContentLoaded", () => {
    verificarSubastasGanadas();
    //Para revisar cada 10 segundos mientras que la pestaña este abierta
    //setInterval(verificarSubastasGanadas, 20000);
    iniciarSondeoLigero();
});

// ==========================================
// Subastas Presentacion al ganar una subasta
// ==========================================
async function verificarSubastasGanadas() {
    const token = localStorage.getItem('auth_token');
    if (!token) return;

    try {
        const response = await fetch('/api/auctions/my-bid-status', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();

        if (!data.success) return;

        if (data.data.wins.length > 0) {
            mostrarBannerSubastaGanada(data.data.wins);
            return;
        }

        const key = auctionFlagKey();
        if (data.data.activeBidsCount === 0 && key) {
            localStorage.removeItem(key);
        }

    } catch (error) {
        console.error('Error al verificar subastas ganadas:', error);
    }
}

function iniciarSondeoLigero() {
    setInterval(() => {
        const key = auctionFlagKey();
        if (key && localStorage.getItem(key) === 'true') {
            verificarSubastasGanadas();
        }
    }, 20000);
}


function mostrarBannerSubastaGanada(wins) {
    if (document.getElementById('auction-win-modal')) return; // ya se está mostrando
    //Para que no interrumpa si el usuario esta en el modal de checkout
    if (document.getElementById('checkout-modal')?.classList.contains('active')) return;
    const primera = wins[0];
    const extraTexto = wins.length > 1
        ? `<p class="auction-win-extra">Tienes ${wins.length - 1} subasta(s) ganada(s) más pendientes de confirmar.</p>`
        : '';

    const overlay = document.createElement('div');
    overlay.id = 'auction-win-overlay';
    overlay.className = 'auction-win-overlay active';

    const modal = document.createElement('div');
    modal.id = 'auction-win-modal';
    modal.className = 'auction-win-modal active';

    modal.innerHTML = `
        <div class="auction-win-icon">🏆</div>
        <h2>¡Ganaste la subasta!</h2>
        <p class="auction-win-product">${primera.productName}</p>
        <p class="auction-win-price">Q${primera.finalPrice.toFixed(2)}</p>
        ${extraTexto}
        <div class="auction-win-actions">
            <button type="button" class="auction-win-confirm-btn" onclick="cerrarModalGanador(); abrirConfirmacionSubastaGanada(${primera.auctionId})">
                Confirmar pedido
            </button>
            <button type="button" class="auction-win-decline-btn" onclick="confirmarCancelarSubasta(${primera.auctionId})">
                No quiero este producto
            </button>
        </div>
    `;


    

    document.body.appendChild(overlay);
    document.body.appendChild(modal);
    document.body.style.overflow = "hidden";
}

window.cerrarModalGanador = function() {
    document.getElementById('auction-win-overlay')?.remove();
    document.getElementById('auction-win-modal')?.remove();
    document.body.style.overflow = "";
};


window.confirmarCancelarSubasta = async function(auctionId) {
    const confirmado = await mostrarConfirmacion('¿Seguro que no quieres este producto? Se le ofrecerá al siguiente postor.', {
        textoConfirmar: 'Sí, rechazarlo', peligro: true
    });
    if (!confirmado) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/auctions/${auctionId}/decline`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo procesar tu decisión.');

        mostrarAviso('Entendido, se le ha ofrecido el producto al siguiente postor.', 'success');
        cerrarModalGanador();

    } catch (error) {
        mostrarAviso(error.message);
    }
};

function auctionFlagKey() {
    const token = localStorage.getItem('auth_token');
    return token ? `tiene_pujas_activas_${token}` : null;
}



//MODAL DE SUBASTAS
window.abrirConfirmacionSubastaGanada = async function(auctionId) {
    document.getElementById('auction-win-banner')?.remove();

    abrirCheckoutModal(); // función que ya existe, solo abre el modal visualmente
    await cargarPasoDireccionParaSubasta(auctionId);
};

async function cargarPasoDireccionParaSubasta(auctionId) {
    const content = document.getElementById("checkout-modal-content");
    const token = localStorage.getItem('auth_token');

    content.innerHTML = `<p class="loading-text">Cargando tus direcciones...</p>`;

    try {
        const response = await fetch('/api/addresses', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar tus direcciones.');

        checkoutState.addresses = data.data;
        renderizarPasoDireccion(); // función que ya tienes

        //Sobrescribimos el botón de confirmar para que apunte al endpoint de subasta
        document.getElementById("checkout-confirm-btn").onclick = () => procesarConfirmacionSubasta(auctionId);

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

async function procesarConfirmacionSubasta(auctionId) {
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

        const response = await fetch(`/api/auctions/${auctionId}/checkout`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ addressId })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo confirmar tu pedido.');

        renderizarPasoConfirmacion(data.data); // función que ya tienes, reutilizada tal cual

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = "block";
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.textContent = "Confirmar pedido";
    }


    
}
