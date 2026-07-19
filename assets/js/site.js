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
  let current = Math.max(0, Math.min(steps.length - 1, Number(JSON.parse(localStorage.getItem(draftKey) || '{}')._step || 0)));
  const showStep = () => {
    steps.forEach((step, index) => step.hidden = index !== current);
    progress.forEach((item, index) => item.classList.toggle('active', index === current));
    previous.hidden = current === 0;
    next.hidden = current === steps.length - 1;
    submit.hidden = current !== steps.length - 1;
    try { const draft = JSON.parse(localStorage.getItem(draftKey) || '{}'); draft._step = current; localStorage.setItem(draftKey, JSON.stringify(draft)); } catch (_) {}
  };
  next.addEventListener('click', () => {
    const fields = [...steps[current].querySelectorAll('input, textarea, select')];
    if (!fields.every(field => field.reportValidity())) return;
    error.hidden = true; current += 1; showStep();
  });
  previous.addEventListener('click', () => { current -= 1; showStep(); });

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
