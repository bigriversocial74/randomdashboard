(() => {
  const scrollStories = [
    { element: document.querySelector('.hero-scroll'), variable: '--hero-progress', phases: true, holdAt: 1 },
    { element: document.querySelector('.data-story'), variable: '--data-progress', holdAt: .40 },
    { element: document.querySelector('.ai-story'), variable: '--ai-progress', holdAt: .40 },
    { element: document.querySelector('.transformation-story'), variable: '--transform-progress', holdAt: .40 },
    { element: document.querySelector('.value-map-story'), variable: '--map-progress', holdAt: .40 },
    { element: document.querySelector('.agent-story'), variable: '--agent-progress', holdAt: .40 },
    { element: document.querySelector('.launch-section'), variable: '--launch-progress', holdAt: .40 },
    { element: document.querySelector('.synergy-summary'), variable: '--synergy-progress', holdAt: .40 },
    { element: document.querySelector('.final-cta-scroll'), variable: '--final-cta-progress', holdAt: .46 },
  ].filter((item) => item.element);

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let ticking = false;

  const update = () => {
    scrollStories.forEach((story) => {
      const rect = story.element.getBoundingClientRect();
      const travel = Math.max(story.element.offsetHeight - window.innerHeight, 1);
      const raw = Math.min(1, Math.max(0, -rect.top / travel));
      // Complete the animation before the physical scroll section ends.
      // The remaining distance acts as a deliberate hold so the final data remains visible.
      const progress = Math.min(1, raw / story.holdAt);
      story.element.style.setProperty(story.variable, progress.toFixed(4));
      document.documentElement.style.setProperty(story.variable, progress.toFixed(4));

      if (story.phases) {
        story.element.dataset.phase = progress < .28 ? 'separate' : progress < .62 ? 'connecting' : 'aligned';
      }
    });

    ticking = false;
  };

  const requestUpdate = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(update);
  };

  if (reduceMotion) {
    scrollStories.forEach((story) => {
      story.element.style.setProperty(story.variable, '1');
      document.documentElement.style.setProperty(story.variable, '1');
      if (story.phases) story.element.dataset.phase = 'aligned';
    });
  } else {
    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', requestUpdate);
    update();
  }

})();

(() => {
  const toggle = document.querySelector('.mobile-menu-toggle');
  const drawer = document.getElementById('mobileNav');
  const closeButtons = document.querySelectorAll('[data-mobile-menu-close]');
  if (!toggle || !drawer) return;

  let lastFocused = null;
  const focusable = () => Array.from(drawer.querySelectorAll('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'));
  const setOpen = (open) => {
    if (open) lastFocused = document.activeElement;
    document.body.classList.toggle('mobile-nav-open', open);
    drawer.setAttribute('aria-hidden', open ? 'false' : 'true');
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
    if (open) {
      drawer.querySelector('[data-mobile-menu-close]')?.focus();
    } else if (lastFocused instanceof HTMLElement) {
      lastFocused.focus({ preventScroll: true });
    }
  };

  toggle.addEventListener('click', () => setOpen(!document.body.classList.contains('mobile-nav-open')));
  closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
  drawer.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
  document.addEventListener('keydown', (event) => {
    if (!document.body.classList.contains('mobile-nav-open')) return;
    if (event.key === 'Escape') {
      event.preventDefault();
      setOpen(false);
      return;
    }
    if (event.key === 'Tab') {
      const items = focusable();
      if (!items.length) return;
      const first = items[0];
      const last = items[items.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault(); last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault(); first.focus();
      }
    }
  });
  window.addEventListener('resize', () => {
    if (window.innerWidth > 700) setOpen(false);
  });
})();


(() => {
  const menu = document.querySelector('.public-account-menu');
  const button = menu?.querySelector('.public-account-button');
  const dropdown = menu?.querySelector('.public-account-dropdown');
  if (!menu || !button || !dropdown) return;

  const setOpen = (open, focusFirst = false) => {
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    dropdown.hidden = !open;
    if (open && focusFirst) dropdown.querySelector('a')?.focus();
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    setOpen(button.getAttribute('aria-expanded') !== 'true');
  });
  button.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown') {
      event.preventDefault();
      setOpen(true, true);
    }
  });
  dropdown.addEventListener('keydown', (event) => {
    const links = Array.from(dropdown.querySelectorAll('a'));
    const index = links.indexOf(document.activeElement);
    if (event.key === 'ArrowDown' && links.length) {
      event.preventDefault(); links[(index + 1 + links.length) % links.length].focus();
    } else if (event.key === 'ArrowUp' && links.length) {
      event.preventDefault(); links[(index - 1 + links.length) % links.length].focus();
    } else if (event.key === 'Tab' && !event.shiftKey && document.activeElement === links[links.length - 1]) {
      setOpen(false);
    }
  });
  dropdown.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      event.preventDefault();
      setOpen(false);
      button.focus();
    }
  });
})();
