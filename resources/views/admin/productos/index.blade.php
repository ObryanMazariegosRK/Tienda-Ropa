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
                
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label for="price" class="form-label">Precio</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="price" class="form-control currency-input" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label for="offerPrice" class="form-label">Precio Oferta</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-secondary">Q</span>
                            <input type="number" id="offerPrice" class="form-control currency-input" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <div class="form-text">Opcional</div>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="saleType" class="form-label">Tipo de Venta</label>
                        <select id="saleType" class="form-select">
                            <option value="direct">Directa</option>
                            <option value="auction">Subasta</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label for="status" class="form-label">Estado</label>
                        <select id="status" class="form-select">
                            <option value="disabled">Desactivado</option>
                            <option value="available">Disponible</option>
                        </select>
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