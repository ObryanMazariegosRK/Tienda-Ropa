// =========================================================================
// BANNER PRINCIPAL: carrusel de imágenes o videos del grupo activo (solo en la tienda principal)
// =========================================================================

document.addEventListener("DOMContentLoaded", () => {
    const heroBannerContainer = document.getElementById("hero-banner-container");
    let heroCarouselTimer = null;
    let heroCarouselIndex = 0;
    let heroGroupMedia = [];

    const loadHeroBanner = async () => {
        if (!heroBannerContainer) return;
        try {
            const response = await fetch('/api/banner');
            const data = await response.json();

            // data.data es el grupo activo, o null si el admin no ha activado ninguno
            if (data.success && data.data && data.data.media.length > 0) {
                heroGroupMedia = data.data.media.map(m => ({
                    url: m.mediaUrl,
                    type: data.data.type // 'image' o 'video', fijo para todo el grupo
                }));

                heroBannerContainer.innerHTML = `<div class="hero-carousel" id="hero-carousel"></div>`;
                mostrarSlideHero(0);
                iniciarAutoAvanceHero();
            } else {
                heroBannerContainer.innerHTML = `
                    <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
                `;
            }
        } catch (error) {
            console.error("Error al cargar el banner:", error);
            heroBannerContainer.innerHTML = `
                <img src="/image/hero-image.png" alt="Campaña por defecto" class="hero-media">
            `;
        }
    };

    

    function mostrarSlideHero(index) {
        const carousel = document.getElementById("hero-carousel");
        if (!carousel) return;

        const item = heroGroupMedia[index];
        heroCarouselIndex = index;

        if (item.type === 'video') {
            // Sin "loop": queremos que termine y dispare el avance al siguiente
            carousel.innerHTML = `<video class="hero-media" autoplay muted playsinline src="/storage/${item.url}"></video>`;
            carousel.querySelector('video').addEventListener('ended', avanzarSlideHero);
        } else {
            carousel.innerHTML = `<img class="hero-media" src="/storage/${item.url}" alt="Banner">`;
        }
    }

    function avanzarSlideHero() {
        const siguiente = (heroCarouselIndex + 1) % heroGroupMedia.length;
        mostrarSlideHero(siguiente);
    }

    function iniciarAutoAvanceHero() {
        if (heroGroupMedia.length <= 1) return;

        // Si el grupo es de video, el avance ya lo dispara el evento 'ended'
        // dentro de mostrarSlideHero — no necesitamos temporizador aquí.
        if (heroGroupMedia[0].type === 'video') return;

        clearInterval(heroCarouselTimer);
        heroCarouselTimer = setInterval(avanzarSlideHero, 6000);
    }

    loadHeroBanner();
});
