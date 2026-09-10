window.adminConfirm = function(message, options = {}) {
    return new Promise((resolve) => {
        const modalEl = document.getElementById('adminConfirmModal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        document.getElementById('adminConfirmTitle').textContent = options.title || '¿Estás seguro?';
        document.getElementById('adminConfirmMessage').textContent = message;
        document.getElementById('adminConfirmIcon').innerHTML = `<i class="fas ${options.icon || 'fa-question-circle'}"></i>`;

        const viejoBtn = document.getElementById('adminConfirmBtn');
        viejoBtn.textContent = options.confirmText || 'Confirmar';
        viejoBtn.className = `btn ${options.confirmClass || 'btn-danger'} flex-fill`;

        // Clonamos el botón para limpiar listeners de usos anteriores del modal
        const nuevoBtn = viejoBtn.cloneNode(true);
        viejoBtn.parentNode.replaceChild(nuevoBtn, viejoBtn);

        let resuelto = false;
        nuevoBtn.addEventListener('click', () => {
            resuelto = true;
            modal.hide();
            resolve(true);
        });

        modalEl.addEventListener('hidden.bs.modal', () => {
            if (!resuelto) resolve(false);
        }, { once: true });

        modal.show();
    });
};

window.adminAlert = function(message, options = {}) {
    const modalEl = document.getElementById('adminAlertModal');
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

    const tipo = options.type || 'success'; // 'success' | 'error' | 'warning'

    const titulosPorDefecto = {
        success: 'Listo',
        error: 'Ocurrió un error',
        warning: 'Atención'
    };
    const iconosPorTipo = {
        success: 'fa-check-circle',
        error: 'fa-times-circle',
        warning: 'fa-triangle-exclamation'
    };

    document.getElementById('adminAlertTitle').textContent = options.title || titulosPorDefecto[tipo];
    document.getElementById('adminAlertMessage').textContent = message;

    const iconEl = document.getElementById('adminAlertIcon');
    iconEl.innerHTML = `<i class="fas ${iconosPorTipo[tipo]}"></i>`;
    iconEl.classList.remove('admin-confirm-icon-error', 'admin-confirm-icon-success', 'admin-confirm-icon-warning');
    iconEl.classList.add(`admin-confirm-icon-${tipo}`);

    modal.show();
};