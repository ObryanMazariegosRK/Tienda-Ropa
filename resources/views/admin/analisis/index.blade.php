@extends('admin.admin')

@section('title', 'Análisis')

@section('content')

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mt-4 mb-3">
        <div>
            <h1 class="mb-1">Análisis</h1>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Tendencias e historia del negocio · datos del data warehouse</li>
            </ol>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <select class="form-select form-select-sm w-auto" id="analisisMeses" aria-label="Período">
                <option value="3">Últimos 3 meses</option>
                <option value="6">Últimos 6 meses</option>
                <option value="12" selected>Últimos 12 meses</option>
                <option value="24">Últimos 24 meses</option>
                <option value="0">Todo el historial</option>
            </select>
            <button type="button" class="btn btn-sm btn-success" id="btnActualizarDw">
                <i class="fas fa-rotate me-1"></i> Actualizar ahora
            </button>
        </div>
    </div>

    <p class="dw-ultima-carga mb-4" id="dwUltimaCarga">Cargando…</p>

    <div id="analisisVacio" class="dw-vacio d-none">
        <i class="fas fa-warehouse"></i>
        <p>Aún no hay ventas en el data warehouse.<br>Se cargan cada hora; toca <strong>Actualizar ahora</strong> para cargarlas ya.</p>
    </div>

    <div id="analisisContenido">
        <!-- Resumen del período -->
        <div class="row">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-success border-4 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Ingreso neto</div>
                    <div class="h3 mb-0" id="kpiIngreso">Q —</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-primary border-4 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Ganancia neta</div>
                    <div class="h3 mb-0" id="kpiGanancia">Q —</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-warning border-4 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Pedidos</div>
                    <div class="h3 mb-0" id="kpiPedidos">—</div>
                </div></div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-start border-info border-4 shadow-sm h-100"><div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Ticket promedio</div>
                    <div class="h3 mb-0" id="kpiTicket">Q —</div>
                </div></div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-chart-column me-1"></i> Tendencia de 12 meses vs. año anterior</div>
                    <div class="card-body"><canvas id="chartTendencia" height="120"></canvas></div>
                </div>
            </div>
            <div class="col-xl-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-calendar-week me-1"></i> ¿Qué días se vende más?</div>
                    <div class="card-body"><canvas id="chartDias" height="220"></canvas></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><i class="fas fa-percent me-1"></i> Margen de ganancia por categoría</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 dw-tabla">
                        <thead>
                            <tr>
                                <th>Categoría</th>
                                <th class="text-end">Unidades</th>
                                <th class="text-end">Ingreso</th>
                                <th class="text-end">Ganancia</th>
                                <th style="min-width: 160px;">Margen</th>
                            </tr>
                        </thead>
                        <tbody id="tablaMargen"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-5 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-gavel me-1"></i> Desempeño de las subastas</div>
                    <div class="card-body" id="bloqueSubastas"></div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-users me-1"></i> Clientes que compraron</div>
                    <div class="card-body"><canvas id="chartClientes" height="220"></canvas></div>
                </div>
            </div>
            <div class="col-xl-4 col-md-6 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><i class="fas fa-hourglass-half me-1"></i> Días promedio hasta venderse</div>
                    <div class="card-body" id="bloqueTiempoVenta"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/admin/js/analisis-controller.js"></script>
@endpush
