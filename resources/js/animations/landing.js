import { animate, inView, stagger } from 'motion';
import { claim, easeOut, reducedMotion as reduceMotion } from './utils.js';

function playHeroIntro() {
    const sequence = [
        ['[data-hero="eyebrow"]', { opacity: [0, 1], y: [14, 0], scale: [0.96, 1] }, { duration: 0.55, ease: easeOut }],
        ['[data-hero="headline"]', { opacity: [0, 1], y: [28, 0], filter: ['blur(8px)', 'blur(0px)'] }, { duration: 0.8, ease: easeOut, at: '-0.22' }],
        ['[data-hero="description"]', { opacity: [0, 1], y: [20, 0] }, { duration: 0.65, ease: easeOut, at: '-0.42' }],
        ['[data-hero="benefits"]', { opacity: [0, 1], y: [16, 0] }, { duration: 0.6, ease: easeOut, at: '-0.35' }],
        ['[data-hero="explore"]', { opacity: [0, 1], y: [12, 0] }, { duration: 0.55, ease: easeOut, at: '-0.32' }],
        ['[data-hero="search-card"]', { opacity: [0, 1], x: [42, 0], y: [12, 0], scale: [0.97, 1] }, { duration: 0.9, type: 'spring', stiffness: 95, damping: 18, at: '-0.72' }],
    ];

    sequence.forEach(([selector]) => {
        const element = document.querySelector(selector);
        if (element) {
            claim(element, 'reveal');
            element.style.opacity = '0';
        }
    });

    // Timeline keeps the hero choreography coordinated instead of firing every element at once.
    animate(sequence);
}

async function typeWriter(element) {
    const words = (element.dataset.words || '').split('|').filter(Boolean);
    if (!words.length || !claim(element, 'type')) return;

    let index = 0;
    element.textContent = '';

    while (true) {
        // Jeda saat tab tidak aktif, lalu lanjut lagi saat tab dibuka kembali.
        if (document.hidden) {
            await new Promise((resolve) => document.addEventListener('visibilitychange', resolve, { once: true }));
            continue;
        }

        const word = words[index % words.length];
        await animate(0, word.length, {
            duration: Math.max(0.45, word.length * 0.075),
            ease: 'linear',
            onUpdate: (value) => {
                element.textContent = word.slice(0, Math.round(value));
            },
        });

        await new Promise((resolve) => window.setTimeout(resolve, 1450));

        await animate(word.length, 0, {
            duration: Math.max(0.22, word.length * 0.035),
            ease: 'easeIn',
            onUpdate: (value) => {
                element.textContent = word.slice(0, Math.round(value));
            },
        });

        index += 1;
        await new Promise((resolve) => window.setTimeout(resolve, 180));
    }
}

function initScrollReveals() {
    document.querySelectorAll('[data-motion="reveal"], [data-motion="reveal-right"]').forEach((element) => {
        if (!claim(element, 'reveal')) return;
        const fromRight = element.dataset.motion === 'reveal-right';
        element.style.opacity = '0';
        inView(element, () => {
            animate(element, {
                opacity: [0, 1],
                x: fromRight ? [34, 0] : 0,
                y: fromRight ? 0 : [26, 0],
                filter: ['blur(5px)', 'blur(0px)'],
            }, { duration: 0.75, ease: easeOut });
        }, { amount: 0.12 });
    });

    document.querySelectorAll('[data-motion-stagger]').forEach((group) => {
        const items = Array.from(group.querySelectorAll(':scope > [data-motion-item]'));
        if (!items.length || !claim(group, 'reveal')) return;

        items.forEach((item) => { item.style.opacity = '0'; });
        inView(group, () => {
            animate(items, {
                opacity: [0, 1],
                y: [28, 0],
                scale: [0.97, 1],
                filter: ['blur(4px)', 'blur(0px)'],
            }, {
                duration: 0.7,
                delay: stagger(0.11, { startDelay: 0.04, from: 'first' }),
                ease: easeOut,
            });
        }, { amount: 0.12 });
    });
}

function initHoverInteractions() {
    if (window.matchMedia('(hover: none)').matches) return;

    document.querySelectorAll('[data-motion-hover]').forEach((element) => {
        if (!claim(element, 'hover')) return;
        element.addEventListener('pointerenter', () => {
            animate(element, { y: -6, scale: 1.012 }, { duration: 0.28, type: 'spring', stiffness: 320, damping: 22 });
        });
        element.addEventListener('pointerleave', () => {
            animate(element, { y: 0, scale: 1 }, { duration: 0.38, type: 'spring', stiffness: 260, damping: 22 });
        });
    });

    document.querySelectorAll('[data-motion-button]').forEach((button) => {
        const arrow = button.querySelector('[data-motion-arrow]');
        if (!arrow || !claim(arrow, 'arrow')) return;
        button.addEventListener('pointerenter', () => animate(arrow, { x: 5 }, { duration: 0.22, type: 'spring', stiffness: 350, damping: 18 }));
        button.addEventListener('pointerleave', () => animate(arrow, { x: 0 }, { duration: 0.25, type: 'spring', stiffness: 300, damping: 20 }));
    });
}

function initAmbientMotion() {
    const largeOrb = document.querySelector('[data-motion-orb="large"]');
    const smallOrb = document.querySelector('[data-motion-orb="small"]');

    if (largeOrb && claim(largeOrb, 'ambient')) animate(largeOrb, { x: [0, -24, 0], y: [0, 18, 0], scale: [1, 1.06, 1] }, { duration: 13, repeat: Infinity, ease: 'easeInOut' });
    if (smallOrb && claim(smallOrb, 'ambient')) animate(smallOrb, { x: [0, 18, 0], y: [0, -22, 0] }, { duration: 10, repeat: Infinity, ease: 'easeInOut' });

    const searchIcon = document.querySelector('[data-motion-icon="search"]');
    if (searchIcon) animate(searchIcon, { rotate: [0, -8, 8, 0], scale: [1, 1.06, 1] }, { duration: 5, repeat: Infinity, repeatDelay: 1.5, ease: 'easeInOut' });
}

function initCampusSelection() {
    const options = document.querySelectorAll('input[name="campus"]');
    options.forEach((input) => {
        input.addEventListener('change', () => {
            const selected = input.closest('label')?.querySelector('.campus-option');
            if (selected) animate(selected, { scale: [0.98, 1.025, 1] }, { duration: 0.38, ease: easeOut });
        });
    });
}

export function initLandingAnimations() {
    if (reduceMotion) return;

    playHeroIntro();
    const typewriter = document.querySelector('[data-typewriter]');
    if (typewriter) typeWriter(typewriter);

    initScrollReveals();
    initHoverInteractions();
    initAmbientMotion();
    initCampusSelection();
}
