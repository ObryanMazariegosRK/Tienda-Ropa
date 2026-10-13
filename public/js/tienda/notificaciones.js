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

const MENSAJE_NOTIFICACIONES_BLOQUEADAS =
    'Tu navegador tiene bloqueadas las notificaciones de esta tienda. Para activarlas, toca el candado 🔒 ' +
    'junto a la dirección de la página, cambia "Notificaciones" a "Permitir" y recarga la página.';

async function suscribirPush() {
    const token = localStorage.getItem('auth_token');
    if (!token) throw new Error('Debes iniciar sesión.');

    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        // En iPhone solo funcionan con la tienda agregada a la pantalla de inicio
        const esIPhone = /iPhone|iPad|iPod/.test(navigator.userAgent);
        throw new Error(esIPhone
            ? 'En iPhone, primero agrega la tienda a tu pantalla de inicio (Compartir → "Agregar a inicio") y activa las notificaciones desde ahí.'
            : 'Tu navegador no soporta notificaciones.');
    }

    // Si ya están bloqueadas, el navegador ni siquiera vuelve a preguntar
    if (Notification.permission === 'denied') {
        throw new Error(MENSAJE_NOTIFICACIONES_BLOQUEADAS);
    }

    const permiso = await Notification.requestPermission();
    if (permiso === 'denied') throw new Error(MENSAJE_NOTIFICACIONES_BLOQUEADAS);
    if (permiso !== 'granted') {
        throw new Error('Cerraste el aviso sin elegir. Vuelve a activar el interruptor y toca "Permitir".');
    }

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
