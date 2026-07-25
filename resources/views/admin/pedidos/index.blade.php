@extends('admin.admin')

@section('title', 'Pedidos - Admin')

@section('content')
    <style>
        .order-row-pending { background-color: #fff8e1 !important; }
        .status-select { min-width: 160px; }
    </style>

    <h1 class="mt-4">Pedidos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Pedidos</li>
    </ol>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-receipt me-1"></i> Todos los pedidos</span>
            <select id="filterStatus" class="form-select form-select-sm" style="width: auto;">
                <option value="">Todos los estados</option>
                <option value="pending_payment">Pendiente de pago</option>
                <option value="confirmed">Confirmado</option>
                <option value="preparing">En preparación</option>
                <option value="on_route">En camino</option>
                <option value="delivered">Entregado</option>
                <option value="cancelled">Cancelado</option>
            </select>
        </div>
        <div class="card-body">
            <div id="ordersTableContainer">
                <p class="text-center text-muted">Cargando pedidos...</p>
            </div>
        </div>
    </div>

    <!-- Modal de detalle del pedido -->
    <div class="modal fade" id="modalOrderDetail" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del pedido</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="orderDetailBody">
                    <!-- Se llena dinámicamente -->
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="/admin/js/pedidos-controller.js"></script>
@endpush