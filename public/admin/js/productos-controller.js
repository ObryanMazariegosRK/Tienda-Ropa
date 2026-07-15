document.addEventListener("DOMContentLoaded", () => {
    const CAT_API_URL = "http://localhost:8000/api/categories";
    const PRODUCT_API_URL = "http://localhost:8000/api/products";
    
    const categorySelect = document.getElementById("categoryId");
    const subcategorySelect = document.getElementById("subcategoryId");
    const imageInput = document.getElementById("images");
    const imagePreview = document.getElementById("imagePreview");
    const productForm = document.getElementById("productForm");
    const MAX_IMAGES = 5;

    //Aquí guardaremos las fotos acumuladas
    let selectedFiles = []; 

    //CARGAR CATEGORÍAS PADRE
    fetch(CAT_API_URL)
        .then(res => res.json())
        .then(data => {
            const categories = Array.isArray(data) ? data : (data.data || []);
            categorySelect.innerHTML = '<option value="">Seleccione una categoría</option>';
            
            //Filtramos solo las Activas y que NO tengan padre (Categorías Principales)
            categories.filter(c => c.isActive && !c.parentCategoryId).forEach(c => {
                categorySelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });
        });

    //BUSCAR SUBCATEGORÍAS CUANDO SE ELIGE UN PADRE
    categorySelect.addEventListener("change", (e) => {
        const parentId = e.target.value;
        
        if (!parentId) {
            subcategorySelect.innerHTML = '<option value="">Selecciona una categoría primero</option>';
            subcategorySelect.disabled = true;
            return;
        }

        subcategorySelect.innerHTML = '<option value="">Cargando...</option>';
        subcategorySelect.disabled = true;

        //Usamos tu endpoint de buscar por padre
        fetch(`${CAT_API_URL}/parent/${parentId}`)
            .then(res => res.json())
            .then(data => {
                const subcategories = Array.isArray(data) ? data : (data.data || []);
                
                if (subcategories.length > 0) {
                    subcategorySelect.innerHTML = '<option value="">Ninguna (Opcional)</option>';
                    subcategories.filter(c => c.isActive).forEach(sub => {
                        subcategorySelect.innerHTML += `<option value="${sub.id}">${sub.name}</option>`;
                    });
                    subcategorySelect.disabled = false;
                } else {
                    subcategorySelect.innerHTML = '<option value="">Sin subcategorías disponibles</option>';
                }
            })
            .catch(err => {
                console.error("Error cargando subcategorías", err);
                subcategorySelect.innerHTML = '<option value="">Error de carga</option>';
            });
    });

    //LÓGICA DE ACUMULAR IMÁGENES
    imageInput.addEventListener("change", (e) => {
        const newFiles = Array.from(e.target.files);
        
        //Verificamos si la suma de las fotos que ya están + las nuevas supera el límite de 5
        if (selectedFiles.length + newFiles.length > MAX_IMAGES) {
            
            //Bloqueamos la acción y le avisamos al usuario el motivo exacto
            alert(`Error: No puedes superar el límite de ${MAX_IMAGES} imágenes.\n\nActualmente tienes ${selectedFiles.length} seleccionadas y estás intentando agregar ${newFiles.length} más.`);
            
        } else {
            
            //Si la suma está dentro del límite (5 o menos), las agregamos todas
            selectedFiles = selectedFiles.concat(newFiles); 
            
            //Solo actualizamos la vista previa si realmente agregamos imágenes
            updateImagePreview();
        }
        
        //Limpiamos el input para que HTML nos deje seleccionar la misma foto si queremos
        imageInput.value = ""; 
    });

    //DIBUJAR PREVISUALIZACIÓN DE IMÁGENES
    function updateImagePreview() {
        imagePreview.innerHTML = "";
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (event) => {
                const div = document.createElement("div");
                div.style.position = "relative";
                div.style.width = "120px";
                div.style.height = "120px";
                
                div.innerHTML = `
                    <img src="${event.target.result}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd;">
                    <button type="button" class="btn btn-danger btn-sm" 
                            style="position: absolute; top: -5px; right: -5px; border-radius: 50%; padding: 2px 6px;"
                            onclick="removeImage(${index})">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                imagePreview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    //Función global para eliminar una imagen de la memoria
    window.removeImage = function(index) {
        selectedFiles.splice(index, 1); //Quitamos la imagen del arreglo
        updateImagePreview(); //Volvemos a dibujar
    };

    //ENVIAR FORMULARIO
    productForm.addEventListener("submit", (e) => {
        e.preventDefault();

        const formData = new FormData();
        
        //Si seleccionó subcategoría, enviamos esa. Si no, enviamos el padre.
        const parentCatId = document.getElementById("categoryId").value;
        const subCatId = document.getElementById("subcategoryId").value;
        const finalCategoryId = subCatId ? subCatId : parentCatId;
        
        formData.append("categoryId", finalCategoryId);
        formData.append("name", document.getElementById("name").value);
        formData.append("description", document.getElementById("description").value);
        formData.append("price", document.getElementById("price").value);
        
        const offer = document.getElementById("offerPrice").value;
        if(offer) formData.append("offerPrice", offer);
        
        formData.append("saleType", document.getElementById("saleType").value);
        formData.append("status", document.getElementById("status").value);

        //Agregamos las imágenes desde nuestra memoria, no desde el input
        selectedFiles.forEach((file, index) => {
            formData.append("images[]", file);
        });

        console.log("Archivos listos para enviar:", selectedFiles);
        for (let pair of formData.entries()) {
            console.log(pair[0] + ', ' + pair[1]); 
        }

        //Enviamos la petición
        // Enviamos la petición
        fetch(PRODUCT_API_URL, {
            method: "POST",
            body: formData,
            headers: { 
                "Accept": "application/json" 
            }
        })
        .then(async res => {
            // 💡 TRUCO: Leemos primero como texto plano para evitar que se rompa si Laravel manda HTML (error 500)
            const respuestaTexto = await res.text();
            let data;

            try {
                data = JSON.parse(respuestaTexto);
            } catch (e) {
                console.error("El servidor no devolvió un JSON válido. Respuesta recibida:", respuestaTexto);
                throw new Error("El servidor devolvió un error interno (HTML). Revisa la consola.");
            }

            // Si la respuesta no es exitosa (400, 422, 500, etc.)
            if (!res.ok) {
                // 1. Si son errores de validación estructurados de Laravel (data.errors)
                if (data.errors) {
                    const mensajesError = Object.values(data.errors).flat().join("\n");
                    throw new Error("Errores de validación:\n" + mensajesError);
                }
                
                // 2. Si es una excepción de tu catch de Laravel (data.error)
                if (data.error) {
                    throw new Error(data.error); 
                }

                // 3. Si es un mensaje de error genérico
                throw new Error(data.message || "Error desconocido del servidor");
            }
            
            return data;
        })
        .then(res => {
            if (res.id || res.success) {
                alert("¡Producto creado con éxito!");
                productForm.reset();
                selectedFiles = [];
                updateImagePreview(); 
            } else {
                alert("Error extraño: " + (res.message || "No se pudo crear el producto"));
            }
        })
        .catch(err => {
            console.error("Error capturado en JS:", err);
            alert(err.message); 
        });
    });


    // ==========================================
    // AUTO-FORMATO DE MONEDA A 2 DECIMALES
    // ==========================================
    const currencyInputs = document.querySelectorAll('.currency-input');
    
    currencyInputs.forEach(input => {
        // El evento 'blur' ocurre cuando el usuario sale del campo de texto
        input.addEventListener('blur', function() {
            if (this.value) {
                // Convertimos el valor a decimal y lo forzamos a 2 posiciones
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});


