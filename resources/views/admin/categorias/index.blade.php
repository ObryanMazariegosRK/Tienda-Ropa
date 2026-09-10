@extends('admin.admin')

@section('title', 'Gestión de Categorías')

@section('content')

    <style>
    @media (max-width: 767.98px) {
        /* Le gana ancho extra a la tabla sin tocar el layout compartido del admin */
        .table-responsive {
            margin: 0 -0.6rem;
            width: calc(100% + 1.2rem);
        }
        .card-body {
            padding: 0.9rem 0.7rem;
        }

        /* Ocultamos Descripción (se ve completa al Editar) para que ID / Categoría / Estado quepan sin scroll */
        #categoriesTableBody td:nth-child(3),
        .table thead th:nth-child(3) {
            display: none;
        }

        .table {
            font-size: 0.82rem;
        }
        .table td,
        .table th {
            padding: 0.6rem 0.4rem;
        }
    }
    </style>
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
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-table me-1"></i> Categorías Registradas
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70%">Categoría Padre</th>
                                    <th style="width: 30%">Estado</th>
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