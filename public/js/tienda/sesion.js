// =========================================================================
// SESIÓN: saludo y botón de cerrar sesión en el header
// =========================================================================

document.addEventListener("DOMContentLoaded", () => {
    actualizarAuthUI();
});

// ==========================================
// ESTADO DE SESIÓN EN EL HEADER (login / hola + logout)
// ==========================================
async function actualizarAuthUI() {
    const authContainer = document.getElementById('auth-container');
    if (!authContainer) return;

    const token = localStorage.getItem('auth_token');
    if (!token) {
        renderLoginLink(authContainer);
        return;
    }

    try {
        const response = await fetch('/api/profile', {
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
            cache: 'no-store'
        });

        if (response.status === 401) {
            // Token inválido o expirado: aquí sí termina la sesión
            localStorage.removeItem('auth_token');
            renderLoginLink(authContainer);
            return;
        }

        if (!response.ok) {
            // Error temporal del servidor: la sesión sigue siendo válida, no borramos el token
            renderLoggedInUI(authContainer, null);
            return;
        }

        const data = await response.json();
        renderLoggedInUI(authContainer, data.name); 

    } catch (error) {
        // Sin conexión: conservamos la sesión y saludamos sin nombre
        console.error('Error al verificar sesión:', error);
        renderLoggedInUI(authContainer, null);
    }
}

function renderLoginLink(container) {
    container.innerHTML = `
        <a href="/login" class="icon-link" title="Iniciar Sesión / Registrarse">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" />
                <path d="M16 19h6" />
                <path d="M19 16v6" />
                <path d="M6 21v-2a4 4 0 0 1 4 -4h4" />
            </svg>
        </a>`;
}

function renderLoggedInUI(container, name) {
    container.innerHTML = `
        <span class="user-greeting">${name ? `Hola, ${escapeHtml(name)}` : 'Hola'}</span>
        <button type="button" class="icon-link" id="logout-btn" title="Cerrar sesión">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" />
                <path d="M15 6v-1a2 2 0 0 0 -2 -2h-8a2 2 0 0 0 -2 2v14a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2v-1" />
            </svg>
        </button>`;

    document.getElementById('logout-btn').addEventListener('click', confirmarCerrarSesion);
}

window.confirmarCerrarSesion = async function() {
    const confirmado = await mostrarConfirmacion('¿Estás seguro de que quieres cerrar sesión?', {
        tipo: 'info', textoConfirmar: 'Cerrar sesión'
    });
    if (!confirmado) return;

    const token = localStorage.getItem('auth_token');

    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` }
        });
    } catch (error) {
        console.error('Error al cerrar sesión en el servidor:', error);
        // Continuamos igual: aunque falle la llamada al backend,
        // igual limpiamos la sesión local para no dejar al usuario atascado
    } finally {
        localStorage.removeItem('auth_token');
        window.location.href = '/';
    }
};
