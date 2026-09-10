// ==========================================
// CONFIGURACIÓN DE ENDPOINTS 
// ==========================================
const API_CATEGORIES = "/api/categories"; 
const API_SUBCATEGORIES = "/api/categories/parent/"; 
const API_PRODUCTS_ALL = "/api/admin/products"; 
const API_PRODUCTS_BY_CATEGORY = "/api/categories/"; 

// ==========================================
// ESTADO GLOBAL EN MEMORIA
// ==========================================
let listaProductosMemoria = []; 
let editSelectedFiles = []; 
let imagenesEliminadas = []; 
let listaCategoriasMemoria = [];
let currentEditingAuctionId = null;
let productGalleriesAdmin = {};
let adminAuctionPollTimer = null;

document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    //ELEMENTOS DEL DOM
    // ==========================================
    const filterCategory = document.getElementById("filterCategory");
    const filterSubcategory = document.getElementById("filterSubcategory");
    const productsGrid = document.getElementById("productsGrid");
    const loadingSpinner = document.getElementById("loadingSpinner");
    
    const editCategorySelect = document.getElementById("edit_category_id");
    const editSubcategorySelect = document.getElementById("edit_subcategory_id");
    const editSaleTypeSelect = document.getElementById("edit_sale_type");
    
    // ==========================================
    // EFINICIÓN DE FUNCIONES
    // ==========================================
    
    function cargarCategoriasPrincipales() 
    {
        fetch(API_CATEGORIES)
            .then(response => response.json())
            .then(res => {
                let categories = Array.isArray(res) ? res : (res.data || []);
                const parentCategories = categories.filter(c => c.isActive && (c.parentCategoryId === null || c.parentCategoryId === undefined));
                
                parentCategories.forEach(cat => {
                    const option = document.createElement("option");
                    option.value = cat.id;
                    option.textContent = cat.name;
                    filterCategory.appendChild(option);
                });
            })
            .catch(err => console.error("Error al cargar categorías:", err));
    }

    /*
    window.cargarProductos = function(url) {
        loadingSpinner.classList.remove("d-none");

        Array.from(productsGrid.children).forEach(child => {
            if (child.id !== "loadingSpinner") child.remove();
        });

        fetch(url, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
            }
        })
            .then(response => response.json())
            .then(res => {
                loadingSpinner.classList.add("d-none");
                let productos = Array.isArray(res) ? res : (res.data || []);
                listaProductosMemoria = productos; // guardamos SIN filtrar, es la fuente de verdad completa
                renderizarGrid(aplicarFiltroEstado(productos));
            })
            .catch(err => {
                console.error("Error al cargar productos:", err);
                loadingSpinner.classList.add("d-none");
                productsGrid.innerHTML += `<div class="col-12 text-center text-danger"><p>Error de conexión al cargar el catálogo.</p></div>`;
            });
    }
    */


    window.hoverImagenAdmin = function(productId, isHovering) {
    const gallery = productGaleriesAdminSafe(productId);
    if (!gallery || gallery.images.length < 2) return;

    const imgElement = document.getElementById(`admin-img-${productId}`);
    if (isHovering && gallery.currentIndex === 0) {
        imgElement.src = gallery.images[1];
    } else if (!isHovering) {
        imgElement.src = gallery.images[gallery.currentIndex];
    }
};

    window.cambiarImagenAdmin = function(productId, direction, event) {
        event.stopPropagation();

        const gallery = productGaleriesAdminSafe(productId);
        if (!gallery || gallery.images.length < 2) return;

        gallery.currentIndex += direction;
        if (gallery.currentIndex >= gallery.images.length) gallery.currentIndex = 0;
        else if (gallery.currentIndex < 0) gallery.currentIndex = gallery.images.length - 1;

        document.getElementById(`admin-img-${productId}`).src = gallery.images[gallery.currentIndex];
    };

    function productGaleriesAdminSafe(productId) {
        return productGalleriesAdmin[productId];
    }

    const STATUS_LABELS = {
        available: { text: 'Disponible', badge: 'bg-success' },
        reserved:  { text: 'Reservado',  badge: 'bg-warning text-dark' },
        sold:      { text: 'Vendido',    badge: 'bg-secondary' },
        disabled:  { text: 'Desactivado', badge: 'bg-dark' }
    };

    

function renderizarGrid(productos) {
    const STORAGE_URL = "/storage/";

    if (productos.length === 0) {
        productsGrid.innerHTML = `
            <div class="col-12 text-center py-5 text-muted">
                <i class="fas fa-box-open fa-3x mb-3"></i>
                <h5>No hay productos que coincidan con estos filtros.</h5>
            </div>`;
        return;
    }

    let html = "";
    productos.forEach(prod => {
        const fotos = (prod.images && prod.images.length > 0)
            ? prod.images.map(img => STORAGE_URL + img.url)
            : ["https://placehold.co/400x400?text=Sin+Imagen"];

        productGalleriesAdmin[prod.id] = { images: fotos, currentIndex: 0 };

        const precio = parseFloat(prod.price).toFixed(2);
        const statusInfo = STATUS_LABELS[prod.status] || { text: prod.status, badge: 'bg-secondary' };
        const bloqueado = prod.status === 'reserved' || prod.status === 'sold';
        const tooltip = bloqueado ? `title="No se puede modificar: producto ${statusInfo.text.toLowerCase()}"` : '';

        html += `
        <div class="col-6 col-lg-3 mb-4">
            <div class="admin-product-card">
                <div class="admin-product-image-wrapper"
                     onmouseenter="hoverImagenAdmin(${prod.id}, true)"
                     onmouseleave="hoverImagenAdmin(${prod.id}, false)">
                    <span class="badge ${statusInfo.badge} admin-status-badge">${statusInfo.text}</span>
                    <img id="admin-img-${prod.id}" src="${fotos[0]}" alt="${prod.name}" loading="lazy">
                    ${fotos.length > 1 ? `
                        <button class="admin-carousel-btn prev" onclick="cambiarImagenAdmin(${prod.id}, -1, event)">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="admin-carousel-btn next" onclick="cambiarImagenAdmin(${prod.id}, 1, event)">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="admin-product-info">
                    <h6 title="${prod.name}">${prod.name}</h6>
                    <p class="admin-product-price">Q ${precio}</p>
                </div>
                <div class="admin-product-actions">
                    <button class="btn btn-warning btn-sm px-3 text-dark fw-bold"
                            onclick="editarProducto(${prod.id})" ${bloqueado ? 'disabled' : ''} ${tooltip}>
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm px-3"
                            onclick="eliminarProducto(${prod.id}, '${prod.name}')" ${bloqueado ? 'disabled' : ''} ${tooltip}>
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        `;
    });
    productsGrid.innerHTML = html;
}




    // Aplica el filtro de estado sobre lo que ya está en memoria — sin volver a pedir al servidor
    function aplicarFiltroEstado(productos) {
        const estado = document.getElementById("filterStatus").value;
        if (!estado) return productos;
        return productos.filter(p => p.status === estado);
    }

    /*
    window.refrescarGridConFiltrosActuales = function() {
        const subId = filterSubcategory.value;
        const catId = filterCategory.value;

        if (subId) {
            window.cargarProductos(`${API_PRODUCTS_BY_CATEGORY}${subId}/products`);
        } else if (catId) {
            window.cargarProductos(`${API_PRODUCTS_BY_CATEGORY}${catId}/products`);
        } else {
            window.cargarProductos(API_PRODUCTS_ALL);
        }
    }
    */

    window.refrescarGridConFiltrosActuales = function() {
        window.cargarProductos(adminCurrentPage);
    }




    // ==========================================
    // EVENTOS DE FILTROS
    // ==========================================
    /*
    filterCategory.addEventListener("change", (e) => {
        const parentId = e.target.value;
        filterSubcategory.innerHTML = '<option value="">Todas las subcategorías...</option>';
        
        if (!parentId) {
            filterSubcategory.disabled = true;
            window.cargarProductos(API_PRODUCTS_ALL); 
            return;
        }

        filterSubcategory.disabled = false;
        window.cargarProductos(`${API_PRODUCTS_BY_CATEGORY}${parentId}/products`);
        
        fetch(`${API_SUBCATEGORIES}${parentId}`)
            .then(response => response.json())
            .then(res => {
                let subcategories = Array.isArray(res) ? res : (res.data || []);
                subcategories.forEach(sub => {
                    const option = document.createElement("option");
                    option.value = sub.id;
                    option.textContent = sub.name;
                    filterSubcategory.appendChild(option);
                });
            })
            .catch(err => console.error("Error al cargar subcategorías:", err));
    });

    filterSubcategory.addEventListener("change", (e) => {
        const subcategoryId = e.target.value;
        if (subcategoryId) {
            window.cargarProductos(`${API_PRODUCTS_BY_CATEGORY}${subcategoryId}/products`);
        } else {
            const parentId = filterCategory.value;
            window.cargarProductos(`${API_PRODUCTS_BY_CATEGORY}${parentId}/products`);
        }
    });

    document.getElementById("filterStatus").addEventListener("change", () => {
        renderizarGrid(aplicarFiltroEstado(listaProductosMemoria));
    });

    */
    const PER_PAGE_ADMIN = 24;
    let adminCurrentPage = 1;
    let adminTotalPages = 1;

    function construirUrlAdmin(page) {
        const params = new URLSearchParams();
        params.set('page', page);
        params.set('per_page', PER_PAGE_ADMIN);

        const status = document.getElementById("filterStatus").value;
        if (status) params.set('status', status);

        const subcategoryId = filterSubcategory.value;
        const parentId = filterCategory.value;
        const categoryId = subcategoryId || parentId; // subcategoría manda si hay una elegida

        if (categoryId) params.set('category_id', categoryId);

        return `${API_PRODUCTS_ALL}?${params.toString()}`;
    }

    window.cargarProductos = function(page = 1) {
        // Mostramos un spinner de carga y limpiamos el grid de una vez
        productsGrid.innerHTML = `
            <div class="col-12 text-center py-5">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
                <p class="mt-2 text-muted">Cargando productos...</p>
            </div>`;

        fetch(construirUrlAdmin(page), {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
            }
        })
            .then(response => response.json())
            .then(res => {
                let productos = Array.isArray(res) ? res : (res.data || []);

                if (productos.length === 0 && page > 1 && res.meta && res.meta.last_page < page) {
                    window.cargarProductos(res.meta.last_page);
                    return;
                }

                listaProductosMemoria = productos;
                renderizarGrid(productos);  // esto reemplaza el spinner con las tarjetas
                window.scrollTo({ top: 0, behavior: 'instant' });

                if (res.meta) {
                    adminCurrentPage = res.meta.current_page;
                    adminTotalPages = res.meta.last_page;
                    renderizarPaginacionAdmin();
                }
            })
            .catch(err => {
                console.error("Error al cargar productos:", err);
                productsGrid.innerHTML = `<div class="col-12 text-center text-danger"><p>Error de conexión al cargar el catálogo.</p></div>`;
            });
    };    

    function renderizarPaginacionAdmin() {
        const contenedor = document.getElementById('adminPagination');
        if (!contenedor) return;

        if (adminTotalPages <= 1) {
            contenedor.innerHTML = '';
            return;
        }

        let html = `<button class="page-btn page-nav" data-page="${adminCurrentPage - 1}" ${adminCurrentPage === 1 ? 'disabled' : ''}>‹</button>`;

        for (let i = 1; i <= adminTotalPages; i++) {
            html += `<button class="page-btn ${i === adminCurrentPage ? 'active' : ''}" data-page="${i}">${i}</button>`;
        }

        html += `<button class="page-btn page-nav" data-page="${adminCurrentPage + 1}" ${adminCurrentPage === adminTotalPages ? 'disabled' : ''}>›</button>`;

        contenedor.innerHTML = html;

        contenedor.querySelectorAll('.page-btn:not([disabled])').forEach(btn => {
            btn.addEventListener('click', () => window.cargarProductos(parseInt(btn.dataset.page, 10)));
        });

        // Auto-scroll: centra el botón de la página activa dentro de la barra
        const botonActivo = contenedor.querySelector('.page-btn.active');
        if (botonActivo) {
            // Centramos el botón activo SOLO moviendo el scroll horizontal de la barra,
            // sin tocar el scroll vertical de la página (scrollIntoView sí lo tocaba).
            const offset = botonActivo.offsetLeft - (contenedor.clientWidth / 2) + (botonActivo.clientWidth / 2);
            contenedor.scrollTo({ left: offset, behavior: 'smooth' });
        }
    }

    // ==========================================
    // EVENTOS DE FILTROS
    // ==========================================
    filterCategory.addEventListener("change", (e) => {
        const parentId = e.target.value;
        filterSubcategory.innerHTML = '<option value="">Todas las subcategorías...</option>';

        if (!parentId) {
            filterSubcategory.disabled = true;
            window.cargarProductos(1);
            return;
        }

        filterSubcategory.disabled = false;
        window.cargarProductos(1);

        fetch(`${API_SUBCATEGORIES}${parentId}`)
        .then(response => response.json())
        .then(res => {
            let subcategories = Array.isArray(res) ? res : (res.data || []);
            subcategories.filter(sub => sub.isActive).forEach(sub => {
                const option = document.createElement("option");
                option.value = sub.id;
                option.textContent = sub.name;
                filterSubcategory.appendChild(option);
            });
        })
        .catch(err => console.error("Error al cargar subcategorías:", err));
    });

    filterSubcategory.addEventListener("change", () => {
        window.cargarProductos(1);
    });

    document.getElementById("filterStatus").addEventListener("change", () => {
        window.cargarProductos(1);
    });

    // ==========================================
    // INICIALIZACIÓN (Llamamos a las funciones)
    // ==========================================
    cargarCategoriasPrincipales();
    window.cargarProductos(1); 










    // ==========================================
    // ELIMINAR PRODUCTO
    // ==========================================
    window.eliminarProducto = async function(id, nombre) {
        const confirmado = await adminConfirm(
            `¿Estás completamente seguro de eliminar el producto "${nombre}"? Esta acción no se puede deshacer.`,
            { title: 'Eliminar producto', confirmText: 'Eliminar', confirmClass: 'btn-danger', icon: 'fa-trash' }
        );
        if (!confirmado) return;

        fetch(`/api/products/${id}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
            }
        })
            .then(async response => {
                const data = await response.json();

                if (response.ok) {
                    adminAlert("Producto eliminado exitosamente", { type: 'success' });
                    window.refrescarGridConFiltrosActuales();
                } else {
                    adminAlert(data.message || "No se pudo eliminar el producto.", { type: 'error' });
                }
            })
            .catch(err => {
                console.error("Error eliminando:", err);
                adminAlert("Ocurrió un error de conexión al intentar eliminar el producto.", { type: 'error' });
            });
    };





    // ==========================================
    // PREPARACIÓN DEL MODAL DE EDICIÓN
    // ==========================================
    
    // Cargar categorías padre para el select del modal
    fetch(API_CATEGORIES)
    .then(res => res.json())
    .then(data => {
        const categories = Array.isArray(data) ? data : (data.data || []);
        
        // GUARDAMOS TODAS LAS CATEGORÍAS EN MEMORIA ANTES DE FILTRARLAS
        listaCategoriasMemoria = categories; 
        
        editCategorySelect.innerHTML = '<option value="">Seleccione una categoría</option>';
        categories.filter(c => c.isActive && !c.parentCategoryId).forEach(c => {
            editCategorySelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
        });
    });

    // Función inteligente para sincronizar las subcategorías en el Modal
    window.cargarSubcategoriasEdicion = function(parentId, subcategoryIdASeleccionar = null) {
        if (!parentId) {
            editSubcategorySelect.innerHTML = '<option value="">Selecciona una categoría primero</option>';
            editSubcategorySelect.disabled = true;
            editSubcategorySelect.required = false;
            return;
        }

        editSubcategorySelect.innerHTML = '<option value="">Cargando...</option>';
        editSubcategorySelect.disabled = true;

        fetch(`${API_SUBCATEGORIES}${parentId}`)
            .then(res => res.json())
            .then(data => {
                const subcategories = Array.isArray(data) ? data : (data.data || []);

                if (subcategories.length > 0) {
                    editSubcategorySelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
                    subcategories.filter(c => c.isActive).forEach(sub => {
                        editSubcategorySelect.innerHTML += `<option value="${sub.id}">${sub.name}</option>`;
                    });
                    editSubcategorySelect.disabled = false;
                    editSubcategorySelect.required = true;

                    // Si la subcategoría asignada al producto no está entre las activas
                    // (la desactivaron después de asignarla), la agregamos igual —
                    // si no, el select se queda en blanco aunque el dato siga existiendo.
                    if (subcategoryIdASeleccionar) {
                        const yaExiste = subcategories.some(sub => sub.id == subcategoryIdASeleccionar && sub.isActive);
                        if (!yaExiste) {
                            const inactiva = subcategories.find(sub => sub.id == subcategoryIdASeleccionar);
                            const nombre = inactiva ? inactiva.name : `Subcategoría #${subcategoryIdASeleccionar}`;
                            editSubcategorySelect.innerHTML += `<option value="${subcategoryIdASeleccionar}">${nombre} (inactiva)</option>`;
                        }
                        editSubcategorySelect.value = subcategoryIdASeleccionar;
                    }
                } else {
                    editSubcategorySelect.innerHTML = '<option value="">Sin subcategorías disponibles</option>';
                    editSubcategorySelect.required = false;
                }
            })
            .catch(err => console.error("Error cargando subcategorías", err));
    };


    





    // Cuando se cambia el padre en el modal, cargar hijos
    editCategorySelect.addEventListener("change", (e) => {
        window.cargarSubcategoriasEdicion(e.target.value);
    });

    const currencyInputs = document.querySelectorAll('.currency-input');
    currencyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) this.value = parseFloat(this.value).toFixed(2);
        });
    });

    // ==========================================
    // ABRIR MODAL
    // ==========================================
    window.editarProducto = function(id) {
        const prod = listaProductosMemoria.find(p => p.id === id);
        if (!prod) {
            adminAlert("No se encontró el producto.", { type: 'error' });
            return;
        }

        document.getElementById("edit_product_id").value = prod.id;
        document.getElementById("edit_name").value = prod.name;
        document.getElementById("edit_price").value = prod.price;
        document.getElementById("edit_offer_price").value = prod.offerPrice || '';
        document.getElementById("edit_description").value = prod.description;
        document.getElementById("edit_sale_type").value = prod.saleType;
        document.getElementById("edit_status").value = prod.status;
        document.getElementById("edit_cost").value = prod.cost || '';

        const apiCategoryId = prod.categoryId || prod.category_id;

        if (apiCategoryId) {
            // Buscamos esta categoría en nuestra lista guardada en memoria
            const categoryData = listaCategoriasMemoria.find(c => c.id === apiCategoryId);
            
            let idParaPadre = "";
            let idParaSubcategoria = "";

            if (categoryData) {
                if (categoryData.parentCategoryId) {
                    // REGLA: Si la categoría tiene un "parentCategoryId", entonces es una Subcategoría.
                    idParaPadre = categoryData.parentCategoryId; // El padre real va al primer selector
                    idParaSubcategoria = categoryData.id;        // Y ella misma se pre-selecciona en el segundo
                } else {
                    // REGLA: Si no tiene padre, es una categoría principal (raíz).
                    idParaPadre = categoryData.id;
                    idParaSubcategoria = ""; // No hay subcategoría asignada
                }
            } else {
                // Fallback por si la categoría fue eliminada o no está en memoria
                idParaPadre = apiCategoryId;
                idParaSubcategoria = "";
            }

            // Asignamos el valor al selector padre y disparamos la carga asíncrona de sus hijas
            document.getElementById("edit_category_id").value = idParaPadre;
            window.cargarSubcategoriasEdicion(idParaPadre, idParaSubcategoria);
        } else {
            // Si el producto no tiene ninguna categoría asignada
            document.getElementById("edit_category_id").value = "";
            window.cargarSubcategoriasEdicion(null);
        }

        const previewContainer = document.getElementById("edit_images_preview");
        previewContainer.innerHTML = ""; 
        imagenesEliminadas = []; 
        editSelectedFiles = [];  

        if (prod.images && prod.images.length > 0) {
            prod.images.forEach(img => {
                const div = document.createElement("div");
                div.className = "position-relative edit-image-item";

                div.innerHTML = `
                    <img src="/storage/${img.url}" style="width: 100%; height: 100%; object-fit: cover;">
                    <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: -5px; right: -5px;" onclick="eliminarImagenAntigua(${img.id}, this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            });
        }

        // Bloqueamos el tipo de venta visualmente, pero SIN "disabled"
        // (así su valor actual sí se sigue enviando en el submit)
        editSaleTypeSelect.style.pointerEvents = "none";
        editSaleTypeSelect.style.backgroundColor = "#e9ecef";
        editSaleTypeSelect.tabIndex = -1;

        actualizarSeccionesPorTipoVenta(id);

        // USO CORRECTO DEL MODAL BOOTSTRAP (Previene crear instancias duplicadas)
        const modalElement = document.getElementById('modalEditarProducto');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
    };


    // ==========================================
    // MANEJO DE IMÁGENES
    // ==========================================
    window.eliminarImagenAntigua = function(imageId, btnElement) {
        imagenesEliminadas.push(imageId);
        btnElement.closest('.edit-image-item').remove();
    };

    document.getElementById("edit_new_images").addEventListener("change", (e) => {
        const files = Array.from(e.target.files);
        const previewContainer = document.getElementById("edit_images_preview");

        files.forEach((file) => {
            editSelectedFiles.push(file);
            const fileIndex = editSelectedFiles.length - 1; 
            const reader = new FileReader();
            reader.onload = (event) => {
                const div = document.createElement("div");
                div.className = "position-relative edit-image-item"; 
                div.innerHTML = `
                    <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 2px dashed #ffc107;">
                    <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: -5px; right: -5px;" onclick="eliminarImagenNueva(${fileIndex}, this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
        e.target.value = ""; 
    });

    window.eliminarImagenNueva = function(index, btnElement) {
        editSelectedFiles[index] = null; 
        btnElement.closest('.edit-image-item').remove();
    };

    // ==========================================
    // ENVIAR FORMULARIO (GUARDAR)
    // ==========================================
    document.getElementById("formEditarProducto").addEventListener("submit", function(e) {
        e.preventDefault(); 
        const form = e.target;

        // Si el padre tiene subcategorías cargadas pero no se eligió ninguna, detenemos el guardado
        const tieneSubcategoriasDisponibles = editSubcategorySelect.options.length > 1 && !editSubcategorySelect.disabled;
        if (tieneSubcategoriasDisponibles && !editSubcategorySelect.value) {
            adminAlert("Esta categoría tiene subcategorías. Por favor selecciona una antes de guardar.", { type: 'warning', title: 'Falta seleccionar' });
            return;
        }

        const categoriaFinal = editSubcategorySelect.value || editCategorySelect.value;

        const formData = new FormData(form);
        formData.append('_method', 'PUT');
        formData.delete('new_images[]');
        formData.set('categoryId', categoriaFinal); // 👈 ahora sí, después de crear formData

        const archivosFinales = editSelectedFiles.filter(f => f !== null);
        archivosFinales.forEach(file => formData.append('new_images[]', file));
        imagenesEliminadas.forEach(id => formData.append('deleted_images[]', id));

        const productId = document.getElementById("edit_product_id").value;
        const btnGuardar = form.querySelector("button[type='submit']");
        const textoOriginal = btnGuardar.innerHTML;
        


        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

        fetch(`/api/products/${productId}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
            }
        })
        .then(response => response.json().then(data => ({ status: response.status, body: data })))
        .then(res => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;

            if (res.status === 200 || res.status === 201) {
                adminAlert("¡Producto actualizado exitosamente!", { type: 'success' });
                window.refrescarGridConFiltrosActuales();
                
                try {
                    const modalElement = document.getElementById('modalEditarProducto');
                    if (modalElement) {
                        // SOLUCIÓN AL ERROR ARIA-HIDDEN: Quitamos el foco al botón antes de ocultar
                        if (document.activeElement && modalElement.contains(document.activeElement)) {
                            document.activeElement.blur(); 
                        }
                        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
                        modalInstance.hide();
                    }
                } catch (errorModal) {
                    console.warn("No se pudo cerrar el modal:", errorModal);
                }
            } else {
                if (res.status === 422) {
                    console.log("Errores de validación:", res.body.errors);
                    adminAlert("Error de validación. Revisa la consola.", { type: 'error' });
                } else {
                    adminAlert("Error: " + (res.body.message || "Problema al guardar."), { type: 'error' });
                }
            }
        })
        .catch(err => {
            console.error("Error en petición:", err);
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
            adminAlert("Error de conexión al servidor.", { type: 'error' });
        });
    });







    async function actualizarSeccionesPorTipoVenta(productId) {
        const esSubasta = editSaleTypeSelect.value === 'auction';
        const priceInput = document.getElementById("edit_price");
        const offerWrapper = document.getElementById("editOfferPriceWrapper");
        const offerInput = document.getElementById("edit_offer_price");
        const auctionInfoPanel = document.getElementById("editAuctionInfo");

        offerWrapper.classList.toggle('d-none', esSubasta);
        offerInput.disabled = esSubasta;
        if (esSubasta) offerInput.value = '';

        // El precio base de una subasta no se edita una vez creada: se deja
        // visible mas como referencia, pero de solo lectura (readOnly, no disabled,
        // para que su valor SÍ se siga enviando y no rompa la validación "required")
        priceInput.readOnly = esSubasta;
        priceInput.classList.toggle('bg-light', esSubasta);

        auctionInfoPanel.classList.toggle('d-none', !esSubasta);

        if (esSubasta) {
            await cargarInfoSubastaParaEdicion(productId);
        }else {
            clearInterval(adminAuctionPollTimer);
        }
    }

    function iniciarPollingEstadoSubastaAdmin(productId) {
        clearInterval(adminAuctionPollTimer);
        document.getElementById("auctionEndedNotice").style.display = "none";

        adminAuctionPollTimer = setInterval(async () => {
            try {
                const response = await fetch(`/api/auctions/product/${productId}/status`, { cache: 'no-store' });
                const data = await response.json();
                if (!data.success || !data.data) return;

                const estado = data.data;
                const yaFinalizo = estado.status !== 'active' || estado.secondsRemaining <= 0;

                if (yaFinalizo) {
                    clearInterval(adminAuctionPollTimer);
                    mostrarAvisoSubastaFinalizadaAdmin(estado);
                }
            } catch (error) {
                console.error('Error revisando estado de subasta:', error);
            }
        }, 6000);
    }

    function mostrarAvisoSubastaFinalizadaAdmin(estado) {
        document.getElementById("editAuctionStatus").textContent = "Finalizada";

        const huboGanador = estado.currentWinnerUserId !== null && estado.currentWinnerUserId !== undefined;

        const mensaje = huboGanador
            ? "Esta subasta acaba de finalizar CON un ganador. El producto quedó reservado esperando su pago — recarga la página para ver los detalles actualizados."
            : "Esta subasta acaba de finalizar SIN ninguna puja. El producto pasará automáticamente a venta directa — recarga la página para editarlo con ese formulario.";

        document.getElementById("auctionEndedNoticeText").textContent = mensaje;
        document.getElementById("auctionEndedNotice").style.display = "block";

        // Ya no tiene sentido dejar que intenten extender la duración de algo que ya terminó
        document.getElementById("saveAuctionDurationBtn").disabled = true;
        document.getElementById("editAuctionEndDate").disabled = true;
    }

    document.getElementById('modalEditarProducto').addEventListener('hidden.bs.modal', () => {
        clearInterval(adminAuctionPollTimer);
    });




    // Convierte una fecha UTC del servidor ("Y-m-d H:i:s") a un valor
    // que el input datetime-local pueda mostrar en la hora LOCAL del navegador
    function utcToLocalInputValue(utcString) {
        const utcDate = new Date(utcString.replace(' ', 'T') + 'Z'); // la 'Z' le dice a JS que es UTC
        const localMs = utcDate.getTime() - utcDate.getTimezoneOffset() * 60000;
        return new Date(localMs).toISOString().slice(0, 16);
    }    

    async function cargarInfoSubastaParaEdicion(productId) {
        try {
            const [statusRes, bidsRes] = await Promise.all([
                fetch(`/api/auctions/product/${productId}/status`, { cache: 'no-store' }),
                fetch(`/api/auctions/product/${productId}/bids`, { cache: 'no-store' })
            ]);
            const statusData = await statusRes.json();
            const bidsData = await bidsRes.json();

            if (!statusData.success || !statusData.data) {
                document.getElementById("editAuctionInfo").innerHTML = `<p class="text-danger">No se encontró la subasta de este producto.</p>`;
                return;
            }

            const auction = statusData.data;
            currentEditingAuctionId = auction.id;

            document.getElementById("editAuctionCurrentPrice").textContent = `Q${auction.currentPrice.toFixed(2)}`;
            document.getElementById("editAuctionBidCount").textContent = bidsData.data ? bidsData.data.length : 0;
            document.getElementById("editAuctionStatus").textContent = auction.status === 'active' ? 'Activa' : 'Finalizada';


            // Convertimos "Y-m-d H:i:s" a formato que acepta datetime-local (YYYY-MM-DDTHH:mm)
            const endDateInput = document.getElementById("editAuctionEndDate");
            endDateInput.value = utcToLocalInputValue(auction.endDate);

        } catch (error) {
            console.error('Error al cargar info de subasta:', error);
        }
    }

    // Convierte el valor local del input ("YYYY-MM-DDTHH:mm") de vuelta a UTC
    // en formato "Y-m-d H:i:s", que es lo que el backend espera
    function localInputValueToUtcString(localValue) {
        const localDate = new Date(localValue); // sin 'Z': JS lo interpreta como hora LOCAL
        const pad = n => String(n).padStart(2, '0');
        return `${localDate.getUTCFullYear()}-${pad(localDate.getUTCMonth() + 1)}-${pad(localDate.getUTCDate())} ${pad(localDate.getUTCHours())}:${pad(localDate.getUTCMinutes())}:00`;
    }
    document.getElementById("saveAuctionDurationBtn").addEventListener("click", async () => {
        const errorEl = document.getElementById("editAuctionDurationError");
        errorEl.classList.add('d-none');

        const newEndDate = document.getElementById("editAuctionEndDate").value;
        if (!newEndDate) return;

        try {
            const response = await fetch(`/api/auctions/${currentEditingAuctionId}/extend-duration`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
                },
                body: JSON.stringify({ newEndDate: localInputValueToUtcString(newEndDate) })
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo actualizar la duración.');

            adminAlert("Duración actualizada correctamente.", { type: 'success' });

        } catch (error) {
            errorEl.textContent = error.message;
            errorEl.classList.remove('d-none');
        }
    });


    





}); // FIN DEL DOMContentLoaded