{{-- Tarjetas "fantasma" mientras llegan los productos (el JS reemplaza todo el contenedor).
     Uso: @include('partials.tarjetas-cargando', ['texto' => 'Cargando ofertas...']) --}}
<span class="skeleton-sr" role="status">{{ $texto ?? 'Cargando...' }}</span>
@for ($i = 0; $i < 8; $i++)
    <div class="product-card skeleton-card" aria-hidden="true">
        <div class="product-image-wrapper skeleton-box"></div>
        <div class="product-info">
            <span class="skeleton-line"></span>
            <span class="skeleton-line skeleton-line-short"></span>
        </div>
    </div>
@endfor
