import { animate } from 'motion';
import { canHover, easeOut, reducedMotion } from './utils.js';

/**
 * Glow lembut yang mengikuti cursor (hanya di perangkat dengan mouse).
 * Opt-out per halaman: <body data-motion-no-glow>
 */
export function initCursorGlow() {
    if (reducedMotion || !canHover() || document.body.hasAttribute('data-motion-no-glow')) return;

    const glow = document.createElement('div');
    glow.className = 'cursor-glow';
    glow.setAttribute('aria-hidden', 'true');
    document.body.appendChild(glow);

    let targetX = window.innerWidth / 2;
    let targetY = window.innerHeight / 2;
    let x = targetX;
    let y = targetY;
    let visible = false;
    let running = false;

    // Posisi menyusul cursor secara bertahap (lerp) agar gerakannya mengalir, bukan kaku.
    const tick = () => {
        x += (targetX - x) * 0.14;
        y += (targetY - y) * 0.14;
        glow.style.transform = `translate3d(${x}px, ${y}px, 0) translate(-50%, -50%)`;

        if (Math.abs(targetX - x) > 0.3 || Math.abs(targetY - y) > 0.3) {
            requestAnimationFrame(tick);
        } else {
            running = false;
        }
    };

    const show = () => {
        if (visible) return;
        visible = true;
        animate(glow, { opacity: 1 }, { duration: 0.4, ease: easeOut });
    };

    const hide = () => {
        if (!visible) return;
        visible = false;
        animate(glow, { opacity: 0 }, { duration: 0.35, ease: easeOut });
    };

    window.addEventListener('pointermove', (event) => {
        if (event.pointerType !== 'mouse') return;
        targetX = event.clientX;
        targetY = event.clientY;

        if (!visible) {
            x = targetX;
            y = targetY;
        }

        show();
        if (!running) {
            running = true;
            requestAnimationFrame(tick);
        }
    }, { passive: true });

    document.documentElement.addEventListener('pointerleave', hide);
    window.addEventListener('blur', hide);
}
