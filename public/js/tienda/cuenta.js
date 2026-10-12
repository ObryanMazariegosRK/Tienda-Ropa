// =========================================================================
// MI CUENTA: modal de perfil (con el interruptor de notificaciones) y direcciones
// Requiere: comun.js, notificaciones.js
// =========================================================================

// ==========================================
// MODAL DE CUENTA (Perfil / Direcciones)
// ==========================================
let direccionesCache = [];

document.addEventListener("DOMContentLoaded", () => {
    const profileBtn = document.getElementById("sidebarProfileBtn");
    const addressesBtn = document.getElementById("sidebarAddressesBtn");
    const closeBtn = document.getElementById("account-modal-close");
    const overlay = document.getElementById("account-modal-overlay");

    if (profileBtn) profileBtn.addEventListener("click", abrirModalPerfil);
    if (addressesBtn) addressesBtn.addEventListener("click", abrirModalDirecciones);
    if (closeBtn) closeBtn.addEventListener("click", cerrarAccountModal);
    if (overlay) overlay.addEventListener("click", cerrarAccountModal);
});

function abrirAccountModal() {
    document.getElementById("account-modal").classList.add("active");
    document.getElementById("account-modal-overlay").classList.add("active");
    document.body.style.overflow = "hidden";
}

window.cerrarAccountModal = function() {
    document.getElementById("account-modal").classList.remove("active");
    document.getElementById("account-modal-overlay").classList.remove("active");
    document.body.style.overflow = "";
};

// ===== PERFIL (solo lectura) =====
async function abrirModalPerfil() {
    abrirAccountModal();
    const content = document.getElementById("account-modal-content");
    content.innerHTML = `<p class="loading-text">Cargando...</p>`;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/profile', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error('No se pudo cargar tu perfil.');

        const iniciales = (data.name?.[0] || '') + (data.lastName?.[0] || '');

        content.innerHTML = `
            <div class="account-profile-view">
                <div class="account-profile-avatar">${iniciales.toUpperCase()}</div>
                <h3 class="account-profile-name">${data.name} ${data.lastName || ''}</h3>
                <div class="account-profile-details">
                    <div class="account-profile-field">
                        <span class="account-profile-icon">✉</span>
                        <div>
                            <label>Correo electrónico</label>
                            <p>${data.email}</p>
                        </div>
                    </div>
                    ${data.phone ? `
                    <div class="account-profile-field">
                        <span class="account-profile-icon">☎</span>
                        <div>
                            <label>Teléfono</label>
                            <p>${data.phone}</p>
                        </div>
                    </div>` : ''}
                </div>




                <div class="account-profile-notifications" style="margin-top:1.5rem; border-top:1px solid #333; padding-top:1.2rem;">
                    <h4 style="margin-bottom:.7rem; font-size:1.15rem;">🔔 Notificaciones</h4>
                    <label style="display:flex; align-items:center; gap:.6rem; cursor:pointer; font-size:1.15rem;">
                        <input type="checkbox" id="notif-toggle" style="width:18px; height:18px; cursor:pointer;">
                        <span>Activar notificaciones en este dispositivo</span>
                    </label>
                    <small style="color:#aaa; display:block; margin-top:.5rem; font-size:1.10rem; line-height:1.4;">
                        Te avisaremos cuando tu pedido cambie de estado o ganes una subasta.
                    </small>
                </div>


            </div>
        `;

        // Enganchamos el toggle de notificaciones
        const notifToggle = document.getElementById('notif-toggle');
        if (notifToggle) {
            notifToggle.checked = await estaSuscritoPush();   // refleja el estado actual
            notifToggle.addEventListener('change', async () => {
                try {
                    if (notifToggle.checked) {
                        await suscribirPush();
                    } else {
                        await desuscribirPush();
                    }
                } catch (e) {
                    mostrarAviso(e.message, 'error'); 
                    notifToggle.checked = await estaSuscritoPush();  // revierte si falla
                }
            });
        }
    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

// ===== DIRECCIONES (CRUD completo) =====
async function abrirModalDirecciones() {
    abrirAccountModal();
    await cargarDireccionesModal();
}

async function cargarDireccionesModal() {
    const content = document.getElementById("account-modal-content");
    content.innerHTML = `<p class="loading-text">Cargando direcciones...</p>`;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/addresses', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar tus direcciones.');

        direccionesCache = data.data;
        renderizarDireccionesModal();

    } catch (error) {
        content.innerHTML = `<p class="loading-text">${error.message}</p>`;
    }
}

function renderizarDireccionesModal() {
    const content = document.getElementById("account-modal-content");
    const alLimite = direccionesCache.length >= 5;

    const listHtml = direccionesCache.map(addr => `
        <div class="account-address-card ${addr.isDefault ? 'is-default' : ''}" data-address-id="${addr.id}" onclick="clicEnDireccion(${addr.id}, event)">
            <div class="account-address-info">
                <strong>${addr.label}</strong> ${addr.isDefault ? '<span class="account-address-default-badge">Predeterminada</span>' : ''}
                <p>${addr.addressLine}</p>
            </div>
            <div class="account-address-actions">
                <button type="button" onclick="event.stopPropagation(); abrirEdicionDireccion(${addr.id})">Editar</button>
                <button type="button" class="account-address-delete" onclick="event.stopPropagation(); eliminarDireccionModal(${addr.id})">Eliminar</button>
            </div>
        </div>
    `).join('');

    content.innerHTML = `
        <h3>Mis Direcciones</h3>
        <div class="account-address-list">
            ${direccionesCache.length > 0 ? listHtml : '<p class="text-muted">Aún no tienes direcciones guardadas.</p>'}
        </div>
        ${direccionesCache.length > 0 ? '<p class="account-address-hint">Toca una dirección para marcarla como predeterminada.</p>' : ''}

        ${alLimite
            ? `<p class="account-address-limit-note">Ya tienes el máximo de 5 direcciones. Elimina una para poder agregar otra.</p>`
            : `<button type="button" class="account-link-btn" id="toggleNewAddressBtn">+ Agregar nueva dirección</button>
               <form id="newAddressModalForm" class="account-new-address-form hidden">
                   <div class="account-form-row">
                       <label>Etiqueta</label>
                       <input type="text" id="newAddrLabel" placeholder="Ej. Casa, Trabajo" maxlength="50">
                   </div>
                   <div class="account-form-row">
                       <label>Dirección completa</label>
                       <textarea id="newAddrLine" rows="2" placeholder="Zona, calle, referencia..." maxlength="500"></textarea>
                   </div>
                   <button type="button" class="account-primary-btn" onclick="guardarNuevaDireccionModal()">Guardar dirección</button>
               </form>`
        }

        <p class="account-modal-error" id="accountAddressError" style="display:none;"></p>
    `;

    if (!alLimite) {
        document.getElementById("toggleNewAddressBtn").addEventListener("click", () => {
            document.getElementById("newAddressModalForm").classList.toggle("hidden");
        });
    }
}



window.clicEnDireccion = function(id, event) {
    const addr = direccionesCache.find(a => a.id === id);
    if (!addr || addr.isDefault) return; // ya es la predeterminada, no hace nada
    marcarDireccionPredeterminada(id);
};

window.marcarDireccionPredeterminada = async function(id) {
    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}/default`, {
            method: 'PATCH',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo actualizar.');
        cargarDireccionesModal();
    } catch (error) {
        mostrarAviso(error.message);
    }
};

window.eliminarDireccionModal = async function(id) {
    const confirmado = await mostrarConfirmacion('¿Eliminar esta dirección?', {
        textoConfirmar: 'Eliminar', peligro: true
    });
    if (!confirmado) return;

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo eliminar.');
        cargarDireccionesModal();
    } catch (error) {
        mostrarAviso(error.message);
    }
};

window.guardarNuevaDireccionModal = async function() {
    const errorEl = document.getElementById("accountAddressError");
    errorEl.style.display = 'none';

    const label = document.getElementById("newAddrLabel").value.trim();
    const addressLine = document.getElementById("newAddrLine").value.trim();

    if (!label || !addressLine) {
        errorEl.textContent = 'Completa la etiqueta y la dirección.';
        errorEl.style.display = 'block';
        return;
    }

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch('/api/addresses', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ label, addressLine, isDefault: false })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo guardar.');

        cargarDireccionesModal();

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.style.display = 'block';
    }
};


window.abrirEdicionDireccion = function(id) {
    const addr = direccionesCache.find(a => a.id === id);
    if (!addr) return;

    const card = document.querySelector(`.account-address-card[data-address-id="${id}"]`);
    card.innerHTML = `
        <div class="account-form-row">
            <label>Etiqueta</label>
            <input type="text" id="editAddrLabel_${id}" value="${addr.label}" maxlength="50">
        </div>
        <div class="account-form-row">
            <label>Dirección completa</label>
            <textarea id="editAddrLine_${id}" rows="2" maxlength="500">${addr.addressLine}</textarea>
        </div>
        <div class="account-address-actions">
            <button type="button" onclick="guardarEdicionDireccion(${id})">Guardar</button>
            <button type="button" onclick="renderizarDireccionesModal()">Cancelar</button>
        </div>
    `;
};

window.guardarEdicionDireccion = async function(id) {
    const label = document.getElementById(`editAddrLabel_${id}`).value.trim();
    const addressLine = document.getElementById(`editAddrLine_${id}`).value.trim();

    if (!label || !addressLine) {
        mostrarAviso('Completa ambos campos.', 'warning');
        return;
    }

    const token = localStorage.getItem('auth_token');
    try {
        const response = await fetch(`/api/addresses/${id}`, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({ label, addressLine })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo actualizar.');

        cargarDireccionesModal();

    } catch (error) {
        mostrarAviso(error.message);
    }
};
