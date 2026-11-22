import { animate, hover, inView, press, scroll, stagger } from 'motion';
import { canHover, claim, claimedWithin, easeOut, freeTransition, reducedMotion } from './utils.js';

/**
 * Lapisan Motion global untuk SEMUA layout (guest, auth, owner, tenant).
 * Tidak perlu menambah atribut di setiap blade: kartu, tombol, tabel, dropdown, dan modal
 * dikenali otomatis lewat pola class Tailwind yang sudah dipakai di project.
 *
 * Opt-in / opt-out (opsional):
 *   data-motion-card     -> paksa elemen diperlakukan sebagai kartu
 *   data-motion-skip     -> elemen (dan isinya) tidak dianimasikan
 *   data-motion-ignore   -> tombol/link tidak diberi efek tekan & hover
 *   data-motion-popup    -> elemen `.hidden` yang dianimasikan saat tampil
 *   <body data-motion-off>          -> matikan seluruh lapisan ini
 *   <body data-motion-no-progress>  -> sembunyikan progress bar scroll
 */

const CARD = '[data-motion-card], :is(.rounded-lg, .rounded-xl, .rounded-2xl, .rounded-3xl).bg-white';
const NOT_CARD_TAGS = new Set(['INPUT', 'SELECT', 'TEXTAREA', 'BUTTON', 'LABEL', 'SPAN', 'TD', 'TH', 'TR', 'OPTION', 'IMG', 'I']);
const SKIP_ANCESTORS = 'table, aside, nav, [role="dialog"], [data-motion-skip], [id$="dropdown"], [id$="modal"]';
const BUTTONS = 'button:not([disabled]), a[class*="bg-"][class*="rounded"], [data-motion-press]';

const spring = { type: 'spring', stiffness: 320, damping: 24 };
const NO_SHADOW = '0px 0px 0px 0px rgba(15, 23, 42, 0)';
const LIFT_SHADOW = '0px 16px 32px -14px rgba(15, 23, 42, 0.22)';

/* ---------------------------------------------------------------- kartu */

function collectCards(root) {
    return [...root.querySelectorAll(CARD)].filter((element) => {
        if (NOT_CARD_TAGS.has(element.tagName.toUpperCase())) return false;
        if (element.closest(SKIP_ANCESTORS)) return false;
        if (claimedWithin(element, 'reveal')) return false; // sudah ditangani animasi khusus halaman
        if (element.parentElement?.closest(CARD)) return false; // kartu bersarang: cukup induknya

        const style = getComputedStyle(element);
        if (style.display === 'none' || style.position === 'fixed') return false;

        const { width, height } = element.getBoundingClientRect();
        return width >= 140 && height >= 40;
    });
}

function initCardReveal(cards) {
    // Kartu yang masuk viewport pada saat yang sama muncul beruntun (stagger), bukan serentak.
    let batchAt = 0;
    let batchIndex = 0;
    const nextDelay = () => {
        const now = performance.now();
        if (now - batchAt > 150) batchIndex = 0;
        batchAt = now;
        return Math.min(batchIndex++, 7) * 0.07;
    };

    cards.forEach((card) => {
        if (!claim(card, 'reveal')) return;

        const compact = card.getBoundingClientRect().width < 640;
        freeTransition(card);
        card.style.opacity = '0';

        inView(
            card,
            () => {
                animate(
                    card,
                    { opacity: [0, 1], y: [20, 0], ...(compact ? { scale: [0.985, 1] } : {}) },
                    { duration: 0.6, delay: nextDelay(), ease: easeOut }
                );
            },
            { amount: 0.08, margin: '0px 0px -4% 0px' }
        );
    });
}

function initCardHover(cards) {
    if (!canHover()) return;

    cards.forEach((card) => {
        const { width, height } = card.getBoundingClientRect();
        const hasSiblingCards = [...(card.parentElement?.children ?? [])].some((el) => el !== card && el.matches(CARD));
        const clickable = card.matches('a') || card.hasAttribute('data-motion-hover') || Boolean(card.querySelector(':scope > a'));
        const compact = width <= 560 && height <= 520;

        // Kartu besar (tabel/form/section penuh) tidak diangkat; hanya kartu ringkas atau kartu yang bisa diklik.
        if (!(clickable || (compact && hasSiblingCards))) return;
        if (!claim(card, 'hover')) return;

        // Kalau class Tailwind sudah punya `hover:shadow-*` atau kartu sudah punya shadow, biarkan shadow apa adanya.
        const animateShadow = getComputedStyle(card).boxShadow === 'none' && !/hover:shadow/.test(card.className);
        freeTransition(card, { shadow: animateShadow });

        hover(card, () => {
            animate(card, { y: -4, ...(animateShadow ? { boxShadow: LIFT_SHADOW } : {}) }, spring);
            return () => animate(card, { y: 0, ...(animateShadow ? { boxShadow: NO_SHADOW } : {}) }, { ...spring, stiffness: 260 });
        });
    });
}

/* ------------------------------------------------------- judul & flash */

function initTitles(root) {
    root.querySelectorAll('h1').forEach((heading) => {
        if (heading.closest(SKIP_ANCESTORS) || claimedWithin(heading, 'reveal') || !claim(heading, 'reveal')) return;
        heading.style.opacity = '0';
        inView(heading, () => animate(heading, { opacity: [0, 1], x: [-14, 0] }, { duration: 0.6, ease: easeOut }), { amount: 0.2 });
    });
}

function initFlash() {
    document.querySelectorAll('[role="status"], [role="alert"]').forEach((element) => {
        if (element.closest('[data-motion-skip], [id$="dropdown"]') || !claim(element, 'reveal')) return;
        animate(element, { opacity: [0, 1], y: [-10, 0], scale: [0.98, 1] }, { type: 'spring', stiffness: 260, damping: 22 });
    });
}

/* --------------------------------------------------------------- tabel */

function initTables(root) {
    root.querySelectorAll('table').forEach((table) => {
        if (table.closest('[data-motion-skip]')) return;
        const rows = [...table.querySelectorAll('tbody > tr')].slice(0, 14);
        if (rows.length < 2 || !claim(table, 'rows')) return;

        rows.forEach((row) => { row.style.opacity = '0'; });
        inView(
            table,
            () => animate(rows, { opacity: [0, 1] }, { duration: 0.4, delay: stagger(0.035, { startDelay: 0.12 }), ease: 'easeOut' }),
            { amount: 0.05 }
        );
    });
}

/* -------------------------------------------------------------- tombol */

function initButtons() {
    document.querySelectorAll(BUTTONS).forEach((element) => {
        if (element.closest('[data-motion-ignore]') || !claim(element, 'press')) return;

        const style = getComputedStyle(element);
        if (style.display === 'inline') return; // transform tidak berlaku pada elemen inline

        // Link ber-background yang lebar biasanya kartu, bukan tombol.
        if (element.tagName === 'A') {
            const { width, height } = element.getBoundingClientRect();
            if (width > 360 || height > 90) return;
        }

        freeTransition(element);

        hover(element, () => {
            animate(element, { scale: 1.03 }, { duration: 0.18, ease: 'easeOut' });
            return () => animate(element, { scale: 1 }, { duration: 0.25, ease: easeOut });
        });

        press(element, () => {
            animate(element, { scale: 0.96 }, { duration: 0.12, ease: 'easeOut' });
            return () => animate(element, { scale: element.matches(':hover') ? 1.03 : 1 }, { type: 'spring', stiffness: 400, damping: 20 });
        });
    });
}

/* ------------------------------------------------------------- sidebar */

function initSidebar() {
    const links = [...document.querySelectorAll('#owner-sidebar nav a')];
    if (!links.length) return;

    animate(
        links.map((link) => link.closest('li') ?? link),
        { opacity: [0, 1], x: [-14, 0] },
        { duration: 0.5, delay: stagger(0.045, { startDelay: 0.15 }), ease: easeOut }
    );

    links.forEach((link) => {
        claim(link, 'press'); // jangan ikut efek tekan tombol umum
        if (link.hasAttribute('aria-current') || !canHover()) return;
        hover(link, () => {
            animate(link, { x: 4 }, { type: 'spring', stiffness: 400, damping: 26 });
            return () => animate(link, { x: 0 }, { type: 'spring', stiffness: 320, damping: 26 });
        });
    });
}

/* ------------------------------------------------- modal, overlay, dropdown */

// Modal/dropdown di project ini dibuka dengan menghapus class `hidden`.
// MutationObserver mendeteksi saat itu terjadi dan memutar animasi masuk tanpa mengubah script bawaannya.
function initPopups() {
    const popups = [...document.querySelectorAll('.fixed.inset-0, [id$="dropdown"], [data-motion-popup]')].filter((element) =>
        element.classList.contains('hidden')
    );

    popups.forEach((popup) => {
        const isOverlay = popup.matches('.fixed.inset-0');
        const panel = isOverlay ? popup.querySelector('[role="dialog"]') ?? popup.querySelector('img') : null;
        let wasHidden = true;

        new MutationObserver(() => {
            const isHidden = popup.classList.contains('hidden');
            const justOpened = wasHidden && !isHidden;
            wasHidden = isHidden;
            if (!justOpened) return;

            if (isOverlay) {
                animate(popup, { opacity: [0, 1] }, { duration: 0.22, ease: 'easeOut' });
                if (panel) {
                    animate(panel, { opacity: [0, 1], y: [18, 0], scale: [0.95, 1] }, { type: 'spring', stiffness: 300, damping: 26 });
                }
            } else {
                popup.style.transformOrigin = 'top right';
                animate(popup, { opacity: [0, 1], y: [-8, 0], scale: [0.96, 1] }, { duration: 0.2, ease: easeOut });
            }
        }).observe(popup, { attributes: true, attributeFilter: ['class'] });
    });
}

/* ---------------------------------------------------- progress scroll */

function initScrollProgress() {
    if (document.body.hasAttribute('data-motion-no-progress')) return;

    const bar = document.createElement('div');
    bar.className = 'motion-scroll-progress';
    bar.setAttribute('aria-hidden', 'true');
    document.body.appendChild(bar);

    scroll(animate(bar, { scaleX: [0, 1] }, { ease: 'linear' }));
}

/* ----------------------------------------------------------------- init */

export function initGlobalMotion() {
    if (reducedMotion || document.body.hasAttribute('data-motion-off')) return;

    const root = document.querySelector('main') ?? document.body;

    initSidebar();
    initFlash();

    const cards = collectCards(root);
    initCardReveal(cards);
    initCardHover(cards);

    initTitles(root);
    initTables(root);
    initButtons();
    initPopups();
    initScrollProgress();
}
