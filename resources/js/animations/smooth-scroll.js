import Lenis from 'lenis';
import { reducedMotion } from './utils.js';

// Container internal yang benar-benar bisa di-scroll (dropdown notifikasi, sidebar, modal, dll)
// harus tetap memakai scroll native supaya tidak "terjebak" oleh Lenis.
function isNestedScroller(node) {
    if (!(node instanceof HTMLElement) || node === document.body || node === document.documentElement) return false;
    const { overflowY } = getComputedStyle(node);
    return (overflowY === 'auto' || overflowY === 'scroll') && node.scrollHeight > node.clientHeight + 1;
}

function anchorOffset() {
    const bar = document.querySelector('nav.sticky, header.sticky, nav.fixed, header.fixed');
    return -((bar?.offsetHeight ?? 0) + 16);
}

export function initSmoothScroll() {
    // Hormati preferensi "reduce motion" dan opt-out per halaman: <body data-lenis-off>.
    if (reducedMotion || document.body.hasAttribute('data-lenis-off')) return null;

    const lenis = new Lenis({
        duration: 1.15,
        easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        smoothWheel: true,
        autoRaf: true,
        prevent: isNestedScroller,
    });

    window.lenis = lenis;

    // Hentikan Lenis saat body dikunci (mis. sidebar mobile terbuka memakai `overflow-hidden`).
    const syncLock = () => {
        if (document.body.classList.contains('overflow-hidden')) lenis.stop();
        else lenis.start();
    };
    new MutationObserver(syncLock).observe(document.body, { attributes: true, attributeFilter: ['class'] });
    syncLock();

    // Link anchor di halaman yang sama (#fasilitas, dll) ikut scroll halus.
    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[href*="#"]');
        if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || link.target === '_blank') return;
        if (link.origin !== location.origin || link.pathname !== location.pathname || link.hash.length < 2) return;

        const target = document.getElementById(decodeURIComponent(link.hash.slice(1)));
        if (!target) return;

        event.preventDefault();
        lenis.scrollTo(target, { offset: anchorOffset(), duration: 1.2 });
        history.pushState(null, '', link.hash);
    });

    return lenis;
}
