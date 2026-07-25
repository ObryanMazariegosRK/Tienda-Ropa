
Banners index v2.blade · PHP
@extends('admin.admin')
 
@section('title', 'Banners - Admin')
 
@section('content')
    <style>
        .group-card {
            cursor: pointer;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .group-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(0,0,0,0.1);
        }
        .group-card.active-group {
            border: 3px solid #198754;
            
        }
        .group-thumb {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background-color: #eee;
        }
        .group-card .badge-active {
            position: absolute;
            top: 10px;
            right: 10px;
        }
        #bannersGrid {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        #bannersGrid > div { flex: 0 0 260px; }
 
        .media-thumb-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 6px;
            overflow: hidden;
            border: 1px solid #ddd;
        }
        .media-thumb-item img, .media-thumb-item video {
            width: 100%; height: 100%; object-fit: cover;
        }
        .media-thumb-remove {
            position: absolute;
            top: -6px;
            right: -6px;
            border-radius: 50%;
            padding: 2px 7px;
        }
    </style>
 
    <h1 class="mt-4">Banners de la Tienda</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Banners</li>
    </ol>
 
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-1"></i>
        Solo un grupo puede estar <strong>activo</strong> a la vez — es el que se muestra en el carrusel principal de la tienda.
    </div>
 
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-plus me-1"></i> Crear nuevo grupo de banner
        </div>
        <div class="card-body">
            <form id="createGroupForm">
                <div class="row">
                    <div class="col-md-5 mb-3">
                        <label class="form-label">Nombre del grupo</label>
                        <input type="text" id="groupName" class="form-control" placeholder="Ej. Primavera, Invierno..." required>
                    </div>
                    <div class="col-md-7 mb-3">
                        <label class="form-label">Imágenes (hasta 5) o videos — no mezclar tipos</label>
                        <input type="file" id="groupMedia" class="form-control" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" multiple>
                    </div>
                </div>
                <div id="groupPreviewList" class="d-flex flex-wrap gap-2 mb-3"></div>
                <div id="createGroupError" class="alert alert-danger d-none"></div>
                <button type="submit" id="createGroupBtn" class="btn btn-success">
                    <i class="fas fa-upload me-2"></i> Crear grupo
                </button>
                <span id="groupsLimitNote" class="text-muted ms-2"></span>
            </form>
        </div>
    </div>
 
    <div id="bannersGrid">
        <p class="text-muted">Cargando...</p>
    </div>
 
    <!-- Modal de edición de grupo -->
    <div class="modal fade" id="modalEditGroup" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>


                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del grupo</label>
                        <input type="text" id="editGroupName" class="form-control">
                    </div>

                    <hr>

                    <label class="form-label fw-bold">Archivos del grupo</label>
                    <div id="editMediaList" class="d-flex flex-wrap gap-2 mb-2"></div>

                    <input type="file" id="addMoreMediaInput" class="form-control mb-2" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm" multiple>

                    <div id="editGroupError" class="alert alert-danger d-none mt-2"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-outline-danger" id="deleteGroupBtn">
                        <i class="fas fa-trash me-1"></i> Eliminar grupo completo
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="saveGroupChangesBtn">
                            <i class="fas fa-save me-1"></i> Guardar cambios
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
 
@push('scripts')
    <script src="/admin/js/banners-controller.js"></script>
@endpush
 
