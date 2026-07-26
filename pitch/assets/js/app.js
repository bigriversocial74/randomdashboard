(() => {
  const progressBar = document.getElementById('progressBar');
  const chapters = [...document.querySelectorAll('.scroll-chapter')];
  const desktopQuery = window.matchMedia('(min-width: 1001px)');
  let ticking = false;

  const stageForProgress = (progress) => {
    // All animation is complete by 52%; the rest is a long reading hold.
    if (progress < 0.04) return 0;
    if (progress < 0.14) return 1;
    if (progress < 0.27) return 2;
    if (progress < 0.40) return 3;
    if (progress < 0.52) return 4;
    return 5;
  };

  const update = () => {
    ticking = false;
    const documentHeight = document.documentElement.scrollHeight - window.innerHeight;
    const pageProgress = documentHeight > 0 ? window.scrollY / documentHeight : 0;
    if (progressBar) progressBar.style.width = `${Math.min(1, Math.max(0, pageProgress)) * 100}%`;

    if (!desktopQuery.matches) {
      chapters.forEach((chapter) => {
        chapter.classList.add('is-active', 'is-complete');
        chapter.dataset.stage = '5';
      });
      return;
    }

    chapters.forEach((chapter) => {
      const rect = chapter.getBoundingClientRect();
      const scrollable = Math.max(1, chapter.offsetHeight - window.innerHeight);
      const progress = Math.min(1, Math.max(0, -rect.top / scrollable));
      const active = rect.top <= 0 && rect.bottom >= window.innerHeight;
      const completed = progress >= 0.995;
      const stage = stageForProgress(progress);

      chapter.style.setProperty('--chapter-progress', progress.toFixed(4));
      chapter.dataset.stage = String(stage);
      chapter.classList.toggle('is-active', active || (rect.top < window.innerHeight && rect.bottom > 0));
      chapter.classList.toggle('is-complete', completed);

      // Sequence items reveal progressively during stage two.
      const sequenceItems = chapter.querySelectorAll('[data-sequence]');
      sequenceItems.forEach((item) => {
        const index = Number(item.dataset.sequence || 0);
        const threshold = 0.13 + index * 0.038;
        const visible = progress >= threshold;
        item.style.opacity = visible ? '1' : '0';
        item.style.transform = visible ? 'none' : 'translateY(34px) scale(.96)';
      });
    });
  };

  const requestUpdate = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  };

  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', requestUpdate);
  desktopQuery.addEventListener?.('change', requestUpdate);
  update();
})();

(() => {
  const toggle = document.querySelector('.mobile-menu-toggle');
  const drawer = document.getElementById('mobileNav');
  const closeButtons = document.querySelectorAll('[data-mobile-menu-close]');
  if (!toggle || !drawer) return;

  const setOpen = (open) => {
    document.body.classList.toggle('mobile-nav-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    if (open) drawer.querySelector('[data-mobile-menu-close]')?.focus();
  };

  toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('mobile-nav-open')));
  closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
  drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && document.body.classList.contains('mobile-nav-open')) {
      setOpen(false);
      toggle.focus();
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 700) setOpen(false);
  });
})();
