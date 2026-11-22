import { initSmoothScroll } from './animations/smooth-scroll.js';
import { initLandingAnimations } from './animations/landing.js';
import { initGuestMotion } from './animations/guest-motion.js';
import { initGlobalMotion } from './animations/global-motion.js';
import { initCursorGlow } from './animations/cursor-glow.js';

// 1) Lenis: smooth scroll untuk semua halaman (guest, auth, owner, tenant).
initSmoothScroll();

// 2) Animasi khusus halaman dijalankan lebih dulu. Elemen yang mereka tangani ditandai
//    (lihat claim() di animations/utils.js) supaya tidak dianimasikan ulang oleh lapisan global.
if (document.querySelector('[data-page="landing"]')) {
    initLandingAnimations();
}

if (document.querySelector('[data-guest-motion]')) {
    initGuestMotion();
}

// 3) Lapisan Motion global: kartu, hover, tombol, tabel, modal/dropdown, progress scroll.
initGlobalMotion();

// 4) Glow yang mengikuti cursor (hanya perangkat dengan mouse).
initCursorGlow();
