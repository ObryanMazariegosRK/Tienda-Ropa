// =========================================================================
// NOTIFICACIONES PUSH: suscribir / desuscribir este dispositivo (service worker /sw.js)
// =========================================================================

// ==========================================
// NOTIFICACIONES PUSH
// ==========================================
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map(c => c.charCodeAt(0)));
}

async function estaSuscritoPush() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) return false;
    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) return false;
    return !!(await reg.pushManager.getSubscription());
}

async function suscribirPush() {
    const token = localStorage.getItem('auth_token');
    if (!token) throw new Error('Debes iniciar sesión.');

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        throw new Error('Tu navegador no soporta notificaciones.');
    }

    const permiso = await Notification.requestPermission();
    if (permiso !== 'granted') throw new Error('No diste permiso para las notificaciones.');

    // Registramos y ESPERAMOS a que el Service Worker esté activo
    await navigator.serviceWorker.register('/sw.js');
    const reg = await navigator.serviceWorker.ready;   //espera a que esté activo

    const vapidKey = document.querySelector('meta[name="vapid-public-key"]').content;

    let sub;
    try {
        sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey)
        });
    } catch (err) {
        // Navegadores que bloquean el servicio de push de Google (Brave por defecto)
        if (err && /push service error/i.test(err.message)) {
            throw new Error(
                'Tu navegador está bloqueando las notificaciones. ' +
                'actívalas en Configuración. '+
                'También puedes usar Chrome.'
            );
        }
        throw err; // cualquier otro error, lo dejamos pasar tal cual
    }

    const res = await fetch('/api/push/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify(sub)
    });
    if (!res.ok) throw new Error('No se pudo guardar la suscripción.');
}

async function desuscribirPush() {
    const token = localStorage.getItem('auth_token');
    const reg = await navigator.serviceWorker.getRegistration();
    if (!reg) return;
    const sub = await reg.pushManager.getSubscription();
    if (!sub) return;

    await fetch('/api/push/unsubscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'Authorization': `Bearer ${token}` },
        body: JSON.stringify({ endpoint: sub.endpoint })
    });
    await sub.unsubscribe();
}
