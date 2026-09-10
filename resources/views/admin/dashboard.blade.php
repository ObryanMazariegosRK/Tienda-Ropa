@extends('admin.admin')

@section('title', 'Dashboard Principal')

@section('content')
    <h1 class="mt-4">Dashboard</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item active">Resumen General</li>
    </ol>

    <!-- ========== BLOQUE 1: RESUMEN RÁPIDO ========== -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Ingresos del mes</div>
                    <div class="h3 mb-0" id="summaryMonthlyRevenue">Q —</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Pedidos pendientes</div>
                    <div class="h3 mb-0" id="summaryPendingOrders">—</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Productos activos</div>
                    <div class="h3 mb-0" id="summaryActiveProducts">—</div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold">Subastas activas</div>
                    <div class="h3 mb-0" id="summaryActiveAuctions">—</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========== BLOQUE 2: VENTAS Y FINANZAS ========== -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="fas fa-chart-line me-1"></i> Ingresos</span>
            <div class="btn-group btn-group-sm" role="group" id="revenueRangeButtons">
                <button type="button" class="btn btn-outline-light" data-range="today">Hoy</button>
                <button type="button" class="btn btn-outline-light" data-range="7d">7 días</button>
                <button type="button" class="btn btn-outline-light active" data-range="30d">30 días</button>
                <button type="button" class="btn btn-outline-light" data-range="custom">Personalizado</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3 d-none" id="customRangeInputs">
                <div class="col-md-4">
                    <label class="form-label small">Desde</label>
                    <input type="date" id="customStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Hasta</label>
                    <input type="date" id="customEndDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="button" class="btn btn-primary btn-sm w-100" id="applyCustomRangeBtn">Aplicar</button>
                </div>
            </div>

            <div class="mb-3">
                <div class="text-muted small">Total del periodo</div>
                <div class="h2 mb-0" id="revenueTotalAmount">Q 0.00</div>
                <div class="text-success fw-bold" id="revenueTotalProfit">Ganancia: Q 0.00</div>
            </div>
            

            <div style="position: relative; height: 300px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- ========== INGRESOS POR TIPO DE VENTA ========== -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h6 class="text-muted text-uppercase fw-bold mb-0">Ingresos por tipo de venta</h6>
        <small class="text-muted" id="saleTypePeriodLabel"></small>
    </div>
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-1">
                        <i class="fas fa-store me-1"></i> Venta directa
                    </div>
                    <div class="h3 mb-0" id="revenueDirect">Q 0.00</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase fw-bold mb-1">
                        <i class="fas fa-gavel me-1"></i> Subastas
                    </div>
                    <div class="h3 mb-0" id="revenueAuction">Q 0.00</div>
                </div>
            </div>
        </div>
    </div>


    <!-- ========== EXPORTAR REPORTES ========== -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body d-flex align-items-center flex-wrap gap-2">
            <span class="text-muted fw-bold me-2"><i class="fas fa-download me-1"></i> Exportar reportes:</span>
            <button type="button" class="btn btn-success btn-sm" id="exportOrdersExcelBtn">
                <i class="fas fa-file-excel me-1"></i> Pedidos (Excel)
            </button>

            <button type="button" class="btn btn-success btn-sm" id="exportSalesExcelBtn">
                <i class="fas fa-file-excel me-1"></i> Ventas (Excel)
            </button>


            <button type="button" class="btn btn-danger btn-sm" id="exportSalesPdfBtn">
                <i class="fas fa-file-pdf me-1"></i> Ventas (PDF)
            </button>

            <button type="button" class="btn btn-danger btn-sm" id="exportOrdersPdfBtn">
                <i class="fas fa-file-pdf me-1"></i> Pedidos (PDF)
            </button>

        </div>
    </div>



    <!-- ========== BLOQUE 3: PEDIDOS ========== -->
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-dark text-white">
                    <i class="fas fa-receipt me-1"></i> Pedidos por estado
                </div>
                <div class="card-body">
                    <canvas id="ordersStatusChart" height="220"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-4">
            <div class="row h-100">

                <div class="col-12">
                    <div class="card shadow-sm border-danger">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle me-1"></i> Pedidos atascados (+3 días sin avanzar)
                        </div>
                        <div class="card-body p-0">
                            <div id="stuckOrdersList" class="list-group list-group-flush">
                                <div class="list-group-item text-muted">Cargando...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    


    <!-- ========== TOP 5 CATEGORÍAS ========== -->
    <div class="card mb-4 shadow-sm">


        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-trophy me-1"></i> Top 5 categorías con más ventas</span>
            <small class="text-white-50" id="topCategoriesPeriodLabel"></small>
        </div>

        <div class="card-body">
            <div id="topCategoriesList" class="list-group list-group-flush">
                <div class="list-group-item text-muted">Cargando...</div>
            </div>
        </div>
    </div>

    
    

    
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="/admin/js/dashboard-controller.js"></script>
@endpush