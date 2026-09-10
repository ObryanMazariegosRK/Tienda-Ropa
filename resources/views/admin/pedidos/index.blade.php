@extends('admin.admin')

@section('title', 'Pedidos - Admin')

@section('content')
    <style>
        .order-row-pending { background-color: #fff8e1 !important; }
        .status-select { min-width: 160px; }
        .status-select-wrapper {
            position: relative;
            display: inline-block;
        }
        .status-select-wrapper::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 0.85rem;
            transform: translateY(-50%);
            width: 0.7rem;
            height: 0.7rem;
            border-radius: 50%;
            background-color: currentColor;
            z-index: 2;
            pointer-events: none;
        }
        .status-select-wrapper .status-select {
            padding-left: 2.2rem;
            border-width: 2px;
            border-color: currentColor;
            font-weight: 700;
        }

        .status-select-wrapper.status-pending_payment { color: #C88600; }
        .status-select-wrapper.status-confirmed,
        .status-select-wrapper.status-preparing        { color: #3B82F6; }
        .status-select-wrapper.status-on_route         { color: #A855F7; }
        .status-select-wrapper.status-delivered        { color: #22C55E; }
        .status-select-wrapper.status-cancelled        { color: #EF4444; }

        /* Botón "Detalle" (el ojito) — combina con la paleta del panel */
        .btn-detail-order {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.6rem;
            height: 2.6rem;
            border-radius: 50%;
            border: 1px solid var(--ad-border);
            background-color: var(--ad-card);
            color: var(--ad-green-dark);
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .btn-detail-order:hover {
            background-color: var(--ad-green-soft);
            border-color: var(--ad-green-hover);
            color: var(--ad-green-dark);
        }

        .order-detail-meta p {
            margin-bottom: 0.7rem;
            font-size: 0.95rem;
        }
        .order-detail-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.9rem;
            border-radius: 999px;
            border: 1.5px solid currentColor;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .order-detail-status-pill::before {
            content: "";
            width: 0.6rem;
            height: 0.6rem;
            border-radius: 50%;
            background-color: currentColor;
        }
        .order-detail-status-pill.status-pending_payment { color: #C88600; }
        .order-detail-status-pill.status-confirmed,
        .order-detail-status-pill.status-preparing        { color: #3B82F6; }
        .order-detail-status-pill.status-on_route         { color: #A855F7; }
        .order-detail-status-pill.status-delivered        { color: #22C55E; }
        .order-detail-status-pill.status-cancelled        { color: #EF4444; }

        .order-detail-table th {
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--ad-text-muted);
            border-bottom: 2px solid var(--ad-divider);
        }
        .order-detail-total {
            margin-top: 1rem;
            color: var(--ad-green-dark);
        }
        .status-select-wrapper .status-select:disabled {
            opacity: 0.55;
            cursor: not-allowed;
            background-color: #F1F5F9;
        }

        @media (max-width: 767.98px) {
            #ordersTableContainer table th:nth-child(2),
            #ordersTableContainer table td:nth-child(2),
            #ordersTableContainer table th:nth-child(3),
            #ordersTableContainer table td:nth-child(3) {
                display: none;
            }
            #ordersTableContainer table {
                font-size: 0.82rem;
            }
            #ordersTableContainer td,
            #ordersTableContainer th {
                padding: 0.55rem 0.3rem;
            }

            /* El contenedor principal del admin, más pegado a las orillas */
            #layoutSidenav_content .container-fluid {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }
            .card-body {
                padding: 0.8rem 0.6rem;
            }

            /* Select de estado compacto: el cambio clave es acercar el punto al texto */
            .status-select-wrapper .status-select {
                padding-left: 1.5rem;   /* antes 1.9rem — el punto queda más pegado al texto */
                padding-right: 0.2rem;
                font-size: 0.8rem;
            }
            .status-select-wrapper::before {
                left: 0.5rem;           /* el punto más cerca del borde izquierdo */
                width: 0.5rem;
                height: 0.5rem;
            }
        }

       #orderTabs {
            border-bottom: none;
        }
        #orderTabs .nav-link {
            color: #1F2937 !important;
            border: none;
            background: transparent;
            font-weight: 600;
            cursor: pointer;
            margin-right: 0.4rem;
            border-radius: 8px 8px 0 0;
        }
        #orderTabs .nav-link:hover {
            background: rgba(0, 0, 0, 0.05);
        }
        #orderTabs .nav-link.active {
            color: #1F2937 !important;
            background-color: rgba(0, 0, 0, 0.08);   /* fondo gris tenue para indicar seleccionada */
        }
        

        

        
    </style>

    <h1 class="mt-4">Pedidos</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Pedidos</li>
    </ol>

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span><i class="fas fa-receipt me-1"></i> Pedidos</span>
                <select id="filterStatus" class="form-select form-select-sm" style="width: auto;">
                    <!-- Se llena dinámicamente según la pestaña -->
                </select>
            </div>
            <ul class="nav nav-tabs card-header-tabs mt-2" id="orderTabs">
                <li class="nav-item">
                    <button class="nav-link active" data-grupo="active">Activos</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-grupo="history">Historial</button>
                </li>
            </ul>
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