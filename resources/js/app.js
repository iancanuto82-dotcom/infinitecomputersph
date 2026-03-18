import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

function initPublicScrollReveal() {
    if (!document.body.classList.contains('public-theme')) {
        return;
    }

    const main = document.querySelector('.public-main');
    if (!main) {
        return;
    }

    const sections = Array.from(main.children);
    if (sections.length === 0) {
        return;
    }

    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    sections.forEach((section, index) => {
        section.classList.add('scroll-reveal');
        section.style.setProperty('--scroll-reveal-delay', `${Math.min(index, 4) * 70}ms`);
    });

    if (prefersReducedMotion || !('IntersectionObserver' in window)) {
        sections.forEach((section) => section.classList.add('is-visible'));
        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
        });
    }, {
        threshold: 0.14,
        rootMargin: '0px 0px -8% 0px',
    });

    sections.forEach((section) => observer.observe(section));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPublicScrollReveal, { once: true });
} else {
    initPublicScrollReveal();
}
