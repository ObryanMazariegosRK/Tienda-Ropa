/*!
    * Start Bootstrap - SB Admin v7.0.7 (https://startbootstrap.com/template/sb-admin)
    * Copyright 2013-2023 Start Bootstrap
    * Licensed under MIT (https://github.com/StartBootstrap/startbootstrap-sb-admin/blob/master/LICENSE)
    */
    // 
// Scripts
// 

window.addEventListener('DOMContentLoaded', event => {

    // Toggle the side navigation
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (sidebarToggle) {
        // Uncomment Below to persist sidebar toggle between refreshes
        // if (localStorage.getItem('sb|sidebar-toggle') === 'true') {
        //     document.body.classList.toggle('sb-sidenav-toggled');
        // }
        sidebarToggle.addEventListener('click', event => {
            event.preventDefault();
            document.body.classList.toggle('sb-sidenav-toggled');
            localStorage.setItem('sb|sidebar-toggle', document.body.classList.contains('sb-sidenav-toggled'));
        });
    }

    document.addEventListener("DOMContentLoaded", () => {
        const rutaActual = window.location.pathname;
        const menuProductos = document.getElementById("collapseProductos");
        const botonMenuProductos = document.querySelector('[data-bs-target="#collapseProductos"]');

        if (menuProductos && botonMenuProductos) {
            if (rutaActual.includes("categorias.html") || rutaActual.includes("productos.html")) {
                botonMenuProductos.classList.remove("collapsed");
                botonMenuProductos.setAttribute("aria-expanded", "true");
                menuProductos.classList.add("show");

                if (rutaActual.includes("categorias.html")) {
                    const linkCat = document.getElementById("link-categorias");
                    if(linkCat) linkCat.classList.add("active");
                } else if (rutaActual.includes("productos.html")) {
                    const linkProd = document.getElementById("link-productos");
                    if(linkProd) linkProd.classList.add("active");
                }
            }
        }
    });




});

