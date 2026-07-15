@extends('admin.admin')

@section('title', 'Catálogo de Productos')

@section('content')
    <h1 class="mt-4">Catálogo de Productos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Productos</li>
    </ol>

    <!-- PANEL DE FILTROS -->
    <div class="card mb-4 shadow-sm border-0 bg-light">
        <div class="card-body">
            <div class="row align-items-end">
                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="filterCategory" class="form-label fw-bold text-secondary">Categoría Principal</label>
                    <select id="filterCategory" class="form-select border-primary">
                        <option value="">Todas las categorías...</option>
                        <!-- Se llenará con JS -->
                    </select>
                </div>
                <div class="col-md-4 mb-2 mb-md-0">
                    <label for="filterSubcategory" class="form-label fw-bold text-secondary">Subcategoría</label>
                    <select id="filterSubcategory" class="form-select border-primary" disabled>
                        <option value="">Selecciona una categoría primero...</option>
                        <!-- Se llenará con JS -->
                    </select>
                </div>
                <div class="col-md-4 text-md-end">
                    <!-- Botón para ir a crear un producto -->
                    <a href="{{ route('productos.index') }}" class="btn btn-success">
                        <i class="fas fa-plus me-1"></i> Agregar Producto
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- CUADRÍCULA DE PRODUCTOS (GRID) -->
    <div class="row" id="productsGrid">
        
        <!-- Indicador de Carga (Se oculta con JS cuando cargan los datos) -->
        <div class="col-12 text-center py-5" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-2 text-muted">Cargando catálogo...</p>
        </div>

        <!-- 
            EJEMPLO VISUAL DE CÓMO SE INYECTARÁ LA TARJETA CON JAVASCRIPT:
            Tu archivo JS creará un HTML similar a este por cada producto.
        -->
        <!--
        <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm border-0">
                <img src="ruta/a/la/imagen.jpg" class="card-img-top" alt="Nombre del Producto" style="height: 200px; object-fit: cover;">
                <div class="card-body text-center">
                    <h6 class="card-title fw-bold mb-1">Camisa Polo Azul</h6>
                    <p class="text-success fw-bold mb-0">Q 150.00</p>
                </div>
                <div class="card-footer bg-white border-top-0 d-flex justify-content-between pb-3">
                    <button class="btn btn-warning btn-sm px-3 text-dark fw-bold" onclick="editarProducto(1)">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-danger btn-sm px-3" onclick="eliminarProducto(1)">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
        -->
        
    </div>

    <!-- Modal para Editar Producto -->
    <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalEditarProductoLabel"><i class="fas fa-edit"></i> Editar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formEditarProducto">
                    <div class="modal-body">
                        <input type="hidden" id="edit_product_id" name="id">
                        
                       
                        <div class="row mb-3">
                            

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Categoría Principal</label>
                                    <select class="form-select" id="edit_category_id" name="categoryId" required>
                                        <option value="">Cargando categorías...</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Subcategoría (Opcional)</label>
                                    <select class="form-select" id="edit_subcategory_id" name="subcategoryId" disabled>
                                        <option value="">Selecciona una categoría primero</option>
                                    </select>
                                </div>
                            </div>


                            <div class="col-md-6">
                                <label class="form-label">Nombre del Producto</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>

                  
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Precio (Q)</label>
                                <!-- 💡 Agregada la clase currency-input -->
                                <input type="number" step="0.01" class="form-control currency-input" id="edit_price" name="price" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Precio Oferta (Q)</label>
                                <!-- 💡 Agregada la clase currency-input -->
                                <input type="number" step="0.01" class="form-control currency-input" id="edit_offer_price" name="offerPrice">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Tipo de Venta</label>
                                <select class="form-select" id="edit_sale_type" name="saleType">
                                    <option value="direct">Directa</option>
                                    <option value="auction">Subasta</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado</label>
                                <select class="form-select" id="edit_status" name="status">
                                    <option value="disabled">Desactivado</option>
                                    <option value="available">Disponible</option>
                                </select>
                            </div>
                        </div>





                        <div class="mb-3">


                            <label class="form-label">Galería del Producto</label>
    
                            <!-- Aquí vivirán TODAS las imágenes (las viejas y las nuevas) -->
                            <div id="edit_images_preview" class="d-flex flex-wrap gap-2 mb-2"></div>


                            <label class="form-label mt-2">Agregar Nuevas Imágenes</label>
                            <input type="file" class="form-control" id="edit_new_images" name="new_images[]" multiple accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning fw-bold">Actualizar Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>





@endsection

@push('scripts')
    <!-- Tu archivo JS que se encargará de hacer los fetch y pintar las tarjetas -->
    <script src="/admin/js/productos-catalogo.js"></script>
@endpush