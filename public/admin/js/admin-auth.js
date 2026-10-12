(function() {
    const token = localStorage.getItem('admin_auth_token');

    if (!token) {
        window.location.href = '/admin/login';
        return;
    }

    function cerrarSesionLocal() {
        localStorage.removeItem('admin_auth_token');
        window.location.href = '/admin/login';
    }

    // Si el servidor no responde (se está reiniciando, falla el internet, error 500...)
    // NO borramos el token: la sesión sigue siendo válida, solo hay que reintentar.
    function mostrarErrorDeConexion() {
        const pintar = () => {
            if (document.getElementById('admin-auth-error')) return;
            const aviso = document.createElement('div');
            aviso.id = 'admin-auth-error';
            aviso.style.cssText = 'max-width:420px;margin:15vh auto;padding:2rem;text-align:center;'
                + 'border:1px solid #E5E7EB;border-radius:14px;background:#fff;font-family:system-ui,sans-serif;';
            aviso.innerHTML = `
                <p style="font-weight:700;margin-bottom:.5rem;">No se pudo conectar con el servidor</p>
                <p style="color:#6B7280;margin-bottom:1.25rem;">Tu sesión sigue activa. Revisa tu conexión e inténtalo de nuevo.</p>
                <button type="button" onclick="location.reload()"
                        style="border:0;border-radius:10px;padding:.6rem 1.4rem;background:#16A34A;color:#fff;font-weight:700;cursor:pointer;">
                    Reintentar
                </button>`;
            document.body.appendChild(aviso);
        };
        // Este archivo se carga en el <head>: esperamos a que exista el <body>
        if (document.body) pintar(); else document.addEventListener('DOMContentLoaded', pintar);
    }

    // Verificamos que el token siga siendo válido Y que sea de un admin real
    fetch('/api/profile', {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        cache: 'no-store'
    })
    .then(async response => {
        // 401 = token inválido o vencido: aquí sí termina la sesión
        if (response.status === 401) return cerrarSesionLocal();
        if (!response.ok) return mostrarErrorDeConexion();

        const profile = await response.json();
        if (profile.role !== 'admin') return cerrarSesionLocal(); // no es una cuenta de administrador

        // Mostramos el contenido (estaba oculto por CSS mientras se validaba)
        document.body.classList.add('admin-auth-verified');

        const nameEl = document.getElementById('admin-user-name');
        if (nameEl) nameEl.textContent = profile.name;
    })
    .catch(() => mostrarErrorDeConexion()); // sin conexión: conservamos la sesión
})();

window.adminLogout = async function() {
    const confirmado = await adminConfirm('¿Estás seguro de que quieres cerrar sesión?', {
        title: 'Cerrar sesión',
        confirmText: 'Sí, cerrar sesión',
        confirmClass: 'btn-danger',
        icon: 'fa-sign-out-alt'
    });
    if (!confirmado) return;

    const token = localStorage.getItem('admin_auth_token');
    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: { 'Authorization': `Bearer ${token}` }
        });
    } catch (error) {
        console.error(error);
    } finally {
        localStorage.removeItem('admin_auth_token');
        window.location.href = '/admin/login';
    }
};