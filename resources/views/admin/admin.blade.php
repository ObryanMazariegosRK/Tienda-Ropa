<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>@yield('title', 'Panel de Administración')</title>
    
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="/admin/css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>

    <!--Para ocultar el contenido antes de validar si es admin o no-->
    <style>
        body:not(.admin-auth-verified) #layoutSidenav { display: none; }
    </style>
    <script src="/admin/js/admin-auth.js"></script>
    
</head>
<body class="sb-nav-fixed">
    
    <!-- NAVBAR SUPERIOR -->
    <nav class="sb-topnav navbar navbar-expand navbar-dark bg-dark">
        <a class="navbar-brand ps-3" href="{{ route('dashboard') }}">Moda Admin</a>
        <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle"><i class="fas fa-bars"></i></button>

        <div class="ms-auto d-flex align-items-center gap-3 pe-3">
            <span id="admin-user-name" class="text-white"></span>
            <button type="button" class="btn btn-sm btn-outline-light" onclick="adminLogout()">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
        </div>
    </nav>

    <div id="layoutSidenav">
        <!-- MENÚ LATERAL -->
        <div id="layoutSidenav_nav">
            <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                <div class="sb-sidenav-menu">
                    <div class="nav">
                        
                        <div class="sb-sidenav-menu-heading">Principal</div>
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                            Dashboard
                        </a>
                        
                        <div class="sb-sidenav-menu-heading">Gestión</div>
                        
                        <!-- LÓGICA DEL MENÚ DESPLEGABLE -->
                        @php
                            // Esto se queda igual: mantiene el menú abierto si estamos en cualquier ruta de productos o categorías
                            $isOpen = request()->routeIs('productos.*', 'categorias.*');
                        @endphp

                        <a class="nav-link {{ $isOpen ? '' : 'collapsed' }}" href="#" data-bs-toggle="collapse" data-bs-target="#collapseProductos" aria-expanded="{{ $isOpen ? 'true' : 'false' }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-box-open"></i></div>
                            Catálogo
                            <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                        </a>

                        <div class="collapse {{ $isOpen ? 'show' : '' }}" id="collapseProductos" data-bs-parent="#sidenavAccordion">
                            <nav class="sb-sidenav-menu-nested nav">
                                <!-- 1. ENLACE AL CATÁLOGO (Grid de Tarjetas) -->
                                <a class="nav-link {{ request()->routeIs('productos.catalogo') ? 'active' : '' }}" href="{{ route('productos.catalogo') }}">
                                    Ver Catálogo
                                </a>
                                
                                <!-- 2. ENLACE AL FORMULARIO DE CREAR -->
                                <a class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                                    Nuevo Producto
                                </a>
                                
                                <!-- 3. ENLACE A LAS CATEGORÍAS -->
                                <a class="nav-link {{ request()->routeIs('categorias.index') ? 'active' : '' }}" href="{{ route('categorias.index') }}">
                                    Categorías
                                </a>

                                <!-- 4 ENLACE A LOS BANNERS (aun no funciona)-->
                                <a class="nav-link {{ request()->routeIs('banners.index') ? 'active' : '' }}" href="{{ route('banners.index') }}">
                                    Banners
                                </a>
                                <!--Para los pedidos pa-->
                                <a class="nav-link {{ request()->routeIs('pedidos.index') ? 'active' : '' }}" href="{{ route('pedidos.index') }}">
                                    <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                                    Pedidos
                                </a>
                            </nav>
                        </div>

                        
                                                
                    </div>
                </div>
            </nav>
        </div>

        <!-- CONTENIDO DINÁMICO -->
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- SCRIPTS BASE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/admin/js/scripts.js"></script>
    
    <!-- HUECO PARA SCRIPTS DE CADA VISTA -->
    @stack('scripts')
</body>
</html>