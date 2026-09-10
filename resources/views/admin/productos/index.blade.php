@extends('admin.admin')

@section('title', 'Crear Producto - Admin')

@section('content')
    <!-- Estilos específicos para la previsualización de imágenes de esta vista -->
    <style>
        .preview-item { position: relative; width: 120px; height: 120px; }
        .preview-item img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #ddd; }
        .btn-remove-image { position: absolute; top: -5px; right: -5px; border-radius: 50%; padding: 2px 6px; }
    </style>

    <h1 class="mt-4">Nuevo Producto</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Crear Producto</li>
    </ol>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-box-open me-1"></i> Detalles del Producto
        </div>
        <div class="card-body">
            <form id="productForm">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="name" class="form-label">Nombre del Producto</label>
                        <input type="text" id="name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="categoryId" class="form-label">Categoría Principal</label>
                        <select id="categoryId" class="form-select" required>
                            <option value="">Cargando categorías...</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="subcategoryId" class="form-label">Subcategoría (Opcional)</label>
                        <select id="subcategoryId" class="form-select" disabled>
                            <option value="">Selecciona una categoría primero</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea id="description" class="form-control" rows="3" required></textarea>
                </div>
               

                <!-- Tipo de Venta y Estado: siempre visibles, definen qué sección de abajo se muestra -->
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="saleType" class="form-label">Tipo de Venta</label>
                        <select id="saleType" class="form-select" required>
                            <option value="direct">Venta Directa</option>
                            <option value="auction">Subasta</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" class="form-select">
                            <option value="disabled">Desactivado</option>
                            <option value="available" selected>Disponible</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="cost" class="form-label">Costo del producto <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="cost" class="form-control currency-input" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    <div class="form-text">Cuánto le costó adquirir esta pieza (uso interno, no visible al cliente).</div>
                </div> 

                <!-- Sección: Venta Directa -->
                <div id="directSaleFields" class="row border rounded p-3 mb-3 bg-light">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="price" class="form-control currency-input" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="offerPrice" class="form-label">Precio Oferta</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="offerPrice" class="form-control currency-input" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-text">Opcional (al ingresar un precio, aparecerá en la sección de ofertas)</div>
                    </div>

                    <div class="col-12" id="marginWarningDirect" style="display:none;">
                        <div class="alert alert-warning py-2 px-3 mb-0">
                            <i class="fas fa-triangle-exclamation me-1"></i> <span id="marginWarningDirectText"></span>
                        </div>
                    </div>

                    <div class="col-12" id="marginWarningOffer" style="display:none;">
                        <div class="alert alert-warning py-2 px-3 mb-0">
                            <i class="fas fa-triangle-exclamation me-1"></i> <span id="marginWarningOfferText"></span>
                        </div>
                    </div>
                </div>

                <!-- Sección: Subasta -->
                <div id="auctionFields" class="row border rounded p-3 mb-3 bg-light d-none">
                    
             
                    
                    <div class="col-md-4 mb-3">
                        <label for="auctionStartingPrice" class="form-label">Precio Base</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="auctionStartingPrice" class="form-control currency-input" step="0.01" min="0" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="auctionDurationAmount" class="form-label">Duración</label>
                        <input type="number" id="auctionDurationAmount" class="form-control" min="1" placeholder="Ej. 3">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="auctionDurationUnit" class="form-label">Unidad</label>
                        <select id="auctionDurationUnit" class="form-select">
                            <option value="hours">Horas</option>
                            <option value="days" selected>Días</option>
                            <option value="weeks">Semanas</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="auctionMinIncrement" class="form-label">Incremento mínimo por puja</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="auctionMinIncrement" class="form-control currency-input" step="0.01" min="0" placeholder="10.00">
                        </div>
                        <div class="form-text">Si lo dejas vacío, se usa Q10.00 por defecto.</div>
                    </div>

                    <div class="col-12" id="marginWarningAuction" style="display:none;">
                        <div class="alert alert-warning py-2 px-3 mb-0">
                            <i class="fas fa-triangle-exclamation me-1"></i> <span id="marginWarningAuctionText"></span>
                        </div>
                    </div>
                    <div class="col-12" id="marginWarningOffer" style="display:none;">
                        <div class="alert alert-warning py-2 px-3 mb-0">
                            <i class="fas fa-triangle-exclamation me-1"></i> <span id="marginWarningOfferText"></span>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="images" class="form-label fw-bold">Imágenes del Producto</label>
                    <input type="file" id="images" class="form-control" multiple accept="image/*">
                    <div class="form-text">
                        <i class="fas fa-info-circle me-1"></i>Puedes seleccionar varias imágenes a la vez, y seguir agregando más.
                    </div>
                    <div id="imagePreview" class="d-flex gap-3 flex-wrap mt-3"></div>
                </div>

                <button type="submit" class="btn btn-success btn-lg w-100">
                    <i class="fas fa-save me-2"></i> Guardar Producto
                </button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="/admin/js/productos-controller.js"></script>
@endpush