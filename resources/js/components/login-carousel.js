import Swiper from 'swiper';
import { Autoplay, EffectFade, Pagination } from 'swiper/modules';

export function initLoginCarousel() {
    new Swiper('#loginCarousel', {
        modules: [Autoplay, EffectFade, Pagination],
        loop: true,
        effect: 'fade',
        fadeEffect: { crossFade: true },
        speed: 800,
        autoplay: { delay: 5000, disableOnInteraction: false },
        pagination: { el: '#loginCarousel .swiper-pagination', clickable: true },
    });
}
