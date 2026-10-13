{{-- App instalable (PWA): manifest, íconos y service worker. Va dentro del <head>. --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#111111">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Amerishop">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">
<script>
    // El mismo service worker de las notificaciones; registrarlo al cargar hace la tienda instalable
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
    }
</script>
