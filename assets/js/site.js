const toggle = document.querySelector('.nav-toggle');
const nav = document.querySelector('.site-nav');
if (toggle && nav) toggle.addEventListener('click', () => { const open = nav.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(open)); });

// Browser autofill and password managers routinely fill visually hidden
// honeypot fields, which made real users' submissions fail. Clear honeypots
// just before any form submits so only non-JS bots ever trip them.
document.querySelectorAll('form').forEach((form) => {
  form.addEventListener('submit', () => {
    form.querySelectorAll('.honeypot input, .honeypot textarea').forEach((field) => { field.value = ''; });
  });
});

const submissionForm = document.querySelector('[data-submission-form]');
if (submissionForm) {
  submissionForm.addEventListener('submit', () => {
    const button = submissionForm.querySelector('button[type="submit"], button:not([type])');
    if (!button || !submissionForm.checkValidity()) return;
    button.disabled = true;
    button.setAttribute('aria-disabled', 'true');
    button.dataset.originalText = button.textContent;
    button.textContent = 'Submitting…';
  });
}


const clearDraft = document.querySelector('[data-clear-draft-key]');
if (clearDraft) localStorage.removeItem(`form-draft:${clearDraft.dataset.clearDraftKey}`);

document.querySelectorAll('[data-draft-form]').forEach((form) => {
  const key = `form-draft:${form.dataset.draftForm}`;
  const isHoneypot = (field) => field.closest('.honeypot') !== null || field.name === 'company' || field.name === 'topic_extra';
  try {
    const draft = JSON.parse(localStorage.getItem(key) || '{}');
    // Purge honeypot values previously saved by older versions of this script:
    // once autofilled, they were restored on every visit and permanently
    // blocked submissions from this browser.
    if ('company' in draft || 'topic_extra' in draft) {
      delete draft.company;
      delete draft.topic_extra;
      localStorage.setItem(key, JSON.stringify(draft));
    }
    Object.entries(draft).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);
      if (!field || field.type === 'file' || isHoneypot(field)) return;
      if (field.type === 'checkbox') field.checked = Boolean(value);
      else field.value = value;
    });
  } catch (_) { localStorage.removeItem(key); }
  const saveDraft = () => {
    const draft = {};
    [...form.elements].forEach((field) => {
      if (!field.name || field.type === 'file' || field.type === 'hidden' || isHoneypot(field)) return;
      draft[field.name] = field.type === 'checkbox' ? field.checked : field.value;
    });
    try { const existing = JSON.parse(localStorage.getItem(key) || '{}'); if (existing._step !== undefined) draft._step = existing._step; } catch (_) {}
    localStorage.setItem(key, JSON.stringify(draft));
  };
  form.addEventListener('input', saveDraft);
  form.addEventListener('change', saveDraft);
});

const interviewForm = document.querySelector('[data-interview-form]');
if (interviewForm) {
  const steps = [...interviewForm.querySelectorAll('[data-step]')];
  const progress = [...interviewForm.querySelectorAll('.interview-progress span')];
  const previous = interviewForm.querySelector('[data-previous]');
  const next = interviewForm.querySelector('[data-next]');
  const submit = interviewForm.querySelector('[data-submit]');
  const error = interviewForm.querySelector('[data-interview-error]');
  const draftKey = `form-draft:${interviewForm.dataset.draftForm}`;
  let current = 0;
  try { current = Math.max(0, Math.min(steps.length - 1, Number(JSON.parse(localStorage.getItem(draftKey) || '{}')._step || 0))); } catch (_) { localStorage.removeItem(draftKey); }
  const showStep = () => {
    steps.forEach((step, index) => step.hidden = index !== current);
    progress.forEach((item, index) => item.classList.toggle('active', index === current));
    previous.hidden = current === 0;
    const isLastStep = current === steps.length - 1;
    next.hidden = isLastStep;
    next.setAttribute('aria-hidden', String(isLastStep));
    submit.hidden = !isLastStep;
    submit.setAttribute('aria-hidden', String(!isLastStep));
    try { const draft = JSON.parse(localStorage.getItem(draftKey) || '{}'); draft._step = current; localStorage.setItem(draftKey, JSON.stringify(draft)); } catch (_) {}
  };
  showStep();
  next.addEventListener('click', () => {
    const fields = [...steps[current].querySelectorAll('input, textarea, select')];
    if (!fields.every(field => field.reportValidity())) return;
    error.hidden = true; current = Math.min(current + 1, steps.length - 1); showStep();
  });
  previous.addEventListener('click', () => { current = Math.max(current - 1, 0); showStep(); });

  const showError = (message) => { error.textContent = message; error.hidden = false; };

  // Browsers refuse to submit a form containing an invalid control inside a
  // hidden fieldset, and they do it silently (the control cannot be focused).
  // Validate every step ourselves, jump to the first problem, and explain it.
  interviewForm.addEventListener('submit', (event) => {
    const invalid = [...interviewForm.querySelectorAll('input, textarea, select')].find((field) => !field.disabled && !field.checkValidity());
    if (invalid) {
      event.preventDefault();
      const stepIndex = steps.findIndex((step) => step.contains(invalid));
      if (stepIndex !== -1 && stepIndex !== current) { current = stepIndex; showStep(); }
      invalid.reportValidity();
      showError('Please complete the highlighted answer before submitting.');
      return;
    }
    const files = [...interviewForm.querySelectorAll('input[type="file"]')].flatMap((input) => [...(input.files || [])]);
    if (files.some((file) => file.size > 5 * 1024 * 1024)) {
      event.preventDefault();
      showError('One of your photos is larger than 5 MB. Please choose a smaller version and try again — your written answers are saved.');
      return;
    }
    if (files.reduce((total, file) => total + file.size, 0) > 25 * 1024 * 1024) {
      event.preventDefault();
      showError('Your photos add up to more than 25 MB. Please remove one or two and try again — your written answers are saved.');
      return;
    }
    error.hidden = true;
    submit.disabled = true;
    submit.setAttribute('aria-disabled', 'true');
    submit.textContent = 'Submitting…';
  });
}

const articleContent = document.querySelector('[data-article-content]');
if (articleContent) {
  document.querySelectorAll('[data-insert-article-html]').forEach((button) => {
    button.addEventListener('click', () => {
      const snippet = button.dataset.insertArticleHtml || '';
      const start = articleContent.selectionStart ?? articleContent.value.length;
      const end = articleContent.selectionEnd ?? articleContent.value.length;
      const prefix = start > 0 && !articleContent.value.slice(0, start).endsWith('\n') ? '\n' : '';
      const suffix = end < articleContent.value.length && !articleContent.value.slice(end).startsWith('\n') ? '\n' : '';
      articleContent.setRangeText(`${prefix}${snippet}${suffix}`, start, end, 'end');
      articleContent.focus();
      articleContent.dispatchEvent(new Event('input', { bubbles: true }));
    });
  });
}

const articleEditor = document.querySelector('[data-article-editor]');
if (articleEditor) {
  const slugField = articleEditor.elements.namedItem('slug');
  const titleField = articleEditor.elements.namedItem('title');
  const publicTypeField = articleEditor.elements.namedItem('public_type');
  const emptyMessage = articleEditor.querySelector('[data-badge-empty]');
  const badgeContent = articleEditor.querySelector('[data-badge-content]');
  const htmlField = articleEditor.querySelector('[data-badge-html]');
  const urlField = articleEditor.querySelector('[data-badge-url]');
  const preview = articleEditor.querySelector('[data-badge-preview]');
  const siteUrl = (articleEditor.dataset.siteUrl || '').replace(/\/+$/, '');
  const logoUrl = articleEditor.dataset.badgeLogoUrl || '';
  const slugifyForBadge = (value) => {
    const slug = String(value || '').replace(/['’‘`]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    return slug;
  };
  const escapeAttribute = (value) => String(value || '').replace(/[&<>"]/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[char]));
  const publicPath = (slug, publicType) => `${publicType === 'article' ? 'article' : 'story'}/${slug}/`;
  const buildTrackedUrl = (slug, publicType) => {
    const base = `${siteUrl}/${publicPath(slug, publicType)}`;
    const params = new URLSearchParams({
      utm_source: 'featured_business_website',
      utm_medium: 'referral',
      utm_campaign: 'featured_on_badge',
      utm_content: slug,
    });
    return `${base}${base.includes('?') ? '&' : '?'}${params.toString()}`;
  };
  const buildBadgeHtml = (trackedUrl) => `<div style="text-align:center;">
    <div style="margin-bottom:8px; font-size:14px; line-height:1.4;">
        Featured on
    </div>

    <a href="${escapeAttribute(trackedUrl)}" target="_blank" rel="noopener noreferrer" aria-label="Read our feature on Local Legends Orlando">
        <img src="${escapeAttribute(logoUrl)}" alt="Featured on Local Legends Orlando" style="display:block; width:200px; max-width:100%; height:auto; margin:0 auto; border:0;">
    </a>
</div>`;
  const updateBadge = () => {
    const slug = slugifyForBadge(slugField?.value || titleField?.value || '');
    if (!slug) {
      if (emptyMessage) emptyMessage.hidden = false;
      if (badgeContent) badgeContent.hidden = true;
      return;
    }
    const trackedUrl = buildTrackedUrl(slug, publicTypeField?.value || 'story');
    const badgeHtml = buildBadgeHtml(trackedUrl);
    if (htmlField) htmlField.value = badgeHtml;
    if (urlField) urlField.value = trackedUrl;
    if (preview) preview.innerHTML = badgeHtml;
    if (emptyMessage) emptyMessage.hidden = true;
    if (badgeContent) badgeContent.hidden = false;
  };
  [slugField, titleField, publicTypeField].forEach((field) => {
    if (!field) return;
    field.addEventListener('input', updateBadge);
    field.addEventListener('change', updateBadge);
  });
  articleEditor.querySelectorAll('[data-copy-target]').forEach((button) => {
    button.addEventListener('click', async () => {
      const target = button.dataset.copyTarget === 'badge-url' ? urlField : htmlField;
      if (!target) return;
      const originalText = button.textContent;
      try {
        await navigator.clipboard.writeText(target.value);
      } catch (_) {
        target.focus(); target.select(); document.execCommand('copy');
      }
      button.textContent = 'Copied!';
      setTimeout(() => { button.textContent = originalText; }, 2000);
    });
  });
  updateBadge();
}
