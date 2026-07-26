(() => {
  'use strict';

  const qs = (selector, root = document) => root.querySelector(selector);
  const qsa = (selector, root = document) => Array.from(root.querySelectorAll(selector));

  const setHidden = (element, hidden) => {
    if (!element) return;
    element.hidden = hidden;
    element.setAttribute('aria-hidden', hidden ? 'true' : 'false');
  };

  const focusableElements = (root) => root ? qsa('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', root).filter((element) => !element.hidden && element.offsetParent !== null) : [];
  const trapTabKey = (event, root) => {
    if (event.key !== 'Tab' || !root) return;
    const items = focusableElements(root);
    if (!items.length) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault(); last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault(); first.focus();
    }
  };

  const closeDropdowns = (exceptId = '') => {
    qsa('.dropdown-panel').forEach((panel) => {
      if (panel.id !== exceptId) setHidden(panel, true);
    });
    qsa('[data-dropdown-toggle]').forEach((button) => {
      if (button.dataset.dropdownToggle !== exceptId) button.setAttribute('aria-expanded', 'false');
    });
  };

  qsa('[data-dropdown-toggle]').forEach((button) => {
    const id = button.dataset.dropdownToggle;
    const panel = document.getElementById(id);
    if (!panel) return;
    button.setAttribute('aria-controls', id);
    const open = (focusFirst = false) => {
      closeDropdowns(id);
      setHidden(panel, false);
      button.setAttribute('aria-expanded', 'true');
      if (focusFirst) focusableElements(panel)[0]?.focus();
    };
    button.addEventListener('click', (event) => {
      event.stopPropagation();
      if (panel.hidden) open(false); else closeDropdowns();
    });
    button.addEventListener('keydown', (event) => {
      if (event.key === 'ArrowDown') { event.preventDefault(); open(true); }
    });
    panel.addEventListener('keydown', (event) => {
      const items = focusableElements(panel);
      const index = items.indexOf(document.activeElement);
      if (event.key === 'ArrowDown' && items.length) { event.preventDefault(); items[(index + 1 + items.length) % items.length].focus(); }
      if (event.key === 'ArrowUp' && items.length) { event.preventDefault(); items[(index - 1 + items.length) % items.length].focus(); }
      if (event.key === 'Escape') { event.preventDefault(); closeDropdowns(); button.focus(); }
    });
  });

  document.addEventListener('click', (event) => {
    if (!event.target.closest('.dropdown')) closeDropdowns();
  });

  const sidebar = qs('#appSidebar');
  let sidebarOpener = null;
  const openSidebar = (event) => {
    if (!sidebar) return;
    sidebarOpener = event?.currentTarget || document.activeElement;
    sidebar.classList.add('is-open');
    document.body.classList.add('sidebar-open');
    qsa('[data-sidebar-open]').forEach((button) => button.setAttribute('aria-expanded', 'true'));
    if (window.matchMedia('(max-width: 820px)').matches) sidebar.querySelector('[data-sidebar-close]')?.focus();
  };
  const closeSidebar = (restoreFocus = true) => {
    if (!sidebar) return;
    sidebar.classList.remove('is-open');
    document.body.classList.remove('sidebar-open');
    qsa('[data-sidebar-open]').forEach((button) => button.setAttribute('aria-expanded', 'false'));
    if (restoreFocus && sidebarOpener instanceof HTMLElement) sidebarOpener.focus({ preventScroll: true });
  };
  qsa('[data-sidebar-open]').forEach((button) => button.addEventListener('click', openSidebar));
  qsa('[data-sidebar-close]').forEach((button) => button.addEventListener('click', closeSidebar));
  qs('.sidebar-backdrop')?.addEventListener('click', closeSidebar);


  const sidebarAccordion = qs('[data-sidebar-accordion]');
  if (sidebarAccordion) {
    const sections = qsa('[data-nav-section]', sidebarAccordion);
    const storageKey = 'gruber-sidebar-section';
    const setSectionExpanded = (section, expanded) => {
      const button = qs('[data-nav-section-toggle]', section);
      const panel = qs('.nav-section-panel', section);
      if (!button || !panel) return;
      button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      panel.hidden = !expanded;
    };
    const openSection = (section, persist = true) => {
      sections.forEach((candidate) => setSectionExpanded(candidate, candidate === section));
      if (persist && section?.dataset.navSection) {
        try { window.localStorage.setItem(storageKey, section.dataset.navSection); } catch (_) {}
      }
    };

    const activeSection = sections.find((section) => qs('.nav-link.active', section));
    let initialSection = activeSection;
    if (!initialSection) {
      let saved = '';
      try { saved = window.localStorage.getItem(storageKey) || ''; } catch (_) {}
      initialSection = sections.find((section) => section.dataset.navSection === saved)
        || sections.find((section) => section.dataset.navSection === 'workspace')
        || sections[0];
    }
    if (initialSection) openSection(initialSection, false);

    sections.forEach((section) => {
      const button = qs('[data-nav-section-toggle]', section);
      button?.addEventListener('click', () => {
        const expanded = button.getAttribute('aria-expanded') === 'true';
        if (expanded) {
          setSectionExpanded(section, false);
          try { window.localStorage.removeItem(storageKey); } catch (_) {}
          return;
        }
        openSection(section);
      });
    });
  }

  const modalOpeners = new WeakMap();
  const openModal = (id, opener = document.activeElement) => {
    const modal = document.getElementById(id);
    if (!modal) return;
    const dialog = qs('[role="dialog"], .modal-card', modal);
    if (dialog) {
      dialog.setAttribute('role', 'dialog');
      dialog.setAttribute('aria-modal', 'true');
      if (!dialog.hasAttribute('aria-label') && !dialog.hasAttribute('aria-labelledby')) {
        const heading = qs('h1,h2,h3', dialog);
        if (heading) {
          if (!heading.id) heading.id = `${id}Title`;
          dialog.setAttribute('aria-labelledby', heading.id);
        }
      }
    }
    if (opener instanceof HTMLElement) modalOpeners.set(modal, opener);
    setHidden(modal, false);
    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
    window.setTimeout(() => focusableElements(dialog || modal)[0]?.focus({ preventScroll: true }), 30);
  };

  const closeModal = (modal, restoreFocus = true) => {
    if (!modal) return;
    modal.classList.remove('is-open');
    setHidden(modal, true);
    if (!qs('.modal.is-open')) document.body.classList.remove('modal-open');
    const opener = modalOpeners.get(modal);
    if (restoreFocus && opener instanceof HTMLElement) opener.focus({ preventScroll: true });
  };

  qsa('[data-modal-open]').forEach((button) => {
    button.addEventListener('click', () => openModal(button.dataset.modalOpen, button));
  });
  qsa('[data-modal-close]').forEach((button) => {
    button.addEventListener('click', () => closeModal(button.closest('.modal')));
  });
  qsa('.modal[data-auto-open="true"]').forEach((modal) => openModal(modal.id));

  document.addEventListener('keydown', (event) => {
    const openModalNode = qs('.modal.is-open');
    if (openModalNode) trapTabKey(event, qs('[role="dialog"], .modal-card', openModalNode) || openModalNode);
    else if (document.body.classList.contains('history-open')) trapTabKey(event, historyDrawer);
    else if (document.body.classList.contains('sidebar-open') && window.matchMedia('(max-width: 820px)').matches) trapTabKey(event, sidebar);
    if (event.key === 'Escape') {
      if (openModalNode) closeModal(openModalNode);
      closeSidebar();
      closeDropdowns();
      closeHistory();
    }
  });

  qsa('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Continue?')) event.preventDefault();
    });
  });

  qsa('[data-dismiss-flash]').forEach((button) => {
    button.addEventListener('click', () => {
      const flash = button.closest('.flash');
      if (!flash) return;
      flash.classList.add('is-dismissed');
      window.setTimeout(() => flash.remove(), 180);
    });
  });

  const historyDrawer = qs('#historyDrawer');
  let historyOpener = null;
  function openHistory(event) {
    if (!historyDrawer) return;
    historyOpener = event?.currentTarget || document.activeElement;
    setHidden(historyDrawer, false);
    historyDrawer.classList.add('is-open');
    document.body.classList.add('history-open');
    window.setTimeout(() => focusableElements(historyDrawer)[0]?.focus({ preventScroll: true }), 20);
  }
  function closeHistory(restoreFocus = true) {
    if (!historyDrawer) return;
    historyDrawer.classList.remove('is-open');
    setHidden(historyDrawer, true);
    document.body.classList.remove('history-open');
    if (restoreFocus && historyOpener instanceof HTMLElement) historyOpener.focus({ preventScroll: true });
  }
  qsa('[data-history-toggle]').forEach((button) => button.addEventListener('click', openHistory));
  qsa('[data-history-close]').forEach((button) => button.addEventListener('click', closeHistory));

  const preferencesForm = qs('[data-preferences-form]');
  if (preferencesForm) {
    const storageKey = 'gruberProcurementPreferences';
    const density = preferencesForm.elements.namedItem('density');
    const landing = preferencesForm.elements.namedItem('landing');
    const reduceMotion = preferencesForm.elements.namedItem('reduce_motion');
    const status = qs('[data-preferences-status]');

    try {
      const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
      if (saved.density && density) density.value = saved.density;
      if (saved.landing && landing) landing.value = saved.landing;
      if (reduceMotion) reduceMotion.checked = Boolean(saved.reduceMotion);
      document.documentElement.dataset.density = saved.density || 'comfortable';
      document.documentElement.classList.toggle('reduce-motion', Boolean(saved.reduceMotion));
    } catch (_) {
      // Browser storage is optional; the server application remains functional without it.
    }

    preferencesForm.addEventListener('submit', (event) => {
      event.preventDefault();
      const prefs = {
        density: density?.value || 'comfortable',
        landing: landing?.value || 'dashboard',
        reduceMotion: Boolean(reduceMotion?.checked),
      };
      try {
        localStorage.setItem(storageKey, JSON.stringify(prefs));
        document.documentElement.dataset.density = prefs.density;
        document.documentElement.classList.toggle('reduce-motion', prefs.reduceMotion);
        if (status) status.textContent = 'Preferences saved in this browser.';
      } catch (_) {
        if (status) status.textContent = 'Browser storage is unavailable.';
      }
    });
  }

  try {
    const saved = JSON.parse(localStorage.getItem('gruberProcurementPreferences') || '{}');
    document.documentElement.dataset.density = saved.density || 'comfortable';
    document.documentElement.classList.toggle('reduce-motion', Boolean(saved.reduceMotion));
  } catch (_) {
    // Ignore unavailable or malformed browser storage.
  }

  const demoToast = (message) => {
    let toast = qs('.demo-action-toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.className = 'demo-action-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(Number(toast.dataset.timer || 0));
    const timer = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
    toast.dataset.timer = String(timer);
  };


  qsa('[data-print-page]').forEach((button) => button.addEventListener('click', () => window.print()));


  const agentDataNode = qs('#agentDemoData');
  const agentForm = qs('[data-agent-form]');
  const agentInput = qs('[data-agent-input]');
  const agentMessages = qs('[data-agent-messages]');
  let agentPrompts = [];
  if (agentDataNode) {
    try {
      agentPrompts = JSON.parse(agentDataNode.textContent || '[]');
    } catch (_) {
      agentPrompts = [];
    }
  }

  const addAgentMessage = (kind, title, text) => {
    if (!agentMessages) return;
    const article = document.createElement('article');
    article.className = `message ${kind}`;
    const badge = document.createElement('span');
    badge.textContent = kind === 'assistant' ? 'AI' : 'YOU';
    const body = document.createElement('div');
    const strong = document.createElement('strong');
    strong.textContent = title;
    const paragraph = document.createElement('p');
    paragraph.textContent = text;
    body.append(strong, paragraph);
    article.append(badge, body);
    agentMessages.appendChild(article);
    article.scrollIntoView({ behavior: document.documentElement.classList.contains('reduce-motion') ? 'auto' : 'smooth', block: 'end' });
  };

  const respondToPrompt = (prompt, response) => {
    const clean = String(prompt || '').trim();
    if (!clean) return;
    addAgentMessage('user', 'Procurement question', clean);
    window.setTimeout(() => {
      addAgentMessage(
        'assistant',
        'Supervised sample response',
        response || 'I can review only the fictional records visible to this demo role and company scope. A human reviewer must validate evidence before any approval or production action.'
      );
    }, document.documentElement.classList.contains('reduce-motion') ? 0 : 280);
  };

  qsa('[data-agent-suggestion], [data-agent-thread]').forEach((button) => {
    button.addEventListener('click', () => {
      const index = Number(button.dataset.agentSuggestion ?? button.dataset.agentThread ?? -1);
      const item = agentPrompts[index];
      if (!item) return;
      qsa('[data-agent-thread]').forEach((thread) => thread.classList.toggle('active', thread === button));
      respondToPrompt(item.prompt, item.response);
    });
  });

  if (agentForm && agentInput) {
    const submitAgent = () => {
      const text = agentInput.value.trim();
      if (!text) return;
      agentInput.value = '';
      agentInput.style.height = '';
      const lower = text.toLowerCase();
      const matched = agentPrompts.find((item) => {
        const titleWords = String(item.title).toLowerCase().split(/\s+/).filter((word) => word.length > 4);
        return titleWords.some((word) => lower.includes(word));
      });
      respondToPrompt(text, matched?.response || 'The current demo scope contains fictional supplier, item, purchase-order, inventory, savings, scorecard, import, approval, and audit records. I would first identify the relevant entity, cite its sample evidence, and route any proposed change to the required human reviewer.');
    };
    agentForm.addEventListener('submit', (event) => {
      event.preventDefault();
      submitAgent();
    });
    agentInput.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        submitAgent();
      }
    });
    agentInput.addEventListener('input', () => {
      agentInput.style.height = 'auto';
      agentInput.style.height = `${Math.min(agentInput.scrollHeight, 160)}px`;
    });
  }

  qsa('table[data-table]').forEach((table) => {
    qsa('th[data-sort]', table).forEach((heading) => {
      heading.tabIndex = 0;
      heading.setAttribute('role', 'button');
      const sort = () => {
        const body = qs('tbody', table);
        if (!body) return;
        const index = Array.from(heading.parentElement.children).indexOf(heading);
        const direction = heading.dataset.direction === 'asc' ? 'desc' : 'asc';
        const rows = qsa('tr', body);
        rows.sort((a, b) => {
          const aValue = a.children[index]?.dataset.sortValue || a.children[index]?.textContent.trim() || '';
          const bValue = b.children[index]?.dataset.sortValue || b.children[index]?.textContent.trim() || '';
          return aValue.localeCompare(bValue, undefined, { numeric: true, sensitivity: 'base' }) * (direction === 'asc' ? 1 : -1);
        });
        rows.forEach((row) => body.appendChild(row));
        qsa('th[data-sort]', table).forEach((th) => delete th.dataset.direction);
        heading.dataset.direction = direction;
      };
      heading.addEventListener('click', sort);
      heading.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          sort();
        }
      });
    });
  });
})();

// Agent Workspace: evidence-grounded supervised responses, actions, browser history, and chat canvas.
(() => {
  'use strict';

  const dataNode = document.getElementById('agentWorkspaceData');
  const stage = document.getElementById('agentChatStage');
  const suggestions = document.getElementById('agentPromptSuggestions');
  const form = document.getElementById('agentChatForm');
  const input = document.getElementById('agentChatInput');
  const modal = document.getElementById('agentQuickModal');
  const menuButton = document.getElementById('agentQuickMenuButton');
  if (!dataNode || !stage || !form || !input || !modal || !menuButton) return;

  let workspace = { user_name: 'Gruber User', prompts: [] };
  try {
    workspace = JSON.parse(dataNode.textContent || '{}');
  } catch (_) {
    workspace = { user_name: 'Gruber User', prompts: [] };
  }
  const prompts = Array.isArray(workspace.prompts) ? workspace.prompts : [];
  const lookups = Array.isArray(workspace.lookups) ? workspace.lookups : [];
  const policy = workspace.policy || {};
  const historyKey = `gruberAgentHistory:${workspace.environment || 'unknown'}:${workspace.user_name || 'user'}:${workspace.scope || 'scope'}`;
  let threadMessages = [];
  let activeThreadId = null;
  const reduceMotion = () => document.documentElement.classList.contains('reduce-motion');

  const setModalOpen = (open) => {
    modal.classList.toggle('is-open', open);
    modal.setAttribute('aria-hidden', open ? 'false' : 'true');
    menuButton.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('modal-open', open);
    if (open) window.setTimeout(() => modal.querySelector('.agent-quick-card')?.focus({ preventScroll: true }), 30);
  };

  menuButton.addEventListener('click', () => setModalOpen(!modal.classList.contains('is-open')));
  modal.querySelectorAll('[data-agent-quick-close]').forEach((button) => button.addEventListener('click', () => setModalOpen(false)));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal.classList.contains('is-open')) setModalOpen(false);
  });

  const insertBeforeSuggestions = (node) => {
    if (suggestions && suggestions.parentNode === stage) stage.insertBefore(node, suggestions);
    else stage.appendChild(node);
  };

  const scrollMessageIntoView = (node) => {
    window.requestAnimationFrame(() => node.scrollIntoView({ behavior: reduceMotion() ? 'auto' : 'smooth', block: 'end' }));
  };

  const appendUserMessage = (text) => {
    const message = document.createElement('div');
    message.className = 'agent-chat-message user-message';
    const avatar = document.createElement('div');
    avatar.className = 'agent-message-avatar';
    avatar.textContent = String(workspace.user_name || 'User').split(/\s+/).map((part) => part[0] || '').join('').slice(0, 2).toUpperCase() || 'U';
    const body = document.createElement('div');
    body.className = 'agent-message-body';
    const label = document.createElement('span');
    label.textContent = workspace.user_name || 'Gruber User';
    const paragraph = document.createElement('p');
    paragraph.textContent = text;
    body.append(label, paragraph);
    message.append(avatar, body);
    insertBeforeSuggestions(message);
    return message;
  };

  const appendAssistantTyping = (title) => {
    const message = document.createElement('div');
    message.className = 'agent-chat-message assistant-message';
    message.innerHTML = '<div class="agent-message-avatar">AI</div><div class="agent-message-body"><span></span><div class="agent-typing"><i></i><i></i><i></i></div></div>';
    message.querySelector('.agent-message-body>span').textContent = title || 'Executive Briefing Agent';
    insertBeforeSuggestions(message);
    scrollMessageIntoView(message);
    return message;
  };

  const escapeHtml = (value) => String(value ?? '').replace(/[&<>"']/g, (char) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
  const escapeAttribute = (value) => escapeHtml(value).replace(/`/g, '&#096;');

  const actionCardsHtml = (actions) => {
    if (!Array.isArray(actions) || !actions.length) return '';
    return `<div class="agent-action-section"><b>Recommended next actions</b><div class="agent-action-grid">${actions.map((action) => {
      const icon = escapeHtml(action.icon || '→');
      const label = escapeHtml(action.label || 'Open action');
      const description = escapeHtml(action.description || 'Continue in the supervised workflow');
      if (action.type === 'prompt' && action.prompt) {
        return `<button type="button" class="agent-action-card" data-agent-action-prompt="${escapeAttribute(action.prompt)}"><i>${icon}</i><span><strong>${label}</strong><small>${description}</small></span><em>Ask agent</em></button>`;
      }
      const href = escapeAttribute(action.href || '#');
      const external = /^(mailto:|https?:)/i.test(action.href || '') ? ' target="_blank" rel="noopener"' : '';
      return `<a class="agent-action-card" href="${href}"${external}><i>${icon}</i><span><strong>${label}</strong><small>${description}</small></span><em>Open</em></a>`;
    }).join('')}</div></div>`;
  };

  const renderAssistantResponse = (message, item) => {
    const body = message.querySelector('.agent-message-body');
    if (!body) return;
    const evidence = Array.isArray(item.evidence) ? item.evidence : [];
    const evidenceHtml = evidence.length
      ? `<div class="agent-evidence-row"><b>Evidence used</b>${evidence.map((value) => `<span>${escapeHtml(value)}</span>`).join('')}</div>`
      : '';
    const meta = `<div class="agent-response-meta"><span>Confidence: ${escapeHtml(item.confidence || 'Moderate')}</span><span>${escapeHtml(item.generated_at || policy.generated_at || '')}</span><span>${escapeHtml(item.human_review || policy.human_review || 'Human review required')}</span></div>`;
    const evidenceDrawer = evidence.length ? `<details class="agent-evidence-drawer"><summary>Evidence and provenance (${evidence.length})</summary><div>${evidence.map((value) => `<span>${escapeHtml(value)}</span>`).join('')}</div><small>${escapeHtml(policy.environment || '')} · ${escapeHtml(workspace.scope || '')}</small></details>` : '';
    const tools = '<div class="agent-response-tools"><button type="button" data-agent-copy>Copy response</button><button type="button" data-agent-export>Export note</button></div>';
    body.innerHTML = `<span></span>${item.response_html || '<p>I can review approved procurement, inventory, supplier, savings, approval and reporting records and prepare an evidence-based response for human review.</p>'}${meta}${evidenceDrawer}${tools}${actionCardsHtml(item.actions)}`;
    body.querySelector(':scope > span').textContent = item.response_title || 'Executive Briefing Agent';
    message.dataset.agentResponse = JSON.stringify({ title: item.response_title || 'Executive Briefing Agent', text: body.innerText, evidence, generated_at: item.generated_at || policy.generated_at || '' });
    scrollMessageIntoView(message);
  };

  const fallbackFor = (query) => {
    const lower = query.toLowerCase();
    const keywordSets = [
      ['resume', 'résumé', 'background', 'experience'],
      ['saving', 'cost', 'avoidance', 'opportunit'],
      ['quality', 'duplicate', 'normalize', 'data'],
      ['supplier', 'vendor', 'delivery'],
      ['transfer', 'inventory', 'stock'],
      ['brief', 'executive', 'leadership', 'risk'],
    ];
    for (const words of keywordSets) {
      const found = prompts.find((item) => words.some((word) => `${item.title || ''} ${item.prompt || ''}`.toLowerCase().includes(word) && lower.includes(word)));
      if (found) return found;
    }
    return {
      response_title: 'Executive Briefing Agent',
      response_html: '<p>I can analyze the approved Gruber procurement context across purchase orders, suppliers, inventory, savings, approvals and reporting.</p><p>To make the response more precise, include a company, supplier, category, item, purchase order, work order or date range. Any proposed operational change remains subject to human review.</p>',
      evidence: ['Current company scope', 'Approved application records', 'Human-supervision policy'],
    };
  };

  const normalizeAgentText = (value) => String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9$%.-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim();

  const lookupFor = (query) => {
    const clean = normalizeAgentText(query);
    if (!clean) return null;
    const ranked = [];
    lookups.forEach((item) => {
      (Array.isArray(item.aliases) ? item.aliases : []).forEach((alias) => {
        const normalizedAlias = normalizeAgentText(alias);
        if (!normalizedAlias) return;
        const exact = clean === normalizedAlias;
        const boundary = (` ${clean} `).includes(` ${normalizedAlias} `);
        const contained = normalizedAlias.length >= 5 && clean.includes(normalizedAlias);
        if (exact || boundary || contained) ranked.push({ item, score: (exact ? 1000 : boundary ? 500 : 100) + normalizedAlias.length });
      });
    });
    ranked.sort((a, b) => b.score - a.score);
    return ranked[0]?.item || null;
  };

  const findPrompt = (index, query) => {
    if (Number.isInteger(index) && prompts[index]) return prompts[index];

    const lookup = lookupFor(query);
    if (lookup) return lookup;

    const clean = normalizeAgentText(query);
    let winner = null;
    let winnerScore = 0;
    prompts.forEach((item) => {
      const terms = [
        ...(Array.isArray(item.keywords) ? item.keywords : []),
        item.title || '',
        item.prompt || '',
      ];
      let score = 0;
      terms.forEach((term) => {
        const normalizedTerm = normalizeAgentText(term);
        if (!normalizedTerm) return;
        if (clean === normalizedTerm) score += 100;
        else if (clean.includes(normalizedTerm)) score += Math.max(12, normalizedTerm.length);
        else normalizedTerm.split(' ').filter((word) => word.length >= 4).forEach((word) => {
          if (clean.includes(word)) score += 3;
        });
      });
      if (score > winnerScore) {
        winner = item;
        winnerScore = score;
      }
    });
    return winnerScore >= 3 ? winner : fallbackFor(query);
  };

  const send = (query, requestedIndex = null) => {
    const clean = String(query || '').trim();
    if (!clean) return;
    const item = findPrompt(Number.isInteger(requestedIndex) ? requestedIndex : null, clean);
    const userMessage = appendUserMessage(clean);
    const assistantMessage = appendAssistantTyping(item.response_title);
    scrollMessageIntoView(userMessage);
    input.value = '';
    input.style.height = '';
    window.setTimeout(() => { renderAssistantResponse(assistantMessage, item); threadMessages.push({ query: clean }); saveThread(); }, reduceMotion() ? 0 : 520);
  };

  document.querySelectorAll('[data-agent-quick-index]').forEach((button) => {
    button.addEventListener('click', () => {
      const index = Number(button.dataset.agentQuickIndex);
      const item = prompts[index];
      if (!item) return;
      setModalOpen(false);
      send(item.prompt || item.title || '', index);
    });
  });

  document.querySelectorAll('[data-agent-query]').forEach((button) => {
    button.addEventListener('click', () => send(button.dataset.agentQuery || button.textContent || ''));
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    send(input.value);
  });
  stage.addEventListener('click', (event) => {
    const action = event.target.closest('[data-agent-action-prompt]'); if(action){send(action.dataset.agentActionPrompt || '');return;}
    const message=event.target.closest('.assistant-message'); if(!message)return; const payload=message.dataset.agentResponse||'';
    if(event.target.closest('[data-agent-copy]')){navigator.clipboard?.writeText(message.querySelector('.agent-message-body')?.innerText||'');return;}
    if(event.target.closest('[data-agent-export]')){const blob=new Blob([payload||message.innerText],{type:'text/plain'});const url=URL.createObjectURL(blob);const link=document.createElement('a');link.href=url;link.download='gruber-agent-note.txt';link.click();URL.revokeObjectURL(url);}
  });

  input.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      send(input.value);
    }
  });
  input.addEventListener('input', () => {
    input.style.height = 'auto';
    input.style.height = `${Math.min(input.scrollHeight, 130)}px`;
  });


  const sanitizeHistory = (value) => {
    if (!Array.isArray(value)) return [];
    return value.slice(0, 12).map((thread) => {
      const messages = Array.isArray(thread?.messages)
        ? thread.messages.map((entry) => ({ query: String(entry?.query || '').trim().slice(0, 2000) })).filter((entry) => entry.query)
        : [];
      return {
        id: String(thread?.id || ''),
        title: String(thread?.title || messages[0]?.query || 'Agent conversation').slice(0, 80),
        updated_at: String(thread?.updated_at || ''),
        messages: messages.slice(0, 40),
      };
    }).filter((thread) => thread.messages.length);
  };
  const readHistory = () => {
    try { return sanitizeHistory(JSON.parse(localStorage.getItem(historyKey) || '[]')); } catch (_) { return []; }
  };
  const writeHistory = (history) => { try { localStorage.setItem(historyKey, JSON.stringify(sanitizeHistory(history))); } catch (_) {} };
  const renderHistory = () => {
    const list = document.getElementById('agentHistoryList'); const count = document.getElementById('agentHistoryCount');
    const history = readHistory(); if (count) count.textContent = String(history.length);
    if (!list) return; list.innerHTML = '';
    if (!history.length) { list.innerHTML = '<div class="dropdown-empty">No saved Agent conversations in this browser.</div>'; return; }
    history.forEach((thread, index) => { const button=document.createElement('button'); button.type='button'; button.className='history-thread-button'; const strong=document.createElement('strong'); strong.textContent=thread.title || 'Agent conversation'; const small=document.createElement('small'); small.textContent=thread.updated_at || ''; button.append(strong,small); button.addEventListener('click',()=>restoreThread(index)); list.appendChild(button); });
  };
  const saveThread = () => {
    if (!threadMessages.length) return;
    const history = readHistory();
    if (!activeThreadId) activeThreadId = `thread-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    const record = {
      id: activeThreadId,
      title: String(threadMessages[0]?.query || 'Agent conversation').slice(0, 80),
      updated_at: new Date().toLocaleString(),
      messages: threadMessages.map((entry) => ({ query: String(entry?.query || '').trim().slice(0, 2000) })).filter((entry) => entry.query),
    };
    const existingIndex = history.findIndex((entry) => entry.id === activeThreadId);
    if (existingIndex >= 0) history.splice(existingIndex, 1);
    history.unshift(record);
    writeHistory(history);
    renderHistory();
  };
  const restoreThread = (index) => {
    const thread = readHistory()[index];
    if (!thread) return;
    resetChat();
    activeThreadId = thread.id || `thread-${Date.now()}`;
    threadMessages = [];
    (thread.messages || []).forEach((entry) => {
      const query = String(entry.query || '').trim();
      if (!query) return;
      const item = findPrompt(null, query);
      appendUserMessage(query);
      const node = appendAssistantTyping(item.response_title);
      renderAssistantResponse(node, item);
      threadMessages.push({ query });
    });
    document.querySelector('[data-history-close]')?.click();
  };
  renderHistory();

  const resetChat = () => {
    stage.querySelectorAll('.agent-chat-message:not([data-agent-initial])').forEach((node) => node.remove());
    stage.scrollTop = 0; threadMessages = []; activeThreadId = null;
  };
  document.getElementById('clearAgentChat')?.addEventListener('click', () => {
    resetChat();
    setModalOpen(false);
  });
  document.getElementById('newAgentThread')?.addEventListener('click', () => {
    resetChat();
    setModalOpen(false);
    input.focus({ preventScroll: true });
  });

  const initialPrompt = new URLSearchParams(window.location.search).get('prompt');
  if (initialPrompt) window.setTimeout(() => send(initialPrompt), reduceMotion() ? 0 : 220);
})();


// Demo guided tour: cross-page presentation coach with highlighted source areas.
(() => {
  'use strict';
  const node = document.getElementById('guidedTourData');
  if (!node) return;
  let config = { steps: [], tour_url: '' };
  try { config = JSON.parse(node.textContent || '{}'); } catch (_) { return; }
  const steps = Array.isArray(config.steps) ? config.steps : [];
  const params = new URLSearchParams(window.location.search);
  const requested = Number(params.get('tour_step') || 0);
  if (!requested || !steps[requested - 1]) return;
  const step = steps[requested - 1];
  const expected = new URL(step.href, window.location.href);
  if (expected.pathname !== window.location.pathname) {
    window.location.assign(step.href);
    return;
  }
  const target = document.querySelector(step.selector) || document.querySelector('.page-heading') || document.querySelector('.app-content');
  if (!target) return;
  target.classList.add('guided-tour-highlight');
  const coach = document.createElement('aside');
  coach.className = 'guided-tour-coach';
  coach.setAttribute('role', 'dialog');
  coach.setAttribute('aria-label', `Guided tour step ${requested}`);
  const previous = steps[requested - 2];
  const next = steps[requested];
  coach.innerHTML = `
    <div class="guided-tour-coach-head"><span>GUIDED DEMO · ${requested} OF ${steps.length}</span><a href="${config.tour_url}" aria-label="Exit guided tour">×</a></div>
    <div class="guided-tour-coach-progress"><i style="width:${Math.round((requested / steps.length) * 100)}%"></i></div>
    <small>${step.eyebrow || ''}</small><h2>${step.title || ''}</h2><p>${step.description || ''}</p>
    <div class="guided-tour-coach-actions">${previous ? `<a class="button secondary" href="${previous.href}">← Previous</a>` : '<span></span>'}${next ? `<a class="button primary" href="${next.href}">Next →</a>` : `<a class="button primary" href="${config.tour_url}">Finish tour</a>`}</div>`;
  document.body.appendChild(coach);
  window.setTimeout(() => target.scrollIntoView({ behavior: document.documentElement.classList.contains('reduce-motion') ? 'auto' : 'smooth', block: 'center' }), 80);
})();
