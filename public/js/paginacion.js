// Paginación compartida por la tienda (tienda/catalogo.js, subastas.js) y el panel del admin
// (productos-catalogo.js). Se carga antes que esos archivos en sus vistas.

// Arma los botones de paginación con un ancho fijo:  ‹ 1 … 4 5 [6] 7 8 … 20 ›
// Siempre se ven unos pocos números alrededor de la página actual (5 en computadora,
// 3 en celular) más la primera y la última.
function construirBotonesPaginacion(paginaActual, totalPaginas) {
    const visibles = window.innerWidth <= 768 ? 3 : 5;
    const mitad = Math.floor(visibles / 2);

    let inicio = Math.max(1, paginaActual - mitad);
    const fin = Math.min(totalPaginas, inicio + visibles - 1);
    inicio = Math.max(1, fin - visibles + 1);

    const paginas = [];
    if (inicio > 1) {
        paginas.push(1);
        if (inicio > 2) paginas.push('…');
    }
    for (let i = inicio; i <= fin; i++) paginas.push(i);
    if (fin < totalPaginas) {
        if (fin < totalPaginas - 1) paginas.push('…');
        paginas.push(totalPaginas);
    }

    let html = `<button class="page-btn page-nav" data-page="${paginaActual - 1}" ${paginaActual === 1 ? 'disabled' : ''} aria-label="Página anterior">‹</button>`;
    paginas.forEach(p => {
        html += p === '…'
            ? `<span class="page-ellipsis" aria-hidden="true">…</span>`
            : `<button class="page-btn ${p === paginaActual ? 'active' : ''}" data-page="${p}" ${p === paginaActual ? 'aria-current="page"' : ''}>${p}</button>`;
    });
    html += `<button class="page-btn page-nav" data-page="${paginaActual + 1}" ${paginaActual === totalPaginas ? 'disabled' : ''} aria-label="Página siguiente">›</button>`;

    return html;
}
