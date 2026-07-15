@extends('admin.admin')

@section('title', 'Gestión de Categorías')

@section('content')
    <h1 class="mt-4">Módulo de Categorías</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Organiza el catálogo de tu tienda</li>
    </ol>

    <div class="row">
        <!-- Formulario Nueva Categoría -->
        <div class="col-xl-4 col-md-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-plus me-1"></i> Nueva Categoría
                </div>
                <div class="card-body">
                    <form id="categoryForm">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" required placeholder="Ej: Caballeros, Camisas...">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" rows="2" placeholder="Breve descripción..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="parentCategoryId" class="form-label">¿Depende de otra categoría?</label>
                            <select class="form-select" id="parentCategoryId">
                                <option value="" selected>Ninguna (Es una Categoría Padre)</option>
                            </select>
                        </div>
                        <div class="mb-3 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="isActive" checked>
                            <label class="form-check-label" for="isActive">Categoría Activa</label>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-save me-1"></i> Guardar Categoría
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tabla de Categorías -->
        <div class="col-xl-8 col-md-12">
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-table me-1"></i> Categorías Registradas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 10%">ID</th>
                                    <th style="width: 40%">Categoría Padre</th>
                                    <th style="width: 35%">Descripción</th>
                                    <th style="width: 15%">Estado</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar Categoría -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Editar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editCategoryForm">
                        <input type="hidden" id="edit-id"> 
                        
                        <div class="mb-3">
                            <label for="edit-name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit-name" required>
                        </div>

                        <!-- NUEVO: Campo para la descripción -->
                        <div class="mb-3">
                            <label for="edit-description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="edit-description" rows="3"></textarea>
                        </div>

                        <!-- NUEVO: Selector de categoría padre -->
                        <div class="mb-3">
                            <label for="edit-parentCategoryId" class="form-label">Categoría Padre</label>
                            <select class="form-select" id="edit-parentCategoryId">
                                <!-- Se llena automáticamente con JS -->
                            </select>
                        </div>

                        <!-- NUEVO: Switch para estado Activo/Inactivo -->
                        <div class="mb-4 form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="edit-isActive" role="switch">
                            <label class="form-check-label" for="edit-isActive">Categoría Activa</label>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 fw-bold">Actualizar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Tu JavaScript vive en la carpeta public, y lo llamamos así -->
    <script src="/admin/js/categorias-controller.js"></script>
@endpush