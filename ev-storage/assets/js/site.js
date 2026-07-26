(() => {
    'use strict';

    const root = document.documentElement;
    const menuButton = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.primary-nav');

    const setMenu = (open) => {
        if (!menuButton || !nav) return;
        menuButton.setAttribute('aria-expanded', String(open));
        nav.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        const icon = open
            ? '<path d="m6 6 12 12M18 6 6 18"/>'
            : '<path d="M4 7h16M4 12h16M4 17h16"/>';
        const svg = menuButton.querySelector('svg');
        if (svg) svg.innerHTML = icon;
    };

    menuButton?.addEventListener('click', () => {
        setMenu(menuButton.getAttribute('aria-expanded') !== 'true');
    });

    nav?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => setMenu(false));
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setMenu(false);
    });

    const revealElements = document.querySelectorAll('.reveal-up, .reveal-left, .reveal-right');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.16, rootMargin: '0px 0px -7% 0px' });

        revealElements.forEach((element) => revealObserver.observe(element));
    } else {
        revealElements.forEach((element) => element.classList.add('is-visible'));
    }

    const stages = [...document.querySelectorAll('[data-scroll-stage]')];
    const parallaxItems = [...document.querySelectorAll('[data-parallax]')];
    const process = document.querySelector('[data-process-track]');
    let ticking = false;

    const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));

    const updateScrollEffects = () => {
        const viewportHeight = window.innerHeight || 1;

        stages.forEach((stage) => {
            const rect = stage.getBoundingClientRect();
            const total = rect.height + viewportHeight;
            const progress = clamp((viewportHeight - rect.top) / total);
            stage.style.setProperty('--stage-progress', progress.toFixed(4));
        });

        parallaxItems.forEach((item) => {
            const rect = item.getBoundingClientRect();
            const strength = Number(item.dataset.parallax || 0.05);
            const offset = (rect.top + rect.height / 2 - viewportHeight / 2) * strength * -1;
            item.style.transform = `translate3d(0, ${offset.toFixed(2)}px, 0)`;
        });

        if (process) {
            const rect = process.getBoundingClientRect();
            const progress = clamp((viewportHeight * .82 - rect.top) / Math.max(rect.height, 1));
            process.style.setProperty('--process-progress', progress.toFixed(4));
        }

        ticking = false;
    };

    const requestUpdate = () => {
        if (ticking) return;
        ticking = true;
        window.requestAnimationFrame(updateScrollEffects);
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    updateScrollEffects();
})();
