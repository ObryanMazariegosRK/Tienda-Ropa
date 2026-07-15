document.addEventListener("DOMContentLoaded", () => {
    const API_URL = "http://localhost:8000/api/categories"; 
    //Diccionario para guardar los datos de las categorias
    const memoryBank = new Map();
    let listaProductosMemoria = [];
    
    const categoryForm = document.getElementById("categoryForm");
    const parentSelect = document.getElementById("parentCategoryId");
    const tableBody = document.getElementById("categoriesTableBody");

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
        
        //Filtramos las principales para pintar la tabla base
        const parentCategories = categories.filter(c => c.parentCategoryId === null || c.parentCategoryId === undefined);

        parentCategories.forEach(parent => {
            //Fila de la Categoría Padre
            const parentRow = document.createElement("tr");
            parentRow.style.cursor = "pointer"; //La hacemos clickeable
            parentRow.className = "table-info-hover";
            
            parentRow.innerHTML = `
                <td>${parent.id}</td>
                <td>
                    <i class="fas fa-chevron-right me-2 text-primary transition-icon"></i>
                    <strong>${parent.name}</strong> 
                </td>
                <td>${parent.description || '<span class="text-muted">Sin descripción</span>'}</td>
                
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
                    </div>
                </td>
            `;

            tableBody.appendChild(parentRow);

            //Fila contenedora oculta para las subcategorías 
            const subRow = document.createElement("tr");
            subRow.className = "d-none bg-light animate__animated animate__fadeIn";
            subRow.innerHTML = `
                <td colspan="4" class="p-0 text-center">
                    <div class="p-3 text-muted">
                        <i class="fas fa-spinner fa-spin me-2"></i> Buscando subcategorías...
                    </div>
                </td>
            `;
            tableBody.appendChild(subRow);

            //Variable bandera para saber si ya pedimos los datos al servidor o no
            let yaSeCargaronLasSubcategorias = false; 

            //Al momento de hacer clicl en una de las categorias
            parentRow.addEventListener("click", () => {
                const estaOculta = subRow.classList.contains("d-none");
                
                //Animación de la flechita
                const icon = parentRow.querySelector(".fa-chevron-right, .fa-chevron-down");
                if(icon) {
                    icon.classList.toggle("fa-chevron-right");
                    icon.classList.toggle("fa-chevron-down");
                }

                if (estaOculta) {
                    //
                    subRow.classList.remove("d-none"); 
                    
                    //Si es la primera vez que hacemos clic, llamamos a tu endpoint
                    if (!yaSeCargaronLasSubcategorias) {
                        
                        //usamos el endPoint para traer las subcategorias de la categoria
                        fetch(`http://localhost:8000/api/categories/parent/${parent.id}`)
                            .then(response => response.json())
                            .then(res => {
                                // 1. Acomodamos los datos sin importar cómo vengan de Laravel
                                let subcategoriesArray = [];
                                if (Array.isArray(res)) {
                                    subcategoriesArray = res;

                                } else if (res.data && Array.isArray(res.data)) {
                                    subcategoriesArray = res.data;

                                }
                                //almacenamos la informacion de la subcategoria en el diccionario
                                subcategoriesArray.forEach(sub => memoryBank.set(sub.id, sub));

                                //Validamos si la lista tiene elementos
                                if (subcategoriesArray.length > 0) {
                                    
                                    // Construimos el HTML solo con los datos que mandó el servidor
                                    // Construimos el HTML solo con los datos que mandó el servidor
                                    let subListHtml = subcategoriesArray.map(sub => `
                                        <tr class="table-light">
                                            <td class="text-muted ps-4" style="width: 10%">${sub.id}</td>
                                            <td class="ps-5 text-secondary" style="width: 40%"><i class="fas fa-arrow-right me-2 small"></i>${sub.name}</td>
                                            <td class="text-muted small" style="width: 30%">${sub.description || ''}</td>
                                            
                                            <td class="text-end" style="width: 20%">
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
                                                </div>
                                            </td>
                                        </tr>
                                    `).join('');

                                    // Remplazamos el "Cargando..." por la tabla real
                                    subRow.innerHTML = `
                                        <td colspan="4" class="p-0">
                                            <table class="table table-sm m-0">
                                                <tbody>${subListHtml}</tbody>
                                            </table>
                                        </td>
                                    `;
                                } else {
                                    //Si tu endpoint dice que está vacío (arreglo length = 0)
                                    subRow.innerHTML = `
                                        <td colspan="4" class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle me-2"></i> Esta categoría principal aún no tiene subcategorías.
                                        </td>
                                    `;
                                }
                                
                                //Marcamos como "cargado"
                                yaSeCargaronLasSubcategorias = true; 
                            })
                            .catch(err => {
                                console.error("Error al cargar subcategorías:", err);
                                subRow.innerHTML = `<td colspan="4" class="p-3 text-center text-danger">Error de conexión al cargar subcategorías.</td>`;
                            });
                    }
                } else {
                    //Si ya estaba abierta, simplemente la ocultamos
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
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        })
        .then(response => {
            //Comprobamos si el servidor respondió con un código 200 o 201 (Creado)
            if (!response.ok) {
                throw new Error("El servidor rechazó la petición");
            }
            return response.json();
        })
        .then(res => {
            //Validamos si nos devolvio el registro con su nuevo ID
            if (res.id || res.success) {
                alert("¡Categoría guardada con éxito!");
                categoryForm.reset(); //Limpia los campos
                loadCategories();     //Recarga la tabla de inmediato
            }else{
                alert("Error: No se pudo verificar la creación.");
            }
        })
        .catch(err => {
            console.error("Detalle del error:", err);
            alert("Ocurrió un error al enviar los datos. Revisa la consola.");
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
    window.toggleStatus = function(id) {
        const cat = memoryBank.get(id);
        if (!cat) return;

        const actionText = !cat.isActive ? "ACTIVAR" : "INACTIVAR";
        if (!confirm(`¿Deseas ${actionText} esta categoría?`)) return;

        const payload = {
            id: cat.id,
            name: cat.name,
            description: cat.description,
            parentCategoryId: cat.parentCategoryId,
            isActive: !cat.isActive 
        };

        sendUpdateRequest(id, payload);
    };

    // C. ENVIAR FORMULARIO DE EDICIÓN
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
                "Accept": "application/json"
            },
            body: JSON.stringify(payload)
        })
        .then(async response => {
            if (!response.ok) {
                // 💡 Extraemos el JSON con los errores de Laravel
                const errorData = await response.json();
                console.error("⛔ Errores de Laravel:", errorData);
                throw new Error(errorData.message || "Error de validación");
            }
            return response.json();
        })
        .then(() => {
            // Ocultamos el modal
            const modalElement = document.getElementById("editCategoryModal");
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) modalInstance.hide();
            
            alert("¡Actualización exitosa!");
            loadCategories(); 
        })
        .catch(err => {
            console.error("Error capturado en el catch:", err);
            alert("Ocurrió un error: " + err.message + "\nRevisa la consola (F12) para más detalles.");
        });
    }
});