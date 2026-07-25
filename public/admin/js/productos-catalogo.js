// ==========================================
// CONFIGURACIÓN DE ENDPOINTS 
// ==========================================
const API_CATEGORIES = "/api/categories"; 
const API_SUBCATEGORIES = "/api/categories/parent/"; 
const API_PRODUCTS_ALL = "/api/products"; 
const API_PRODUCTS_BY_CATEGORY = "/api/categories/"; 

// ==========================================
// ESTADO GLOBAL EN MEMORIA
// ==========================================
let listaProductosMemoria = []; 
let editSelectedFiles = []; 
let imagenesEliminadas = []; 
let listaCategoriasMemoria = [];

document.addEventListener("DOMContentLoaded", () => {

    // ==========================================
    // ELEMENTOS DEL DOM
    // ==========================================
    const filterCategory = document.getElementById("filterCategory");
    const filterSubcategory = document.getElementById("filterSubcategory");
    const productsGrid = document.getElementById("productsGrid");
    const loadingSpinner = document.getElementById("loadingSpinner");
    
    const editCategorySelect = document.getElementById("edit_category_id");
    const editSubcategorySelect = document.getElementById("edit_subcategory_id");

    // ==========================================
    // DEFINICIÓN DE FUNCIONES (Deben ir antes de llamarlas)
    // ==========================================
    
    function cargarCategoriasPrincipales() {
        fetch(API_CATEGORIES)
            .then(response => response.json())
            .then(res => {
                let categories = Array.isArray(res) ? res : (res.data || []);
                const parentCategories = categories.filter(c => c.parentCategoryId === null || c.parentCategoryId === undefined);
                
                parentCategories.forEach(cat => {
                    const option = document.createElement("option");
                    option.value = cat.id;
                    option.textContent = cat.name;
                    filterCategory.appendChild(option);
                });
            })
            .catch(err => console.error("Error al cargar categorías:", err));
    }

    window.cargarProductos = function(url) {
        loadingSpinner.classList.remove("d-none");
        
        Array.from(productsGrid.children).forEach(child => {
            if (child.id !== "loadingSpinner") child.remove();
        });

        fetch(url)
            .then(response => response.json())
            .then(res => {
                loadingSpinner.classList.add("d-none");
                let productos = Array.isArray(res) ? res : (res.data || []);
                listaProductosMemoria = productos; 
                renderizarGrid(productos);
            })
            .catch(err => {
                console.error("Error al cargar productos:", err);
                loadingSpinner.classList.add("d-none");
                productsGrid.innerHTML += `<div class="col-12 text-center text-danger"><p>Error de conexión al cargar el catálogo.</p></div>`;
            });
    }

    function renderizarGrid(productos) {
        const STORAGE_URL = "/storage/";
        
        if (productos.length === 0) {
            productsGrid.innerHTML += `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fas fa-box-open fa-3x mb-3"></i>
                    <h5>No hay productos en esta categoría.</h5>
                </div>`;
            return;
        }

        let html = "";
        productos.forEach(prod => {
            const imagenPrincipal = (prod.images && prod.images.length > 0 && prod.images[0].url) 
                        ? STORAGE_URL + prod.images[0].url 
                        : "https://placehold.co/400x400?text=Sin+Imagen";

            const precio = parseFloat(prod.price).toFixed(2);

            html += `
            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                <div class="card h-100 shadow-sm border-0">
                    <img src="${imagenPrincipal}" class="card-img-top" alt="${prod.name}" style="height: 200px; object-fit: cover;">
                    <div class="card-body text-center">
                        <h6 class="card-title fw-bold mb-1">${prod.name}</h6>
                        <p class="text-success fw-bold mb-0">Q ${precio}</p>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3">
                        <button class="btn btn-warning btn-sm px-3 text-dark fw-bold" onclick="editarProducto(${prod.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm px-3" onclick="eliminarProducto(${prod.id}, '${prod.name}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            `;
        });
        productsGrid.innerHTML += html; 
    }

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

    // ==========================================
    // INICIALIZACIÓN (Llamamos a las funciones)
    // ==========================================
    cargarCategoriasPrincipales();
    window.cargarProductos(API_PRODUCTS_ALL); 


    // ==========================================
    // EVENTOS DE FILTROS
    // ==========================================
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

    // ==========================================
    // ELIMINAR PRODUCTO
    // ==========================================
    window.eliminarProducto = function(id, nombre) {
        if(confirm(`¿Estás completamente seguro de eliminar el producto "${nombre}"?\nEsta acción no se puede deshacer.`)) {
            fetch(`/api/products/${id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}`
                }
            })
                .then(response => {
                    if(response.ok) {
                        alert("Producto eliminado exitosamente");
                        window.refrescarGridConFiltrosActuales();
                    }
                })
                .catch(err => console.error("Error eliminando:", err));
        }
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
                    editSubcategorySelect.required = true; // 👈 el padre SÍ tiene hijas: obligar

                    if (subcategoryIdASeleccionar) {
                        editSubcategorySelect.value = subcategoryIdASeleccionar;
                    }
                } else {
                    editSubcategorySelect.innerHTML = '<option value="">Sin subcategorías disponibles</option>';
                    editSubcategorySelect.required = false; // 👈 el padre NO tiene hijas: opcional
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
            alert("Error: No se encontró el producto.");
            return;
        }

        document.getElementById("edit_product_id").value = prod.id;
        document.getElementById("edit_name").value = prod.name;
        document.getElementById("edit_price").value = prod.price;
        document.getElementById("edit_offer_price").value = prod.offerPrice || '';
        document.getElementById("edit_description").value = prod.description;
        document.getElementById("edit_sale_type").value = prod.saleType;
        document.getElementById("edit_status").value = prod.status;

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
                div.style.width = "120px";
                div.style.height = "120px";
                div.innerHTML = `
                    <img src="/storage/${img.url}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    <button type="button" class="btn btn-danger btn-sm" style="position: absolute; top: -5px; right: -5px;" onclick="eliminarImagenAntigua(${img.id}, this)">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                previewContainer.appendChild(div);
            });
        }

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
                div.style.width = "120px";
                div.style.height = "120px";
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
            alert("Esta categoría tiene subcategorías. Por favor selecciona una antes de guardar.");
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
                alert("¡Producto actualizado exitosamente!");
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
                    alert("Error de validación. Revisa la consola.");
                } else {
                    alert("Error: " + (res.body.message || "Problema al guardar."));
                }
            }
        })
        .catch(err => {
            console.error("Error en petición:", err);
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
            alert("Error de conexión al servidor.");
        });
    });

}); // FIN DEL DOMContentLoaded