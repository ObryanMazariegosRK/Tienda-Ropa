<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

   
    <link rel="icon" href="{{ asset('Auth/images/Prenda.png') }}" type="image/x-png">
    <title>@yield('title', 'Panel de Administración')</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="/admin/css/styles.css" rel="stylesheet" />
    <link href="/admin/css/admin-theme.css" rel="stylesheet" />
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
        <button class="btn btn-link btn-sm ms-3 me-2" id="sidebarToggle"><i class="fas fa-bars"></i></button>
        <a class="navbar-brand ps-3" href="{{ route('dashboard') }}">Amerishop Admin</a>
        

        <div class="ms-auto d-flex align-items-center gap-3 pe-3">
            <span class="admin-user-greeting">
                Bienvenido, <span id="admin-user-name"></span>
            </span>
            <button type="button" class="btn btn-sm btn-outline-light admin-logout-btn" onclick="adminLogout()" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
                <span class="admin-logout-text">Cerrar sesión</span>
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

                        <a class="nav-link {{ request()->routeIs('productos.catalogo') ? 'active' : '' }}" href="{{ route('productos.catalogo') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-box-open"></i></div>
                            Ver Catálogo
                        </a>

                        <a class="nav-link {{ request()->routeIs('productos.index') ? 'active' : '' }}" href="{{ route('productos.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-plus-circle"></i></div>
                            Nuevo Producto
                        </a>

                        <a class="nav-link {{ request()->routeIs('categorias.index') ? 'active' : '' }}" href="{{ route('categorias.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-sitemap"></i></div>
                            Categorías
                        </a>

                        <a class="nav-link {{ request()->routeIs('banners.index') ? 'active' : '' }}" href="{{ route('banners.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-images"></i></div>
                            Banners
                        </a>

                        <a class="nav-link {{ request()->routeIs('pedidos.index') ? 'active' : '' }}" href="{{ route('pedidos.index') }}">
                            <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                            Pedidos
                        </a>

                        
                                                
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

    <!-- Modal de confirmación genérico -->
    <div class="modal fade" id="adminConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-confirm-modal">
                <div class="modal-body text-center p-4">
                    <div class="admin-confirm-icon mb-3" id="adminConfirmIcon"><i class="fas fa-question-circle"></i></div>
                    <h5 class="mb-2" id="adminConfirmTitle">¿Estás seguro?</h5>
                    <p class="text-muted mb-4" id="adminConfirmMessage"></p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-secondary flex-fill" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-danger flex-fill" id="adminConfirmBtn">Confirmar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de aviso genérico -->
    <div class="modal fade" id="adminAlertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content admin-confirm-modal">
                <div class="modal-body text-center p-4">
                    <div class="admin-confirm-icon mb-3" id="adminAlertIcon"><i class="fas fa-check-circle"></i></div>
                    <h5 class="mb-2" id="adminAlertTitle">Listo</h5>
                    <p class="text-muted mb-4" id="adminAlertMessage"></p>
                    <button type="button" class="btn btn-primary w-100" data-bs-dismiss="modal">Aceptar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- SCRIPTS BASE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="/admin/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const contenido = document.getElementById('layoutSidenav_content');

            if (contenido) {
                contenido.addEventListener('click', () => {
                    const esMovil = window.innerWidth <= 767;
                    const menuAbierto = document.body.classList.contains('sb-sidenav-toggled');

                    if (esMovil && menuAbierto) {
                        document.body.classList.remove('sb-sidenav-toggled');
                        localStorage.setItem('sb|sidebar-toggle', 'false');
                    }
                });
            }
        });
    </script>
    
    <!-- HUECO PARA SCRIPTS DE CADA VISTA -->
     <script src="/admin/js/admin-modals.js"></script>
     <script src="/admin/js/admin-form-autosave.js"></script>
    @stack('scripts')
</body>
</html>