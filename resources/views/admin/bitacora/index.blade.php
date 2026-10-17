@extends('admin.admin')

@section('title', 'Bitácora')

@section('content')

    <h1 class="mt-4">Bitácora</h1>
    <ol class="breadcrumb mb-3">
        <li class="breadcrumb-item active">Quién hizo qué y cuándo en la tienda</li>
    </ol>

    <div class="dw-aviso-inmutable mb-4">
        <i class="fas fa-lock"></i>
        <div>
            <strong>Registro de solo lectura.</strong>
            Las entradas no se pueden editar ni borrar, ni siquiera desde la base de datos: se guardan en una base aparte
            (data warehouse) que solo permite agregar registros.
        </div>
    </div>

    <form class="card shadow-sm mb-4" id="filtrosBitacora">
        <div class="card-body row g-2 align-items-end">
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small" for="filtroDesde">Desde</label>
                <input type="date" class="form-control form-control-sm" id="filtroDesde">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small" for="filtroHasta">Hasta</label>
                <input type="date" class="form-control form-control-sm" id="filtroHasta">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small" for="filtroAccion">Acción</label>
                <select class="form-select form-select-sm" id="filtroAccion">
                    <option value="">Todas</option>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small" for="filtroUsuario">Usuario (correo)</label>
                <input type="search" class="form-control form-control-sm" id="filtroUsuario" placeholder="ej. ana@">
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-success flex-fill"><i class="fas fa-filter me-1"></i> Filtrar</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="limpiarFiltros" title="Quitar filtros"><i class="fas fa-xmark"></i></button>
            </div>
        </div>
    </form>

    <div class="card shadow-sm mb-4">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 dw-tabla">
                    <thead>
                        <tr>
                            <th style="min-width: 150px;">Fecha</th>
                            <th>Usuario</th>
                            <th>Acción</th>
                            <th>Descripción</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody id="tablaBitacora">
                        <tr><td colspan="5" class="text-center text-muted py-4">Cargando…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="admin-pagination d-flex justify-content-center gap-1 mb-4" id="paginacionBitacora"></div>

    <!-- Detalle de un registro -->
    <div class="modal fade" id="modalBitacora" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalleBitacora"></div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="/admin/js/bitacora-controller.js"></script>
@endpush
