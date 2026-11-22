import { animate, inView, stagger } from 'motion';
import { canHover, claim, reducedMotion } from './utils.js';

function revealElements(selector, options = {}) {
    document.querySelectorAll(selector).forEach((element) => {
        if (reducedMotion || !claim(element, 'reveal')) return;
        element.style.opacity = '0';
        inView(element, () => {
            animate(element,
                { opacity: [0, 1], y: [options.y ?? 22, 0], filter: ['blur(5px)', 'blur(0px)'] },
                { duration: options.duration ?? 0.65, delay: options.delay ?? 0, ease: [0.22, 1, 0.36, 1] }
            );
        }, { amount: options.amount ?? 0.15, once: true });
    });
}

function revealGroups(selector) {
    document.querySelectorAll(selector).forEach((group) => {
        const children = [...group.children].filter((el) => !el.matches('[data-motion-ignore]'));
        if (!children.length) return;
        if (reducedMotion || !claim(group, 'reveal')) return;
        children.forEach((child) => { child.style.opacity = '0'; });
        inView(group, () => {
            animate(children,
                { opacity: [0, 1], y: [24, 0], scale: [0.985, 1] },
                { duration: 0.58, delay: stagger(0.09), ease: [0.22, 1, 0.36, 1] }
            );
        }, { amount: 0.12, once: true });
    });
}

function initTypewriter() {
    const target = document.querySelector('[data-typewriter]');
    if (!target || reducedMotion || !claim(target, 'type')) return;
    const words = (target.dataset.words || 'lebih dekat|lebih nyaman|lebih sesuai').split('|');
    let wordIndex = 0;
    let charIndex = words[0].length;
    let deleting = true;
    let timer;
    target.textContent = words[0];
    const tick = () => {
        const word = words[wordIndex];
        if (deleting) {
            charIndex--;
            if (charIndex <= 0) {
                deleting = false;
                wordIndex = (wordIndex + 1) % words.length;
            }
        } else {
            charIndex++;
            if (charIndex >= words[wordIndex].length) {
                deleting = true;
                timer = window.setTimeout(tick, 1450);
                target.textContent = words[wordIndex];
                return;
            }
        }
        target.textContent = words[wordIndex].slice(0, charIndex);
        timer = window.setTimeout(tick, deleting ? 48 : 82);
    };
    timer = window.setTimeout(tick, 1500);
    window.addEventListener('pagehide', () => window.clearTimeout(timer), { once: true });
}

function initMagneticHover() {
    if (reducedMotion || !canHover()) return;
    document.querySelectorAll('[data-motion-hover]').forEach((element) => {
        if (!claim(element, 'hover')) return;
        element.addEventListener('pointerenter', () => animate(element, { y: -5, scale: 1.012 }, { duration: 0.24, ease: 'easeOut' }));
        element.addEventListener('pointerleave', () => animate(element, { y: 0, scale: 1 }, { duration: 0.32, ease: [0.22, 1, 0.36, 1] }));
    });
}

function initHeroAmbient() {
    const orbs = document.querySelectorAll('[data-motion-orb]');
    if (reducedMotion || !orbs.length) return;
    orbs.forEach((orb, index) => {
        if (!claim(orb, 'ambient')) return;
        animate(orb, { x: [0, index % 2 ? -14 : 18, 0], y: [0, index % 2 ? 18 : -14, 0], scale: [1, 1.06, 1] },
            { duration: 10 + index * 2, repeat: Infinity, ease: 'easeInOut' });
    });
}


function initOwnerLandingMotion() {
    const page = document.querySelector('[data-page="owner-landing"]');
    if (!page || reducedMotion) return;

    const heroCopy = page.querySelector('[data-owner-hero-copy]');
    const dashboard = page.querySelector('[data-owner-dashboard]');

    if (heroCopy) {
        const items = [...heroCopy.querySelectorAll('[data-owner-intro]')];
        if (items.length) {
            items.forEach((item) => claim(item, 'reveal'));
            animate(items,
                { opacity: [0, 1], y: [26, 0], filter: ['blur(6px)', 'blur(0px)'] },
                { duration: 0.78, delay: stagger(0.12), ease: [0.22, 1, 0.36, 1] }
            );
        }
    }

    if (dashboard) {
        claim(dashboard, 'reveal');
        animate(dashboard,
            { opacity: [0, 1], x: [48, 0], y: [18, 0], scale: [0.96, 1], rotateX: [5, 0] },
            { duration: 1.05, delay: 0.28, type: 'spring', stiffness: 82, damping: 19 }
        );

        const panel = dashboard.querySelector('[data-dashboard-float]');
        if (panel) {
            animate(panel, { y: [0, -7, 0] },
                { duration: 5.5, repeat: Infinity, ease: 'easeInOut' });
        }
    }

    revealGroups('[data-owner-benefits]');
    revealGroups('[data-owner-features]');
    revealGroups('[data-owner-steps]');
    revealElements('[data-owner-section-heading]');
    revealElements('[data-owner-final-cta]', { y: 30, duration: 0.8, amount: 0.18 });

    // Subtle accordion feedback without replacing native accessible details behavior.
    page.querySelectorAll('details').forEach((details) => {
        const content = details.querySelector('p');
        if (!content) return;
        details.addEventListener('toggle', () => {
            if (details.open) {
                animate(content, { opacity: [0, 1], y: [-5, 0] },
                    { duration: 0.28, ease: [0.22, 1, 0.36, 1] });
            }
        });
    });
}

function initPropertyDetailMotion() {
    const page = document.querySelector('[data-page="property-detail"]');
    if (!page) return;

    const breadcrumb = page.querySelector('[data-property-breadcrumb]');
    const heading = page.querySelector('[data-property-heading]');
    const gallery = page.querySelector('[data-property-gallery]');

    if (!reducedMotion) {
        const intro = [breadcrumb, heading].filter(Boolean);
        if (intro.length) {
            intro.forEach((item) => claim(item, 'reveal'));
            animate(intro,
                { opacity: [0, 1], y: [18, 0] },
                { duration: 0.65, delay: stagger(0.1), ease: [0.22, 1, 0.36, 1] }
            );
        }

        if (gallery) {
            claim(gallery, 'reveal');
            animate(gallery,
                { opacity: [0, 1], y: [30, 0], scale: [0.975, 1], clipPath: ['inset(8% 3% 8% 3% round 22px)', 'inset(0% 0% 0% 0% round 22px)'] },
                { duration: 0.95, delay: 0.18, ease: [0.22, 1, 0.36, 1] }
            );
            const image = gallery.querySelector('img');
            if (image) {
                image.addEventListener('pointermove', (event) => {
                    if (!window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
                    const rect = gallery.getBoundingClientRect();
                    const dx = (event.clientX - rect.left) / rect.width - 0.5;
                    const dy = (event.clientY - rect.top) / rect.height - 0.5;
                    animate(image, { scale: 1.035, x: dx * 5, y: dy * 5 },
                        { duration: 0.35, ease: 'easeOut' });
                });
                gallery.addEventListener('pointerleave', () => {
                    animate(image, { scale: 1, x: 0, y: 0 },
                        { duration: 0.55, ease: [0.22, 1, 0.36, 1] });
                });
            }
        }
    }

    revealElements('[data-property-section]');
    revealGroups('[data-property-facilities]');
    revealGroups('[data-property-rooms]');
    revealElements('[data-property-map-panel]', { y: 28, duration: 0.75 });

    if (!reducedMotion) {
        page.querySelectorAll('[data-property-room-card]').forEach((card) => {
            if (!claim(card, 'hover')) return;
            card.addEventListener('pointerenter', () => {
                if (canHover()) {
                    animate(card, { y: -6, scale: 1.012 },
                        { duration: 0.25, type: 'spring', stiffness: 300, damping: 22 });
                }
            });
            card.addEventListener('pointerleave', () => {
                animate(card, { y: 0, scale: 1 },
                    { duration: 0.35, type: 'spring', stiffness: 260, damping: 22 });
            });
        });
    }
}

export function initGuestMotion() {
    initTypewriter();
    revealElements('[data-motion-reveal]');
    revealElements('[data-motion-hero]', { y: 30, duration: 0.8, amount: 0.05 });
    revealGroups('[data-motion-stagger]');
    initMagneticHover();
    initHeroAmbient();
    initOwnerLandingMotion();
    initPropertyDetailMotion();
}
