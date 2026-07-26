(() => {
  const navItems = document.querySelectorAll('[data-page]');
  const pages = document.querySelectorAll('.page');
  const sidebar = document.getElementById('sidebar');
  const menuButton = document.getElementById('menuButton');
  const sidebarClose = document.getElementById('sidebarClose');
  const sidebarBackdrop = document.getElementById('sidebarBackdrop');

  const setSidebarOpen = (open) => {
    sidebar?.classList.toggle('open', open);
    document.body.classList.toggle('sidebar-open', open);
    menuButton?.setAttribute('aria-expanded', open ? 'true' : 'false');
    menuButton?.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  };

  const closeSidebar = () => setSidebarOpen(false);

  const showPage = (id) => {
    pages.forEach((page) => page.classList.toggle('active', page.id === `page-${id}`));
    navItems.forEach((item) => item.classList.toggle('active', item.dataset.page === id));
    closeSidebar();
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const requestedView = new URLSearchParams(window.location.search).get('view');
  if (requestedView && document.getElementById(`page-${requestedView}`)) showPage(requestedView);

  navItems.forEach((item) => item.addEventListener('click', () => showPage(item.dataset.page)));
  document.querySelectorAll('[data-jump]').forEach((button) => button.addEventListener('click', () => showPage(button.dataset.jump)));
  menuButton?.addEventListener('click', () => setSidebarOpen(!sidebar?.classList.contains('open')));
  sidebarClose?.addEventListener('click', closeSidebar);
  sidebarBackdrop?.addEventListener('click', closeSidebar);

  const workspaceMenu = document.getElementById('workspaceMenuModal');
  const workspaceMenuButton = document.getElementById('workspaceMenuButton');
  const closeWorkspaceMenu = () => {
    workspaceMenu?.classList.remove('open');
    workspaceMenu?.setAttribute('aria-hidden', 'true');
    workspaceMenuButton?.setAttribute('aria-expanded', 'false');
  };
  workspaceMenuButton?.addEventListener('click', () => {
    const opening = !workspaceMenu?.classList.contains('open');
    workspaceMenu?.classList.toggle('open', opening);
    workspaceMenu?.setAttribute('aria-hidden', opening ? 'false' : 'true');
    workspaceMenuButton.setAttribute('aria-expanded', opening ? 'true' : 'false');
  });
  document.querySelectorAll('[data-close-workspace-menu]').forEach((button) => button.addEventListener('click', closeWorkspaceMenu));
  document.querySelectorAll('.workspace-menu-item').forEach((item) => item.addEventListener('click', closeWorkspaceMenu));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeWorkspaceMenu();
      closeSidebar();
    }
  });
  window.addEventListener('resize', () => { if (window.innerWidth > 760) closeSidebar(); });
})();

(() => {
  const resumeProfile = {
    name: 'David Evans',
    location: 'Phoenix, Arizona',
    headline: 'Founder, SaaS Product Builder, Commerce Systems Designer and Business Development Leader',
    summary: 'Product-focused founder with more than 20 years of experience across web design, graphic design, ecommerce operations, CRM development, customer lifecycle design, business development and small-business commerce infrastructure.',
    experience: [
      'Founder of Microgifter, an AI-assisted social gifting, merchant CRM, loyalty, campaign, rewards and automated commerce platform.',
      'Business Development and Founder at VP3 Media Corp / VP3.ME since May 2024.',
      'Ecommerce listing and catalog management at Kodi Distributing, including a catalog exceeding 100,000 SKUs across storefronts such as Amazon.',
      'Client Services Manager at Timeshare Attorneys of America, covering new-client processing, discovery, customer service, office operations and CRM development and administration.',
      'More than 20 years serving local and corporate businesses through web design, graphic design, digital marketing, ecommerce systems, CRM workflows and operational problem solving.'
    ],
    capabilities: ['SaaS product strategy', 'PHP web applications', 'MySQL data systems', 'API integration', 'CRM architecture', 'Ecommerce operations', 'Customer lifecycle design', 'AI-assisted workflows', 'UI/UX direction', 'Business development', 'Investor positioning', 'Project QA']
  };

  const agents = {
    executive: {
      title: 'Executive Briefing', icon: '✦',
      description: 'Summarizes enterprise risks, opportunities and decisions using approved procurement and inventory context.',
      prompt: 'You are the Gruber Executive Briefing Agent. Analyze only the approved sample procurement, supplier, inventory, savings and project records available in this prototype. Distinguish facts from assumptions. Cite the records used in each answer. Recommend actions, but never execute transactions. Flag decisions that require human approval.'
    },
    supplier: {
      title: 'Supplier Intelligence', icon: '◇',
      description: 'Evaluates supplier performance, contracts, concentration, pricing and consolidation opportunities.',
      prompt: 'You are the Gruber Supplier Intelligence Agent. Use only approved supplier scorecards, purchase history, contracts and quality records. Compare performance fairly, identify uncertainty, and explain the evidence behind every recommendation. Never award, remove or contact a supplier. Route decisions to Purchasing leadership.'
    },
    inventory: {
      title: 'Inventory Intelligence', icon: '□',
      description: 'Finds excess, aging, stockout exposure and cross-company transfer opportunities.',
      prompt: 'You are the Gruber Inventory Intelligence Agent. Analyze approved item, location, demand, aging and work-order records. Protect strategic legacy inventory from automatic obsolete classification. Recommend transfers or reviews but never move, reserve, write off or purchase inventory.'
    },
    procurement: {
      title: 'Procurement Risk', icon: '↗',
      description: 'Prioritizes open purchase orders, late deliveries, approvals and operational impact.',
      prompt: 'You are the Gruber Procurement Risk Agent. Review approved purchase-order, supplier acknowledgement, required-date and work-order context. Rank risks by customer, service, production and project impact. Recommend escalation paths, but never issue, modify, cancel or approve a purchase order.'
    },
    savings: {
      title: 'Savings Opportunity', icon: '$',
      description: 'Surfaces pricing, freight, inventory and process improvements for Accounting validation.',
      prompt: 'You are the Gruber Savings Opportunity Agent. Identify evidence-based cost, cash and process opportunities. Separate expected, implemented and realized savings. Adjust for volume, specifications and service changes. Never claim realized savings without Accounting validation.'
    },
    data: {
      title: 'Data Quality', icon: '⌁',
      description: 'Detects duplicate suppliers, missing fields and records that weaken reporting or AI analysis.',
      prompt: 'You are the Gruber Data Quality Agent. Inspect approved supplier, item, purchase-order and inventory records for duplicates, missing fields, invalid values and inconsistent naming. Propose merges or corrections with confidence levels. Never alter source records automatically.'
    }
  };

  const responseFor = (agent, query) => {
    const q = query.toLowerCase();
    if (q.includes('resume') || q.includes('résumé') || q.includes('david evans') || q.includes('my background') || q.includes('my experience') || q.includes('qualifications')) {
      return {
        html: `<p><strong>${resumeProfile.name}</strong> is a ${resumeProfile.headline.toLowerCase()} based in ${resumeProfile.location}. ${resumeProfile.summary}</p><p><strong>Most relevant experience:</strong></p><ul>${resumeProfile.experience.map((item) => `<li>${item}</li>`).join('')}</ul><p><strong>Fit for the Gruber initiative:</strong> his background is strongest where product strategy, systems integration, ecommerce data, CRM design, interface direction and hands-on delivery meet. That supports the program-lead and product-build side of an AI procurement transformation while procurement policy, finance validation and operational approvals remain with Gruber subject-matter leaders.</p><p><a class="chat-inline-link" href="../resume.php">Open the full résumé →</a></p>`,
        evidence: ['David Evans résumé', 'Microgifter founder work', 'VP3 Media Corp / VP3.ME', 'Kodi Distributing · 100,000+ SKUs', 'Timeshare Attorneys of America', '20+ years in web, design and commerce']
      };
    }
    if (q.includes('top') || q.includes('leadership') || q.includes('brief')) {
      return { html: '<p><strong>Three items deserve leadership review:</strong></p><ul><li><strong>Service risk:</strong> PO GPS-10428 is nine days late and may affect two battery-service commitments.</li><li><strong>Avoidable purchase:</strong> Gruber Communications has 1,800 feet of cable available while Technical Services is requesting 1,200 feet.</li><li><strong>Supplier data risk:</strong> three supplier records appear to represent one legal entity, weakening spend visibility.</li></ul>', evidence: ['PO GPS-10428', 'GC Phoenix inventory', 'GTS open request', 'Supplier duplicate scan'] };
    }
    if (q.includes('transfer') || q.includes('inventory')) {
      return { html: '<p>The clearest transfer candidate is <strong>1,200 feet of cable from Gruber Communications to Gruber Technical Services</strong>. The source location shows 1,800 feet available and the receiving request has a six-day required date.</p><p>Human review should confirm specification, reserved demand and transfer freight before a new PO is released.</p>', evidence: ['GC: 1,800 ft available', 'GTS: 1,200 ft request', '$14K modeled avoidance'] };
    }
    if (q.includes('supplier')) {
      return { html: '<p><strong>Immediate attention:</strong> Critical Power Battery Supply because a past-due order may affect customer service. <strong>Performance review:</strong> Legacy Electronics Components because on-time delivery is 72.6% and quality acceptance is 92.8%.</p><p>Recommended next action: validate open commitments, review alternative sources and prepare a supplier corrective-action discussion.</p>', evidence: ['Supplier scorecard', 'Open PO queue', 'On-time delivery history'] };
    }
    if (q.includes('saving') || q.includes('cost') || q.includes('opportun')) {
      return { html: '<p>The current sample pipeline shows four high-value themes: battery demand consolidation, internal inventory transfers, service-parts pricing, and inbound freight consolidation.</p><p>Combined expected value is illustrative until volume and Accounting baselines are confirmed. The next best step is to validate the battery category as the Gruber Power pilot.</p>', evidence: ['$286K sample pipeline', '$64K validated', '12 active opportunities'] };
    }
    if (q.includes('data') || q.includes('duplicate')) {
      return { html: '<p>The data-quality queue contains <strong>three probable duplicate supplier records</strong>. Similar legal names, domains and billing addresses indicate they should be reviewed as one enterprise supplier.</p><p>No merge should occur until Accounting validates open balances, tax records and payment details.</p>', evidence: ['Supplier master scan', 'Domain match', 'Billing address match'] };
    }
    return { html: `<p>I reviewed the simulated ${agents[agent].title.toLowerCase()} context for your question.</p><p>The strongest current themes are a late battery order, a cross-company cable transfer, supplier-master duplication and rising expedite costs. I would next narrow the analysis by company, supplier, category, date range or work order.</p>`, evidence: ['Sample procurement data', 'Sample inventory snapshot', 'Sample supplier scorecards'] };
  };

  const choices = document.querySelectorAll('.agent-choice');
  const stage = document.getElementById('agentChatStage');
  const form = document.getElementById('agentChatForm');
  const input = document.getElementById('agentChatInput');
  if (!stage || !form || !input) return;
  let activeAgent = 'executive';

  const setAgent = (key) => {
    activeAgent = key;
    const a = agents[key];
    choices.forEach((c) => c.classList.toggle('active', c.dataset.agent === key));
    const promptNode = document.getElementById('systemPrompt');
    const sidebarAgentName = document.getElementById('sidebarAgentName');
    const composerAgentName = document.getElementById('composerAgentName');
    if (promptNode) promptNode.textContent = a.prompt;
    if (sidebarAgentName) sidebarAgentName.textContent = a.title;
    if (composerAgentName) composerAgentName.textContent = a.title;
    input.placeholder = `Ask the ${a.title} Agent…`;
  };

  const appendUser = (text) => {
    const node = document.createElement('div');
    node.className = 'chat-message user-message';
    node.innerHTML = `<div class="message-avatar">DE</div><div class="message-body"><span>David Evans</span><p></p></div>`;
    node.querySelector('p').textContent = text;
    stage.insertBefore(node, document.getElementById('promptSuggestions'));
  };

  const appendAssistant = (query) => {
    const typing = document.createElement('div');
    typing.className = 'chat-message assistant-message';
    typing.innerHTML = `<div class="message-avatar">AI</div><div class="message-body"><span>${agents[activeAgent].title} Agent</span><div class="typing-dots"><i></i><i></i><i></i></div></div>`;
    stage.insertBefore(typing, document.getElementById('promptSuggestions'));
    stage.scrollIntoView({ behavior: 'smooth', block: 'end' });
    window.setTimeout(() => {
      const result = responseFor(activeAgent, query);
      typing.querySelector('.message-body').innerHTML = `<span>${agents[activeAgent].title} Agent</span>${result.html}<div class="evidence-row"><b>Evidence used</b>${result.evidence.map((e) => `<span>${e}</span>`).join('')}</div>`;
      typing.scrollIntoView({ behavior: 'smooth', block: 'end' });
    }, 700);
  };

  const send = (text) => {
    const clean = text.trim();
    if (!clean) return;
    appendUser(clean);
    appendAssistant(clean);
    input.value = '';
    input.style.height = 'auto';
  };

  choices.forEach((choice) => choice.addEventListener('click', () => setAgent(choice.dataset.agent)));
  document.querySelectorAll('[data-agent-prompt]').forEach((button) => button.addEventListener('click', () => send(button.dataset.agentPrompt)));
  form.addEventListener('submit', (event) => { event.preventDefault(); send(input.value); });
  input.addEventListener('input', () => { input.style.height = 'auto'; input.style.height = `${Math.min(input.scrollHeight, 120)}px`; });
  input.addEventListener('keydown', (event) => { if (event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); send(input.value); } });
  document.getElementById('clearAgentChat')?.addEventListener('click', () => {
    stage.querySelectorAll('.chat-message:not(:first-of-type)').forEach((node) => node.remove());
  });
  document.getElementById('newAgentThread')?.addEventListener('click', () => {
    stage.querySelectorAll('.chat-message:not(:first-of-type)').forEach((node) => node.remove());
    setAgent('executive');
  });
})();


(() => {
  const center = document.getElementById('notificationCenter');
  const button = document.getElementById('notificationButton');
  const dropdown = document.getElementById('notificationDropdown');
  const markRead = document.getElementById('markNotificationsRead');
  if (!center || !button || !dropdown) return;

  const close = () => {
    dropdown.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  };

  const toggle = () => {
    const willOpen = dropdown.hidden;
    dropdown.hidden = !willOpen;
    button.setAttribute('aria-expanded', String(willOpen));
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    toggle();
  });
  dropdown.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', close);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
  });
  markRead?.addEventListener('click', () => {
    center.classList.add('read');
    button.setAttribute('aria-label', 'Open notifications');
  });
  dropdown.querySelectorAll('[data-jump]').forEach((item) => item.addEventListener('click', close));
})();


(() => {
  const menu = document.getElementById('userMenu');
  const button = document.getElementById('userMenuButton');
  const dropdown = document.getElementById('userDropdown');
  if (!menu || !button || !dropdown) return;

  const close = () => {
    dropdown.hidden = true;
    button.setAttribute('aria-expanded', 'false');
  };

  const toggle = () => {
    const opening = dropdown.hidden;
    dropdown.hidden = !opening;
    button.setAttribute('aria-expanded', String(opening));
  };

  button.addEventListener('click', (event) => {
    event.stopPropagation();
    toggle();
  });
  dropdown.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', close);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close();
  });
  dropdown.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
  dropdown.querySelectorAll('[data-prototype-logout]').forEach((logout) => {
    logout.addEventListener('click', () => {
      try {
        localStorage.removeItem('gruberAgentChat');
      } catch (error) {
        // Local storage may be blocked; logout still returns to the public page.
      }
      window.location.href = '../index.php?logged_out=1';
    });
  });
})();

(() => {
  const form = document.getElementById('settingsForm');
  if (!form) return;
  const saveState = document.getElementById('settingsSaveState');
  const storageKey = 'gruberWorkspaceSettings';

  const formValue = (element) => element.type === 'checkbox' ? element.checked : element.value;
  const applyValue = (element, value) => {
    if (element.type === 'checkbox') element.checked = Boolean(value);
    else if (typeof value === 'string') element.value = value;
  };

  try {
    const saved = JSON.parse(localStorage.getItem(storageKey) || '{}');
    Array.from(form.elements).forEach((element) => {
      if (element.name && Object.prototype.hasOwnProperty.call(saved, element.name)) applyValue(element, saved[element.name]);
    });
  } catch (error) {
    // Keep the page defaults when browser storage is unavailable or invalid.
  }

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const settings = {};
    Array.from(form.elements).forEach((element) => {
      if (element.name) settings[element.name] = formValue(element);
    });
    try {
      localStorage.setItem(storageKey, JSON.stringify(settings));
    } catch (error) {
      // The prototype remains usable even if local storage is disabled.
    }
    saveState?.classList.add('visible');
    window.setTimeout(() => saveState?.classList.remove('visible'), 2200);
  });
})();
