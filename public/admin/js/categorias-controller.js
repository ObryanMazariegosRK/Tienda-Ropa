document.addEventListener("DOMContentLoaded", () => {
    const API_URL = "/api/categories"; 
    //Diccionario para guardar los datos de las categorias
    const memoryBank = new Map();
    let listaProductosMemoria = [];
    
    const categoryForm = document.getElementById("categoryForm");
    const parentSelect = document.getElementById("parentCategoryId");
    const tableBody = document.getElementById("categoriesTableBody");
    const autoguardado = habilitarAutoguardado(categoryForm, 'nueva_categoria');
    
    //CARGAR DATOS INICIALES 
    function loadCategories() {
        fetch(API_URL)
            .then(response => response.json())
            .then(res => {
                //solo para prueba XD
                console.log("Respuesta de Laravel al pedir categorías:", res);

                let categoriesArray = [];

                //Validamos en qué formato nos mandó Laravel los datos
                if (Array.isArray(res)) {
                    categoriesArray = res; 
                } else if (res.data && Array.isArray(res.data)) {
                    categoriesArray = res.data; 
                }

                //Guardamos en memoria
                categoriesArray.forEach(cat => memoryBank.set(cat.id, cat));

                //Pintamos la pantalla con los datos extraídos
                populateDropdown(categoriesArray);
                autoguardado.reintentarCampo('parentCategoryId'); 
                renderTable(categoriesArray);
            })
            .catch(err => console.error("Error al cargar categorías:", err));
    }

    //Llemamos el selector de las categorias padre
    function populateDropdown(categories) {
        parentSelect.innerHTML = '<option value="">Ninguna (Es una Categoría Padre)</option>';
        
        //filtro para el selector de categorias
        const parentCategories = categories.filter(c => 
            (c.parentCategoryId === null || c.parentCategoryId === undefined) && 
            c.isActive === true //
        );
        
        parentCategories.forEach(parent => {
            const option = document.createElement("option");
            option.value = parent.id;
            option.textContent = parent.name;
            parentSelect.appendChild(option);
        });
    }

    //Renderizamos la tabla desplegable 
    function renderTable(categories) {
        tableBody.innerHTML = "";

        const parentCategories = categories.filter(c => c.parentCategoryId === null || c.parentCategoryId === undefined);

        parentCategories.forEach(parent => {
            //Fila de la Categoría Padre
            const parentRow = document.createElement("tr");
            parentRow.style.cursor = "pointer";
            parentRow.className = "table-info-hover";

            parentRow.innerHTML = `
                <td>
                    <i class="fas fa-chevron-right me-2 text-primary transition-icon"></i>
                    <strong>${parent.name}</strong>
                </td>
                <td class="text-end">
                    <div class="btn-group shadow-sm" role="group">
                        <button onclick="event.stopPropagation(); toggleStatus(${parent.id})" 
                                class="btn btn-sm ${parent.isActive ? 'btn-success' : 'btn-outline-secondary'}" 
                                title="${parent.isActive ? 'Desactivar' : 'Activar'}">
                            <i class="fas ${parent.isActive ? 'fa-check' : 'fa-ban'}"></i>
                        </button>
                        <button onclick="event.stopPropagation(); openEditModal(${parent.id})" 
                                class="btn btn-sm btn-primary" title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <button onclick="event.stopPropagation(); deleteCategory(${parent.id})" 
                                class="btn btn-sm btn-outline-danger" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            `;

            tableBody.appendChild(parentRow);

            //Fila contenedora oculta para las subcategorías
            const subRow = document.createElement("tr");
            subRow.className = "d-none bg-light animate__animated animate__fadeIn";
            subRow.innerHTML = `
                <td colspan="2" class="p-0 text-center">
                    <div class="p-3 text-muted">
                        <i class="fas fa-spinner fa-spin me-2"></i> Buscando subcategorías...
                    </div>
                </td>
            `;
            tableBody.appendChild(subRow);

            let yaSeCargaronLasSubcategorias = false;

            parentRow.addEventListener("click", () => {
                const estaOculta = subRow.classList.contains("d-none");

                const icon = parentRow.querySelector(".fa-chevron-right, .fa-chevron-down");
                if (icon) {
                    icon.classList.toggle("fa-chevron-right");
                    icon.classList.toggle("fa-chevron-down");
                }

                if (estaOculta) {
                    subRow.classList.remove("d-none");

                    if (!yaSeCargaronLasSubcategorias) {

                        fetch(`/api/categories/parent/${parent.id}`)
                            .then(response => response.json())
                            .then(res => {
                                let subcategoriesArray = [];
                                if (Array.isArray(res)) {
                                    subcategoriesArray = res;
                                } else if (res.data && Array.isArray(res.data)) {
                                    subcategoriesArray = res.data;
                                }

                                subcategoriesArray.forEach(sub => memoryBank.set(sub.id, sub));

                                if (subcategoriesArray.length > 0) {

                                    let subListHtml = subcategoriesArray.map(sub => `
                                        <tr class="table-light">
                                            <td class="ps-5 text-secondary" style="width: 70%"><i class="fas fa-arrow-right me-2 small"></i>${sub.name}</td>
                                            <td class="text-end" style="width: 30%">
                                                <div class="btn-group shadow-sm" role="group">
                                                    <button onclick="event.stopPropagation(); toggleStatus(${sub.id})" 
                                                            class="btn btn-sm ${sub.isActive ? 'btn-success' : 'btn-outline-secondary'}"
                                                            title="${sub.isActive ? 'Desactivar' : 'Activar'}">
                                                        <i class="fas ${sub.isActive ? 'fa-check' : 'fa-ban'}"></i>
                                                    </button>
                                                    <button onclick="event.stopPropagation(); openEditModal(${sub.id})" 
                                                            class="btn btn-sm btn-primary" title="Editar">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </button>
                                                    <button onclick="event.stopPropagation(); deleteCategory(${sub.id})" 
                                                            class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('');

                                    subRow.innerHTML = `
                                        <td colspan="2" class="p-0">
                                            <table class="table table-sm m-0">
                                                <tbody>${subListHtml}</tbody>
                                            </table>
                                        </td>
                                    `;
                                } else {
                                    subRow.innerHTML = `
                                        <td colspan="2" class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle me-2"></i> Esta categoría principal aún no tiene subcategorías.
                                        </td>
                                    `;
                                }

                                yaSeCargaronLasSubcategorias = true;
                            })
                            .catch(err => {
                                console.error("Error al cargar subcategorías:", err);
                                subRow.innerHTML = `<td colspan="2" class="p-3 text-center text-danger">Error de conexión al cargar subcategorías.</td>`;
                            });
                    }
                } else {
                    subRow.classList.add("d-none");
                }
            });
        });
    }



    //Enviamos los datos en formato json al dto
    categoryForm.addEventListener("submit", (e) => {
        e.preventDefault();

        //Armamos el objeto igual que el dto
        const payload = {
            name: document.getElementById("name").value,
            description: document.getElementById("description").value,
            //Si no seleccionó padre, mandamos null explícito
            parentCategoryId: parentSelect.value ? parseInt(parentSelect.value) : null,
            isActive: document.getElementById("isActive").checked
        };



        fetch(API_URL, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": `Bearer ${localStorage.getItem('admin_auth_token')}`
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) {
                throw new Error(data.message || "El servidor rechazó la petición");
            }
            return data;
        })
        .then(res => {
            if (res.id || res.success) {
                adminAlert('¡Categoría guardada con éxito!', { type: 'success' });
                autoguardado.limpiarBorrador();
                categoryForm.reset();
                loadCategories();
            } else {
                adminAlert('No se pudo verificar la creación.', { type: 'error' });
            }
        })
        .catch(err => {
            console.error("Detalle del error:", err);
            adminAlert(err.message, { type: 'error', title: 'No se pudo guardar' });
        });



    });

    //Carga inicial al entrar a la página
    loadCategories();

    

    //ABRIR EL MODAL DE EDICIÓN
    window.openEditModal = function(id) {
        const cat = memoryBank.get(id); 
        if (!cat) return;

        document.getElementById("edit-id").value = cat.id;
        document.getElementById("edit-name").value = cat.name;
        document.getElementById("edit-description").value = cat.description || "";
        document.getElementById("edit-isActive").checked = cat.isActive;

        const editSelect = document.getElementById("edit-parentCategoryId");
        
        // --- NUEVA LÓGICA DE VALIDACIÓN ---
        // Buscamos si en la memoria existe alguna categoría que tenga a ESTA categoría como padre
        const tieneHijas = Array.from(memoryBank.values()).some(c => c.parentCategoryId === cat.id);

        if (tieneHijas) {
            // Si tiene hijas, bloqueamos el selector para proteger la estructura
            editSelect.innerHTML = '<option value="">Bloqueado: Tiene subcategorías asociadas</option>';
            editSelect.disabled = true;
        } else {
            // Si NO tiene hijas, cargamos las categorías padre disponibles normalmente
            editSelect.disabled = false;
            editSelect.innerHTML = '<option value="">Ninguna (Es una Categoría Padre)</option>';
            
            const parentCats = Array.from(memoryBank.values()).filter(c => 
                (c.parentCategoryId === null || c.parentCategoryId === undefined) && c.id !== cat.id
            );
            
            parentCats.forEach(p => {
                const isSelected = cat.parentCategoryId === p.id ? "selected" : "";
                editSelect.innerHTML += `<option value="${p.id}" ${isSelected}>${p.name}</option>`;
            });
        }

        new bootstrap.Modal(document.getElementById("editCategoryModal")).show();
    };






    //BOTÓN DE ACTIVAR/INACTIVAR
    window.toggleStatus = async function(id) {
        const cat = memoryBank.get(id);
        if (!cat) return;

        const actionText = !cat.isActive ? "activar" : "inactivar";
        const confirmado = await adminConfirm(`¿Deseas ${actionText} "${cat.name}"?`, {
            title: 'Cambiar estado',
            confirmText: actionText.charAt(0).toUpperCase() + actionText.slice(1),
            confirmClass: 'btn-warning',
            icon: 'fa-toggle-on'
        });
        if (!confirmado) return;

        const payload = {
            id: cat.id,
            name: cat.name,
            description: cat.description,
            parentCategoryId: cat.parentCategoryId,
            isActive: !cat.isActive
        };

        sendUpdateRequest(id, payload);
    };

    //PARA ELIMINAR UNA CATEGORIA O SUBCATEGORIA
    window.deleteCategory = async function(id) {
        const cat = memoryBank.get(id);
        if (!cat) return;

        const confirmado = await adminConfirm(`¿Eliminar "${cat.name}"? Esta acción no se puede deshacer.`, {
            title: 'Eliminar categoría',
            confirmText: 'Eliminar',
            confirmClass: 'btn-danger',
            icon: 'fa-trash'
        });
        if (!confirmado) return;

        fetch(`${API_URL}/${id}`, {
            method: 'DELETE',
            headers: { 'Authorization': `Bearer ${localStorage.getItem('admin_auth_token')}` }
        })
        .then(async response => {
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'No se pudo eliminar la categoría.');
            adminAlert('Categoría eliminada correctamente.', { type: 'success' });
            loadCategories();
        })
        .catch(err => {
            adminAlert(err.message, { type: 'error', title: 'No se pudo eliminar' });
        });
    };

    //ENVIAR FORMULARIO DE EDICIÓN
    document.getElementById("editCategoryForm").addEventListener("submit", (e) => {
        e.preventDefault();
        
        const id = document.getElementById("edit-id").value;
        const parentVal = document.getElementById("edit-parentCategoryId").value;
        
        const payload = {
            id: parseInt(id),
            name: document.getElementById("edit-name").value,
            description: document.getElementById("edit-description").value,
            parentCategoryId: parentVal ? parseInt(parentVal) : null,
            isActive: document.getElementById("edit-isActive").checked
        };

        sendUpdateRequest(id, payload);
    });

    //MOTOR DE ACTUALIZACIÓN
    function sendUpdateRequest(id, payload) {
        fetch(`${API_URL}/${id}`, {
            method: "PUT",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "Authorization": `Bearer ${localStorage.getItem('admin_auth_token')}`
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.message || "Error de validación");
            }
            return response.json();
        })
        .then(() => {
            const modalElement = document.getElementById("editCategoryModal");
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.hide();

            adminAlert('¡Actualización exitosa!', { type: 'success' });
            loadCategories();
        })
        .catch(err => {
            adminAlert(err.message, { type: 'error', title: 'No se pudo actualizar' });
        });
    }
});