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

    //Pastilla de estado y botones de acción (los usan padres y subcategorías)
    function pildoraEstado(cat, principalInactiva = false) {
        if (!cat.isActive) {
            return `<span class="cat-status cat-status-inactive">Inactiva</span>`;
        }
        // Sigue activa, pero no se ve en la tienda porque su categoría principal está inactiva
        if (principalInactiva) {
            return `<span class="cat-status cat-status-hidden" title="Se mostrará de nuevo al activar la categoría principal">Oculta (principal inactiva)</span>`;
        }
        return `<span class="cat-status cat-status-active">Activa</span>`;
    }

    function botonesAcciones(cat) {
        return `
            <div class="cat-actions">
                <button type="button" onclick="event.stopPropagation(); toggleStatus(${cat.id})"
                        class="cat-action-btn cat-action-toggle ${cat.isActive ? 'is-active' : ''}"
                        title="${cat.isActive ? 'Desactivar' : 'Activar'}" aria-label="${cat.isActive ? 'Desactivar' : 'Activar'}">
                    <i class="fas ${cat.isActive ? 'fa-toggle-on' : 'fa-toggle-off'}"></i>
                </button>
                <button type="button" onclick="event.stopPropagation(); openEditModal(${cat.id})"
                        class="cat-action-btn cat-action-edit" title="Editar" aria-label="Editar">
                    <i class="fas fa-pencil-alt"></i>
                </button>
                <button type="button" onclick="event.stopPropagation(); deleteCategory(${cat.id})"
                        class="cat-action-btn cat-action-delete" title="Eliminar" aria-label="Eliminar">
                    <i class="fas fa-trash"></i>
                </button>
            </div>`;
    }

    //Resumen de la parte superior
    function actualizarResumen(categories) {
        const padres = categories.filter(c => c.parentCategoryId === null || c.parentCategoryId === undefined);
        document.getElementById("statParents").textContent = padres.length;
        document.getElementById("statChildren").textContent = categories.length - padres.length;
        document.getElementById("statInactive").textContent = categories.filter(c => !c.isActive).length;
    }

    //Renderizamos la tabla desplegable 
    function renderTable(categories) {
        tableBody.innerHTML = "";
        actualizarResumen(categories);

        const parentCategories = categories.filter(c => c.parentCategoryId === null || c.parentCategoryId === undefined);

        if (parentCategories.length === 0) {
            tableBody.innerHTML = `
                <tr><td colspan="2" class="cat-empty">
                    <i class="fas fa-layer-group"></i>
                    Aún no hay categorías. Crea la primera con el formulario.
                </td></tr>`;
            return;
        }

        parentCategories.forEach(parent => {
            const totalHijas = categories.filter(c => c.parentCategoryId === parent.id).length;

            //Fila de la Categoría Padre
            const parentRow = document.createElement("tr");
            parentRow.className = `cat-row ${parent.isActive ? '' : 'is-inactive'}`;

            parentRow.innerHTML = `
                <td>
                    <div class="cat-main">
                        <span class="cat-chevron"><i class="fas fa-chevron-right"></i></span>
                        <div class="cat-text">
                            <span class="cat-name">${escapeHtml(parent.name)}</span>
                            <span class="cat-meta">
                                ${pildoraEstado(parent)}
                                <span class="cat-count">${totalHijas} ${totalHijas === 1 ? 'subcategoría' : 'subcategorías'}</span>
                            </span>
                        </div>
                    </div>
                </td>
                <td class="text-end">${botonesAcciones(parent)}</td>
            `;

            tableBody.appendChild(parentRow);

            //Fila contenedora oculta para las subcategorías
            const subRow = document.createElement("tr");
            subRow.className = "d-none cat-sub-container";
            subRow.innerHTML = `
                <td colspan="2" class="cat-sub-message">
                    <i class="fas fa-spinner fa-spin me-2"></i> Buscando subcategorías...
                </td>
            `;
            tableBody.appendChild(subRow);

            let yaSeCargaronLasSubcategorias = false;

            parentRow.addEventListener("click", () => {
                const estaOculta = subRow.classList.contains("d-none");

                //La flecha gira con CSS según la clase is-open
                parentRow.classList.toggle("is-open", estaOculta);

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
                                        <li class="cat-sub ${sub.isActive ? '' : 'is-inactive'}">
                                            <div class="cat-sub-text">
                                                <span class="cat-sub-name">${escapeHtml(sub.name)}</span>
                                                ${pildoraEstado(sub, !parent.isActive)}
                                            </div>
                                            ${botonesAcciones(sub)}
                                        </li>
                                    `).join('');

                                    subRow.innerHTML = `
                                        <td colspan="2" class="cat-sub-cell">
                                            <ul class="cat-sub-list">${subListHtml}</ul>
                                        </td>
                                    `;
                                } else {
                                    subRow.innerHTML = `
                                        <td colspan="2" class="cat-sub-message">
                                            <i class="fas fa-info-circle me-2"></i> Esta categoría principal aún no tiene subcategorías.
                                        </td>
                                    `;
                                }

                                yaSeCargaronLasSubcategorias = true;
                            })
                            .catch(err => {
                                console.error("Error al cargar subcategorías:", err);
                                subRow.innerHTML = `<td colspan="2" class="cat-sub-message text-danger">Error de conexión al cargar subcategorías.</td>`;
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

        let mensaje = `¿Deseas ${actionText} "${cat.name}"?`;
        const esPrincipal = cat.parentCategoryId === null || cat.parentCategoryId === undefined;
        if (cat.isActive) {
            mensaje += ' Sus productos dejarán de verse en la tienda mientras esté inactiva.';
        }
        if (cat.isActive && esPrincipal) {
            const hijasActivas = [...memoryBank.values()]
                .filter(c => c.parentCategoryId === cat.id && c.isActive).length;
            if (hijasActivas > 0) {
                mensaje += ` También se ocultarán sus ${hijasActivas} ${hijasActivas === 1 ? 'subcategoría activa' : 'subcategorías activas'}`
                    + ' (conservan su estado y vuelven a verse al reactivarla).';
            }
        }

        const confirmado = await adminConfirm(mensaje, {
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