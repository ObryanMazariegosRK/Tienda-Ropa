const MAX_GROUPS = 5;
const MAX_MEDIA_PER_GROUP = 5;
const MAX_FILE_SIZE_BYTES = 15 * 1024 * 1024;
const VALID_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/webm'];

let groupsCache = [];
let selectedCreateFiles = [];
let currentEditingGroupId = null;

// Estado local del modal de edición — nada se manda al servidor hasta presionar "Guardar"
let editingGroup = null;
let editingRemovedIds = [];
let editingNewFiles = [];

document.addEventListener("DOMContentLoaded", () => {
    cargarGrupos();

    document.getElementById("createGroupForm").addEventListener("submit", crearGrupo);
    document.getElementById("groupMedia").addEventListener("change", (e) => manejarSeleccionCrear(e));
    document.getElementById("saveGroupChangesBtn").addEventListener("click", guardarCambiosDelGrupo);
    document.getElementById("deleteGroupBtn").addEventListener("click", eliminarGrupoCompleto);

    // Descarta el estado local si se cierra el modal sin guardar (X, Cancelar, backdrop)
    document.getElementById("modalEditGroup").addEventListener("hidden.bs.modal", () => {
        editingGroup = null;
        editingRemovedIds = [];
        editingNewFiles = [];
    });
});

function authHeaders(extra = {}) {
    return {
        'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`,
        ...extra
    };
}

function validarArchivos(files, espaciosDisponibles) {
    const errores = [];
    const validos = [];

    files.forEach(file => {
        if (!VALID_MIME_TYPES.includes(file.type)) {
            errores.push(`"${file.name}" no es un tipo válido (JPG, PNG, WEBP, MP4 o WEBM).`);
            return;
        }
        if (file.size > MAX_FILE_SIZE_BYTES) {
            errores.push(`"${file.name}" pesa ${(file.size / 1024 / 1024).toFixed(1)}MB — máximo 15MB.`);
            return;
        }
        validos.push(file);
    });

    if (validos.length > 1) {
        const primerEsVideo = validos[0].type.startsWith('video/');
        const mezclado = validos.some(f => f.type.startsWith('video/') !== primerEsVideo);
        if (mezclado) {
            errores.push('No puedes mezclar imágenes y videos en la misma selección.');
            return { validos: [], errores };
        }
    }

    const excedentes = validos.length - espaciosDisponibles;
    if (excedentes > 0) {
        errores.push(`Solo caben ${espaciosDisponibles} archivo(s) más. Se ignoraron los últimos ${excedentes}.`);
        validos.splice(espaciosDisponibles);
    }

    return { validos, errores };
}

function renderPreviewList(container, files, onRemove) {
    container.innerHTML = "";
    files.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const esVideo = file.type.startsWith('video/');

        const item = document.createElement("div");
        item.className = "media-thumb-item";
        item.innerHTML = `
            ${esVideo ? `<video src="${url}" muted></video>` : `<img src="${url}">`}
            <button type="button" class="btn btn-danger btn-sm media-thumb-remove"><i class="fas fa-times"></i></button>
        `;
        item.querySelector('.media-thumb-remove').addEventListener('click', () => onRemove(index));
        container.appendChild(item);
    });
}

// ==========================================
// CREAR GRUPO
// ==========================================
function manejarSeleccionCrear(e) {
    const errorEl = document.getElementById("createGroupError");
    errorEl.classList.add('d-none');

    const espacios = MAX_MEDIA_PER_GROUP - selectedCreateFiles.length;
    const { validos, errores } = validarArchivos(Array.from(e.target.files), espacios);

    if (errores.length > 0) {
        errorEl.innerHTML = errores.map(m => `<div>${m}</div>`).join('');
        errorEl.classList.remove('d-none');
    }

    selectedCreateFiles = selectedCreateFiles.concat(validos);
    renderCreatePreview();
    e.target.value = "";
}

function renderCreatePreview() {
    renderPreviewList(document.getElementById("groupPreviewList"), selectedCreateFiles, (i) => {
        selectedCreateFiles.splice(i, 1);
        renderCreatePreview();
    });
}

async function crearGrupo(e) {
    e.preventDefault();
    const errorEl = document.getElementById("createGroupError");
    errorEl.classList.add('d-none');

    if (selectedCreateFiles.length === 0) {
        errorEl.textContent = "Selecciona al menos un archivo.";
        errorEl.classList.remove('d-none');
        return;
    }

    const formData = new FormData();
    formData.append('name', document.getElementById("groupName").value);
    selectedCreateFiles.forEach(file => formData.append('media[]', file));

    try {
        const response = await fetch('/api/banner-groups', {
            method: 'POST',
            headers: authHeaders({ 'Accept': 'application/json' }),
            body: formData
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo crear el grupo.');

        document.getElementById("createGroupForm").reset();
        selectedCreateFiles = [];
        document.getElementById("groupPreviewList").innerHTML = "";
        cargarGrupos();

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.classList.remove('d-none');
    }
}

// ==========================================
// LISTAR GRUPOS
// ==========================================
async function cargarGrupos() {
    const grid = document.getElementById("bannersGrid");
    grid.innerHTML = `<p class="text-muted">Cargando...</p>`;

    try {
        const response = await fetch('/api/banner-groups', {
            headers: authHeaders({ 'Accept': 'application/json' }),
            cache: 'no-store'
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudieron cargar los banners.');

        groupsCache = data.data;
        renderizarGrupos();

    } catch (error) {
        grid.innerHTML = `<p class="text-danger">${error.message}</p>`;
    }
}

function renderizarGrupos() {
    const grid = document.getElementById("bannersGrid");
    const createBtn = document.getElementById("createGroupBtn");
    const limitNote = document.getElementById("groupsLimitNote");

    const alLimite = groupsCache.length >= MAX_GROUPS;
    createBtn.disabled = alLimite;
    limitNote.textContent = alLimite
        ? `Ya tienes el máximo de ${MAX_GROUPS} grupos.`
        : `${groupsCache.length} / ${MAX_GROUPS} grupos`;

    if (groupsCache.length === 0) {
        grid.innerHTML = `<p class="text-muted">Aún no hay grupos de banner. Crea el primero arriba.</p>`;
        return;
    }

    grid.innerHTML = groupsCache.map(group => {
        const portada = group.media[0];
        const esVideo = group.type === 'video';

        return `
            <div class="card group-card ${group.isActive ? 'active-group' : ''}" data-id="${group.id}" onclick="abrirEdicionGrupo(${group.id})">
                ${group.isActive ? '<span class="badge bg-success badge-active">Activo</span>' : ''}
                ${portada
                    ? (esVideo
                        ? `<video class="group-thumb" src="/storage/${portada.mediaUrl}" muted loop autoplay></video>`
                        : `<img class="group-thumb" src="/storage/${portada.mediaUrl}">`)
                    : `<div class="group-thumb d-flex align-items-center justify-content-center text-muted">Sin archivos</div>`
                }
                <div class="card-body">
                    <h6 class="card-title mb-1">${group.name}</h6>
                    <p class="text-muted small mb-3">
                        <i class="fas ${esVideo ? 'fa-video' : 'fa-image'} me-1"></i>
                        ${esVideo ? 'Video' : 'Imagen'} — ${group.media.length} archivo(s)
                    </p>
                    <button type="button" class="btn btn-sm w-100 ${group.isActive ? 'btn-outline-secondary' : 'btn-success'}"
                            onclick="event.stopPropagation(); alternarActivo(${group.id})">
                        <i class="fas ${group.isActive ? 'fa-eye-slash' : 'fa-eye'} me-1"></i>
                        ${group.isActive ? 'Desactivar' : 'Activar en la tienda'}
                    </button>
                </div>
            </div>
        `;
    }).join('');
}

// ==========================================
// ACTIVAR / DESACTIVAR (exclusivo)
// ==========================================
window.alternarActivo = async function(id) {
    try {
        const response = await fetch(`/api/banner-groups/${id}/toggle-active`, {
            method: 'PATCH',
            headers: authHeaders({ 'Accept': 'application/json' })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo cambiar el estado.');

        groupsCache = data.data;
        renderizarGrupos();

    } catch (error) {
        alert(error.message);
    }
};

// ==========================================
// MODAL DE EDICIÓN (cambios diferidos hasta "Guardar")
// ==========================================
window.abrirEdicionGrupo = function(id) {
    const group = groupsCache.find(g => g.id === id);
    if (!group) return;

    currentEditingGroupId = id;
    editingGroup = group;
    editingRemovedIds = [];
    editingNewFiles = [];

    document.getElementById("editGroupName").value = group.name;
    document.getElementById("addMoreMediaInput").value = "";
    document.getElementById("editGroupError").classList.add('d-none');
    document.getElementById("addMoreMediaInput").onchange = manejarSeleccionEnEdicion;

    renderizarListaUnificada();
    new bootstrap.Modal(document.getElementById('modalEditGroup')).show();
};

function renderizarListaUnificada() {
    const container = document.getElementById("editMediaList");
    const esVideo = editingGroup.type === 'video';
    container.innerHTML = "";

    // 1. Archivos existentes que NO están marcados para eliminar
    editingGroup.media
        .filter(m => !editingRemovedIds.includes(m.id))
        .forEach(m => {
            const item = document.createElement("div");
            item.className = "media-thumb-item";
            item.innerHTML = `
                ${esVideo
                    ? `<video src="/storage/${m.mediaUrl}" muted loop autoplay></video>`
                    : `<img src="/storage/${m.mediaUrl}">`
                }
                <button type="button" class="btn btn-danger btn-sm media-thumb-remove"><i class="fas fa-times"></i></button>
            `;
            item.querySelector('.media-thumb-remove').addEventListener('click', () => {
                editingRemovedIds.push(m.id);
                renderizarListaUnificada();
            });
            container.appendChild(item);
        });

    // 2. Archivos nuevos, aún no subidos (solo en memoria)
    editingNewFiles.forEach((file, index) => {
        const url = URL.createObjectURL(file);
        const item = document.createElement("div");
        item.className = "media-thumb-item";
        item.innerHTML = `
            ${file.type.startsWith('video/') ? `<video src="${url}" muted></video>` : `<img src="${url}">`}
            <button type="button" class="btn btn-danger btn-sm media-thumb-remove"><i class="fas fa-times"></i></button>
        `;
        item.querySelector('.media-thumb-remove').addEventListener('click', () => {
            editingNewFiles.splice(index, 1);
            renderizarListaUnificada();
        });
        container.appendChild(item);
    });
}

function manejarSeleccionEnEdicion(e) {
    const errorEl = document.getElementById("editGroupError");
    errorEl.classList.add('d-none');

    const restantesExistentes = editingGroup.media.length - editingRemovedIds.length;
    const espacios = MAX_MEDIA_PER_GROUP - restantesExistentes - editingNewFiles.length;

    const { validos, errores } = validarArchivos(Array.from(e.target.files), espacios);

    const grupoEsVideo = editingGroup.type === 'video';
    const filtradosPorTipo = validos.filter(f => {
        const esVideo = f.type.startsWith('video/');
        if (esVideo !== grupoEsVideo) {
            errores.push(`Este grupo es de tipo "${editingGroup.type}" — "${f.name}" no coincide.`);
            return false;
        }
        return true;
    });

    if (errores.length > 0) {
        errorEl.innerHTML = errores.map(m => `<div>${m}</div>`).join('');
        errorEl.classList.remove('d-none');
    }

    editingNewFiles = editingNewFiles.concat(filtradosPorTipo);
    renderizarListaUnificada();
    e.target.value = "";
}

async function guardarCambiosDelGrupo() {
    const errorEl = document.getElementById("editGroupError");
    errorEl.classList.add('d-none');

    const nuevoNombre = document.getElementById("editGroupName").value.trim();
    const saveBtn = document.getElementById("saveGroupChangesBtn");
    saveBtn.disabled = true;
    saveBtn.textContent = 'Guardando...';

    try {
        // 1. Nombre, si cambió
        if (nuevoNombre && nuevoNombre !== editingGroup.name) {
            const resp = await fetch(`/api/banner-groups/${currentEditingGroupId}/rename`, {
                method: 'PATCH',
                headers: authHeaders({ 'Content-Type': 'application/json', 'Accept': 'application/json' }),
                body: JSON.stringify({ name: nuevoNombre })
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'No se pudo renombrar.');
        }

        // 2. Archivos nuevos, ANTES de borrar los viejos —
        // así el grupo nunca se queda momentáneamente en 0 archivos si vas a agregar otros.
        if (editingNewFiles.length > 0) {
            const formData = new FormData();
            editingNewFiles.forEach(file => formData.append('media[]', file));

            const resp = await fetch(`/api/banner-groups/${currentEditingGroupId}/media`, {
                method: 'POST',
                headers: authHeaders({ 'Accept': 'application/json' }),
                body: formData
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'No se pudieron subir los archivos nuevos.');
        }

        // 3. Eliminaciones marcadas
        for (const mediaId of editingRemovedIds) {
            const resp = await fetch(`/api/banner-groups/${currentEditingGroupId}/media/${mediaId}`, {
                method: 'DELETE',
                headers: authHeaders({ 'Accept': 'application/json' })
            });
            const data = await resp.json();
            if (!resp.ok) throw new Error(data.message || 'No se pudo eliminar un archivo.');

            if (data.data === null) {
                // Se eliminó el último archivo: el grupo completo desapareció
                bootstrap.Modal.getInstance(document.getElementById('modalEditGroup')).hide();
                cargarGrupos();
                return;
            }
        }

        bootstrap.Modal.getInstance(document.getElementById('modalEditGroup')).hide();
        cargarGrupos();

    } catch (error) {
        errorEl.textContent = error.message;
        errorEl.classList.remove('d-none');
    } finally {
        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Guardar cambios';
    }
}

async function eliminarGrupoCompleto() {
    if (!confirm('¿Eliminar este grupo completo y todos sus archivos? Esta acción no se puede deshacer.')) return;

    try {
        const response = await fetch(`/api/banner-groups/${currentEditingGroupId}`, {
            method: 'DELETE',
            headers: authHeaders({ 'Accept': 'application/json' })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.message || 'No se pudo eliminar el grupo.');

        bootstrap.Modal.getInstance(document.getElementById('modalEditGroup')).hide();
        cargarGrupos();

    } catch (error) {
        alert(error.message);
    }
}