// Recibe el push y muestra la notificación
self.addEventListener('push', function(event) {
    const payload = event.data ? event.data.json() : {};
    const title = payload.title || 'Amerishop';
    const options = {
        body:  payload.body  || '',
        icon:  payload.icon  || '/Auth/images/Prenda.png',
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