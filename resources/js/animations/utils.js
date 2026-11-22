// Helper bersama untuk seluruh lapisan animasi (Motion + Lenis).

export const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const easeOut = [0.22, 1, 0.36, 1];

export const canHover = () => window.matchMedia('(hover: hover) and (pointer: fine)').matches;

/**
 * Menandai elemen agar tidak dianimasikan dua kali oleh file animasi yang berbeda
 * (mis. landing.js, guest-motion.js, dan global-motion.js bisa menjangkau elemen yang sama).
 * Mengembalikan false jika elemen sudah diklaim untuk `key` yang sama.
 */
export function claim(element, key) {
    const attr = `data-motion-done-${key}`;
    if (!element || element.hasAttribute(attr)) return false;
    element.setAttribute(attr, '');
    return true;
}

/** True jika elemen itu sendiri atau leluhurnya sudah diklaim untuk `key`. */
export function claimedWithin(element, key) {
    return Boolean(element.closest(`[data-motion-done-${key}]`));
}

/**
 * Kelas Tailwind seperti `transition` / `transition-all` ikut menghaluskan `transform` dan `opacity`,
 * sehingga bertabrakan dengan animasi Motion (gerakan jadi "dobel easing" dan terasa lambat).
 * Fungsi ini hanya membuang properti yang dipegang Motion, properti lain (warna, dll) tetap transisi.
 */
export function freeTransition(element, { shadow = false } = {}) {
    const props = getComputedStyle(element).transitionProperty.split(',').map((item) => item.trim());
    const owned = new Set(['all', 'transform', 'translate', 'scale', 'rotate', 'opacity', ...(shadow ? ['box-shadow'] : [])]);
    if (!props.some((item) => owned.has(item))) return;

    const keep = props.filter((item) => !owned.has(item));
    if (props.includes('all')) {
        keep.push('color', 'background-color', 'border-color', ...(shadow ? [] : ['box-shadow']));
    }
    element.style.transitionProperty = [...new Set(keep)].join(', ') || 'none';
}
