@extends('admin.admin')

@section('title', 'Gestión de Categorías')

@section('content')

    <h1 class="mt-4">Módulo de Categorías</h1>
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item active">Organiza el catálogo de tu tienda</li>
    </ol>

    {{-- Resumen rápido: lo llena categorias-controller.js --}}
    <div class="cat-stats mb-4" id="categoryStats">
        <div class="cat-stat"><span class="cat-stat-value" id="statParents">–</span><span class="cat-stat-label">Categorías principales</span></div>
        <div class="cat-stat"><span class="cat-stat-value" id="statChildren">–</span><span class="cat-stat-label">Subcategorías</span></div>
        <div class="cat-stat cat-stat-muted"><span class="cat-stat-value" id="statInactive">–</span><span class="cat-stat-label">Inactivas</span></div>
    </div>

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

                        <input type="hidden" id="description" value="">

                        <div class="mb-3">
                            <label for="parentCategoryId" class="form-label">¿Pertenece a otra categoria?</label>
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
                <div class="card-header bg-primary d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span><i class="fas fa-layer-group me-1"></i> Categorías Registradas</span>
                    <small class="cat-hint">Haz clic en una categoría para ver sus subcategorías</small>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table cat-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Categoría</th>
                                    <th class="text-end">Acciones</th>
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

                        <input type="hidden" id="edit-description" value="">

                        <div class="mb-3">
                            <label for="edit-parentCategoryId" class="form-label">Categoría Padre</label>
                            <select class="form-select" id="edit-parentCategoryId">
                                <!-- Se llena automáticamente con JS -->
                            </select>
                        </div>

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
    
    <script src="/admin/js/categorias-controller.js"></script>
@endpush