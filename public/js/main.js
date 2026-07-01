document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelectorAll(".gallery-layout")

    const showClothes = (category) => {
        container.forEach(container => container.style.display = "none")

        let filterContainer;
        if(category === "minimal"){
            filterContainer = Array.from(container).filter(container => ["layout-2"].includes(container.id))
        } else{
            filterContainer = Array.from(container).filter(container => container.getAttribute("data-category") === category).slice(0, 1)
        }

        filterContainer.forEach(container => container.style.display = "grid")
    }

    document.querySelectorAll(".gallery-lists li").forEach(item => {
        item.addEventListener("click", (e) => {
            document.querySelectorAll(".gallery-lists li").forEach(li => li.classList.remove("active"))
            e.target.classList.add("active")

            let category = e.target.getAttribute("data-category")
            showClothes(category)
        })
    })

    const defaultItem = document.querySelector(".gallery-lists li[data-category='minimal']")
    showClothes(defaultItem.getAttribute("data-category"))


    //Código para el mensaje de la suscripción

    const subscriberInput = document.querySelector(".subscriber-input")
    const subscriberBtn = document.querySelector(".subscriber-btn")
    const subscriberThanks = document.querySelector(".subscriber-thanks")

    let timeOutId;

    subscriberBtn.disabled = true;

    subscriberInput.addEventListener("input", () => {
        if(subscriberInput.value.length > 0){
            subscriberBtn.disabled = false
        } else {
            subscriberBtn.disabled = true
        }
    })

    subscriberBtn.addEventListener("click", ()=> {
        subscriberInput.value = "";
        subscriberThanks.style.display = "block"

        clearTimeout(timeOutId)

        timeOutId = setTimeout(()=> {
            subscriberThanks.style.display = "none"
        }, 3000)
    })

    const navCategoriesContainer = document.getElementById("nav-categories");
    // INTERACTIVIDAD DEL MENÚ MÓVIL
    
    const mobileMenuBtn = document.getElementById("mobile-menu-btn");
    const mainNav = document.getElementById("main-nav");

    //Abrir/Cerrar el menú lateral completo
    mobileMenuBtn.addEventListener("click", () => {
        mainNav.classList.toggle("active");
    });

    //Funcionalidad de "Acordeón" para las subcategorías
    navCategoriesContainer.addEventListener("click", (e) => {
        //Solo activamos el acordeón si estamos en pantalla de celular
        if (window.innerWidth <= 768) {
            const clickedLink = e.target;
            const navItem = clickedLink.closest(".nav-item");
            
            //Verificamos si tocamos un enlace que tiene un submenú debajo
            if (clickedLink.tagName === 'A' && clickedLink.nextElementSibling?.classList.contains('dropdown-menu')) {
                e.preventDefault(); //Evitamos que nos cambie de página
                navItem.classList.toggle("open"); //Abre/cierra el submenú
            }
        }
    });




    // CÓDIGO PARA CARGAR PRODUCTOS DESDE LA API

    const productsContainer = document.getElementById("products-container");

    const loadFeaturedProducts = async () => {
        try {
            //Llamamos a la API de Laravel 
            const response = await fetch('/api/products');
            const result = await response.json();

            //Si la respuesta es correcta y hay datos
            if (result.success && result.data.length > 0) {
                //Limpiamos el texto de "Cargando..."
                productsContainer.innerHTML = ""; 

                //Recorremos cada producto que viene de la base de datos
                result.data.forEach(product => {
                    //Creamos el diseño HTML para cada producto usando Template Literals 
                    const productHtml = `
                        <div class="product" data-id="${product.id}">
                            <img src="/image/product-1.png" alt="${product.name}">
                            <h3>${product.name}</h3>
                            <p class="product-price">$${product.price}</p>
                        </div>
                    `;
                    
                    // Inyectamos el producto en el contenedor
                    productsContainer.innerHTML += productHtml;
                });
            } else {
                productsContainer.innerHTML = "<p>No hay productos disponibles en este momento.</p>";
            }

        } catch (error) {
            console.error("Error al cargar los productos:", error);
            productsContainer.innerHTML = "<p>Ocurrió un error al cargar el catálogo.</p>";
        }
    };

    loadFeaturedProducts();


    // CÓDIGO PARA CARGAR EL MENÚ DINÁMICO 

    const loadCategories = async () => {
        try {
            //Hacemos UNA SOLA petición para traer todo el catálogo de categorías
            const response = await fetch('/api/categories');
            let categorias = await response.json();

            //Si por alguna razón viene envuelto en un 'data', lo extraemos. 
            // Si ya es un arreglo (como nos mostraste), lo dejamos igual.
            if (categorias.data) {
                categorias = categorias.data;
            }

            //Validamos que sea un arreglo y tenga elementos
            if (Array.isArray(categorias) && categorias.length > 0) {
                navCategoriesContainer.innerHTML = ""; // Quitamos el "Cargando menú..."

                //Filtramos solo los Padres (los que tienen parentCategoryId como null)
                const padres = categorias.filter(cat => cat.parentCategoryId === null);

                //Recorremos cada Padre para armar su HTML
                padres.forEach(padre => {
                    
                    //Buscamos si este padre tiene hijos DENTRO de la misma lista que ya descargamos
                    const hijos = categorias.filter(cat => cat.parentCategoryId === padre.id);

                    let dropdownHtml = ""; 
                    
                    //Si encontramos hijos, armamos la caja del submenú
                    if (hijos.length > 0) {
                        dropdownHtml = `<ul class="dropdown-menu">`;
                        hijos.forEach(hijo => {
                            dropdownHtml += `<li><a href="#">${hijo.name}</a></li>`;
                        });
                        dropdownHtml += `</ul>`;
                    }

                    //Armamos el HTML del Padre con o sin submenú
                    const liPadreHtml = `
                        <li class="nav-item">
                            <a href="#">${padre.name}</a>
                            ${dropdownHtml}
                        </li>
                    `;

                    //Lo inyectamos en la barra de navegación
                    navCategoriesContainer.innerHTML += liPadreHtml;
                });
                
            } else {
                navCategoriesContainer.innerHTML = '<li class="nav-item"><a href="#">Sin categorías</a></li>';
            }
        } catch (error) {
            console.error("Error al cargar el menú de categorías:", error);
            navCategoriesContainer.innerHTML = '<li class="nav-item"><a href="#">Error al cargar</a></li>';
        }
    };
    //Ejecutamos la función inmediatamente al cargar la página
    loadCategories();


    //CÓDIGO PARA CARGAR EL BANNER DINÁMICO
    const heroBannerContainer = document.getElementById("hero-banner-container");

    const loadHeroBanner = async () => {
        try {

            const response = await fetch('/api/banner'); 
            const bannerData = await response.json();

            // NOTA: Asumimos que tu API devolverá algo como: 
            // { success: true, data: { type: 'video', url: '/videos/campana-verano.mp4' } }

            if (bannerData.success && bannerData.data) {
                heroBannerContainer.innerHTML = ""; // Limpiamos el texto de carga

                const { type, url } = bannerData.data;

                if (type === 'video') {
                    //Si es video, inyectamos la etiqueta <video> silenciada y en bucle
                    heroBannerContainer.innerHTML = `
                        <video class="hero-media" autoplay muted loop playsinline>
                            <source src="${url}" type="video/mp4">
                            Tu navegador no soporta videos.
                        </video>
                    `;
                } else if (type === 'image') {
                    //Si es imagen, inyectamos la etiqueta <img>
                    heroBannerContainer.innerHTML = `
                        <img src="${url}" alt="Campaña actual" class="hero-media">
                    `;
                }
            } else {
                //Banner por defecto por si la base de datos está vacía
                heroBannerContainer.innerHTML = `
                    <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
                `;
            }

        } catch (error) {
            console.error("Error al cargar el banner:", error);
            //Si hay error, mostramos la imagen por defecto
            heroBannerContainer.innerHTML = `
                <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
            `;
        }
    };

    //llamamos a la función para que se ejecute al cargar la página
    loadHeroBanner();
})