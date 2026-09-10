document.addEventListener("DOMContentLoaded", () => {
    const CAT_API_URL = "/api/categories";
    const PRODUCT_API_URL = "/api/products";
    
    const categorySelect = document.getElementById("categoryId");
    const subcategorySelect = document.getElementById("subcategoryId");
    const imageInput = document.getElementById("images");
    const imagePreview = document.getElementById("imagePreview");
    const productForm = document.getElementById("productForm");
    const MAX_IMAGES = 5;
    const autoguardado = habilitarAutoguardado(productForm, 'nuevo_producto', {
        excluir: ['images']
    });

    //Aquí guardaremos las fotos acumuladas
    let selectedFiles = []; 

    //CARGAR CATEGORÍAS PADRE
    //CARGAR CATEGORÍAS PADRE
    fetch(CAT_API_URL)
        .then(res => res.json())
        .then(data => {
            const categories = Array.isArray(data) ? data : (data.data || []);
            categorySelect.innerHTML = '<option value="">Seleccione una categoría</option>';
            
            categories.filter(c => c.isActive && !c.parentCategoryId).forEach(c => {
                categorySelect.innerHTML += `<option value="${c.id}">${c.name}</option>`;
            });

            autoguardado.reintentarCampo('categoryId');
            if (categorySelect.value) {
                categorySelect.dispatchEvent(new Event('change'));
            }
        });

    //BUSCAR SUBCATEGORÍAS CUANDO SE ELIGE UN PADRE
    categorySelect.addEventListener("change", (e) => {
        const parentId = e.target.value;
        
        if (!parentId) {
            subcategorySelect.innerHTML = '<option value="">Selecciona una categoría primero</option>';
            subcategorySelect.disabled = true;
            subcategorySelect.required = false;
            return;
        }

        subcategorySelect.innerHTML = '<option value="">Cargando...</option>';
        subcategorySelect.disabled = true;

        fetch(`${CAT_API_URL}/parent/${parentId}`)
            .then(res => res.json())
            .then(data => {
                const subcategories = Array.isArray(data) ? data : (data.data || []);
                
                if (subcategories.length > 0) {
                    subcategorySelect.innerHTML = '<option value="">Selecciona una subcategoría</option>';
                    subcategories.filter(c => c.isActive).forEach(sub => {
                        subcategorySelect.innerHTML += `<option value="${sub.id}">${sub.name}</option>`;
                    });
                    subcategorySelect.disabled = false;
                    subcategorySelect.required = true;

                    autoguardado.reintentarCampo('subcategoryId');
                } else {
                    subcategorySelect.innerHTML = '<option value="">Sin subcategorías disponibles</option>';
                    subcategorySelect.disabled = true;
                    subcategorySelect.required = false;
                }
            })
            .catch(err => {
                console.error("Error cargando subcategorías", err);
                subcategorySelect.innerHTML = '<option value="">Error de carga</option>';
                subcategorySelect.required = false;
            });
    });

    //LÓGICA DE ACUMULAR IMÁGENES
    imageInput.addEventListener("change", (e) => {
        const newFiles = Array.from(e.target.files);
        
        //Verificamos si la suma de las fotos que ya están + las nuevas supera el límite de 5
        if (selectedFiles.length + newFiles.length > MAX_IMAGES) {
            
            //Bloqueamos la acción y le avisamos al usuario el motivo exacto
            adminAlert(`No puedes superar el límite de ${MAX_IMAGES} imágenes. Actualmente tienes ${selectedFiles.length} seleccionadas y estás intentando agregar ${newFiles.length} más.`, { type: 'error', title: 'Demasiadas imágenes' });
            
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

        // Bloqueamos el botón para evitar doble envío mientras guarda

        const btnGuardar = productForm.querySelector("button[type='submit']");
        if (btnGuardar.disabled) return;
        


        // Si el padre tiene subcategorías cargadas pero no se eligió ninguna, detenemos el guardado
        const tieneSubcategoriasDisponibles = subcategorySelect.options.length > 1 && !subcategorySelect.disabled;
        if (tieneSubcategoriasDisponibles && !subcategorySelect.value) {
            adminAlert("Esta categoría tiene subcategorías. Por favor selecciona una antes de crear el producto.", { type: 'error', title: 'Falta un dato' });
            return;
        }

        const textoOriginal = btnGuardar.innerHTML;
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        
        const formData = new FormData();

        //Si seleccionó subcategoría, enviamos esa. Si no, enviamos el padre.
        const parentCatId = document.getElementById("categoryId").value;
        const subCatId = document.getElementById("subcategoryId").value;
        const finalCategoryId = subCatId ? subCatId : parentCatId;

        formData.append("categoryId", finalCategoryId);
        formData.append("name", document.getElementById("name").value);
        formData.append("description", document.getElementById("description").value);
        formData.append("saleType", document.getElementById("saleType").value);
        formData.append("status", document.getElementById("status").value);

        
        const esSubasta = document.getElementById("saleType").value === 'auction';

        if (esSubasta) {
            // El campo "price" del backend representa el precio BASE cuando es subasta
            formData.append("cost", document.getElementById("cost").value);
            formData.append("price", document.getElementById("auctionStartingPrice").value);
            formData.append("auctionDurationAmount", document.getElementById("auctionDurationAmount").value);
            formData.append("auctionDurationUnit", document.getElementById("auctionDurationUnit").value);

            const incremento = document.getElementById("auctionMinIncrement").value;
            if (incremento) formData.append("auctionMinIncrement", incremento);

            // No enviamos offerPrice en absoluto para subastas
        } else {
            formData.append("price", document.getElementById("price").value);
            const offer = document.getElementById("offerPrice").value;
            if (offer) formData.append("offerPrice", offer);
            formData.append("cost", document.getElementById("cost").value);
        }

        //Agregamos las imágenes desde nuestra memoria, no desde el input
        selectedFiles.forEach((file, index) => {
            formData.append("images[]", file);
        });

        console.log("Archivos listos para enviar:", selectedFiles);
        for (let pair of formData.entries()) {
            console.log(pair[0] + ', ' + pair[1]);
        }


        //Enviamos la petición
        fetch(PRODUCT_API_URL, {
            method: "POST",
            body: formData,
            headers: {
                "Accept": "application/json",
                "Authorization": `Bearer ${localStorage.getItem('admin_auth_token')}`
            }
        })
        .then(async res => {
            const respuestaTexto = await res.text();
            let data;

            try {
                data = JSON.parse(respuestaTexto);
            } catch (e) {
                console.error("El servidor no devolvió un JSON válido. Respuesta recibida:", respuestaTexto);
                throw new Error("El servidor devolvió un error interno (HTML). Revisa la consola.");
            }

            if (!res.ok) {
                if (data.errors) {
                    const mensajesError = Object.values(data.errors).flat().join("\n");
                    throw new Error("Errores de validación:\n" + mensajesError);
                }
                if (data.error) {
                    throw new Error(data.error);
                }
                throw new Error(data.message || "Error desconocido del servidor");
            }

            return data;
        })
        .then(res => {
            if (res.id || res.success) {
                adminAlert("¡Producto creado con éxito!", { type: 'success' });
                autoguardado.limpiarBorrador();
                productForm.reset();
                selectedFiles = [];
                updateImagePreview();
                actualizarCamposPorTipoVenta(); //restaura la sección visible correcta tras el reset()
            } else {
                adminAlert(res.message || "No se pudo crear el producto", { type: 'error' });
            }
        })
        .catch(err => {
            console.error("Error capturado en JS:", err);
            adminAlert(err.message, { type: 'error', title: 'No se pudo crear el producto' });
        })
        .finally(() => {
            btnGuardar.disabled = false;
            btnGuardar.innerHTML = textoOriginal;
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

    //Toggles para ocultar y mostrar las diferentes secciones al crear un producto
    const saleTypeSelect = document.getElementById("saleType");
    const directSaleFields = document.getElementById("directSaleFields");
    const auctionFields = document.getElementById("auctionFields");

    function actualizarCamposPorTipoVenta() {
        const esSubasta = saleTypeSelect.value === 'auction';
        directSaleFields.classList.toggle('d-none', esSubasta);
        auctionFields.classList.toggle('d-none', !esSubasta);

        // Los campos ocultos no deben ser "required" ni enviarse con datos viejos
        document.getElementById("price").required = !esSubasta;
        document.getElementById("auctionStartingPrice").required = esSubasta;
        document.getElementById("auctionDurationAmount").required = esSubasta;
    }

    saleTypeSelect.addEventListener("change", actualizarCamposPorTipoVenta);
    actualizarCamposPorTipoVenta(); // estado inicial al cargar la página

    ['cost', 'price', 'offerPrice', 'auctionStartingPrice'].forEach(id => {
        document.getElementById(id).addEventListener('input', verificarMargenGananciaDebounced);
    });

    saleTypeSelect.addEventListener('change', verificarMargenGanancia);

    ['cost', 'price', 'offerPrice', 'auctionStartingPrice'].forEach(id => {
        document.getElementById(id).addEventListener('input', verificarMargenGananciaDebounced);
    });



    //Por si el costo es menor o igual al precio base
    function verificarMargenGanancia() {
        const costo = parseFloat(document.getElementById('cost').value) || 0;
        const esSubasta = saleTypeSelect.value === 'auction';

        const warningDirect = document.getElementById('marginWarningDirect');
        const warningAuction = document.getElementById('marginWarningAuction');
        const warningOffer = document.getElementById('marginWarningOffer');

        if (esSubasta) {
            warningDirect.style.display = 'none';
            warningOffer.style.display = 'none';

            const precioBase = parseFloat(document.getElementById('auctionStartingPrice').value) || 0;
            if (costo > 0 && precioBase > 0 && precioBase <= costo) {
                document.getElementById('marginWarningAuctionText').textContent = precioBase < costo
                    ? `El precio base (Q${precioBase.toFixed(2)}) es menor al costo (Q${costo.toFixed(2)}). Si nadie puja más alto, venderías con pérdida.`
                    : `El precio base es igual al costo (Q${costo.toFixed(2)}). Sin pujas, no habría ninguna ganancia.`;
                warningAuction.style.display = 'block';
            } else {
                warningAuction.style.display = 'none';
            }
        } else {
            warningAuction.style.display = 'none';

            const precio = parseFloat(document.getElementById('price').value) || 0;
            if (costo > 0 && precio > 0 && precio <= costo) {
                document.getElementById('marginWarningDirectText').textContent = precio < costo
                    ? `El precio de venta (Q${precio.toFixed(2)}) es menor al costo (Q${costo.toFixed(2)}). Vas a vender con pérdida.`
                    : `El precio de venta es igual al costo (Q${costo.toFixed(2)}). No habría ninguna ganancia.`;
                warningDirect.style.display = 'block';
            } else {
                warningDirect.style.display = 'none';
            }

            const oferta = parseFloat(document.getElementById('offerPrice').value) || 0;
            if (costo > 0 && oferta > 0 && oferta <= costo) {
                document.getElementById('marginWarningOfferText').textContent = oferta < costo
                    ? `El precio de oferta (Q${oferta.toFixed(2)}) es menor al costo (Q${costo.toFixed(2)}). Venderías con pérdida si aplica la oferta.`
                    : `El precio de oferta es igual al costo (Q${costo.toFixed(2)}). No habría ninguna ganancia con la oferta.`;
                warningOffer.style.display = 'block';
            } else {
                warningOffer.style.display = 'none';
            }
        }
    }

    let margenDebounceTimer;
    function verificarMargenGananciaDebounced() {
        clearTimeout(margenDebounceTimer);
        margenDebounceTimer = setTimeout(verificarMargenGanancia, 800);
    }



});


