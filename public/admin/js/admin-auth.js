(function() {
    const token = localStorage.getItem('admin_auth_token');

    if (!token) {
        window.location.href = '/admin/login';
        return;
    }

    // Verificamos que el token siga siendo válido Y que sea de un admin real
    fetch('/api/profile', {
        headers: { 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        cache: 'no-store'
    })
    .then(response => {
        if (!response.ok) throw new Error('Token inválido');
        return response.json();
    })
    .then(profile => {
        if (profile.role !== 'admin') throw new Error('Sin permisos');

        // Mostramos el contenido (estaba oculto por CSS mientras se validaba)
        document.body.classList.add('admin-auth-verified');

        const nameEl = document.getElementById('admin-user-name');
        if (nameEl) nameEl.textContent = profile.name;
    })
    .catch(() => {
        localStorage.removeItem('admin_auth_token');
        window.location.href = '/admin/login';
    });
})();

window.adminLogout = async function() {
    if (!confirm('¿Estás seguro de que quieres cerrar sesión?')) return;

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