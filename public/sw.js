// =========================================================================
// SERVICE WORKER de Amerishop (rama app-movil)
// - Notificaciones push (como siempre)
// - App instalable: si no hay internet, las páginas muestran /offline.html
//   en vez del error del navegador. La API, los precios y las fotos NO se
//   guardan en caché: siempre se piden al servidor.
// =========================================================================

// Cambia este número si modificas offline.html o los íconos, para que se vuelvan a descargar
const CACHE_OFFLINE = 'amerishop-offline-v1';
const ARCHIVOS_OFFLINE = ['/offline.html', '/icons/icono-192.png'];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_OFFLINE)
            .then(cache => cache.addAll(ARCHIVOS_OFFLINE))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', function(event) {
    // Borra cachés de versiones anteriores y toma el control de las pestañas abiertas
    event.waitUntil(
        caches.keys()
            .then(nombres => Promise.all(nombres.filter(n => n !== CACHE_OFFLINE).map(n => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

// Solo intervenimos al abrir páginas: si falla la red, mostramos la pantalla sin conexión
self.addEventListener('fetch', function(event) {
    if (event.request.mode !== 'navigate') return;

    event.respondWith(
        fetch(event.request).catch(() => caches.match('/offline.html'))
    );
});

// Recibe el push y muestra la notificación
self.addEventListener('push', function(event) {
    const payload = event.data ? event.data.json() : {};
    const title = payload.title || 'Amerishop';
    const options = {
        body:  payload.body  || '',
        icon:  payload.icon  || '/icons/icono-192.png',
        badge: payload.badge || '/Auth/images/Prenda.png',
        data:  payload.data  || {}
    };
    event.waitUntil(self.registration.showNotification(title, options));
});

// Al tocar la notificación, abre la URL que venga en los datos
self.addEventListener('notificationclick', function(event) {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(clients.openWindow(url));
});
