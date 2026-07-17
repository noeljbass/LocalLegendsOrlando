const toggle = document.querySelector('.nav-toggle');
const nav = document.querySelector('.site-nav');
if (toggle && nav) toggle.addEventListener('click', () => { const open = nav.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(open)); });

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
  try {
    const draft = JSON.parse(localStorage.getItem(key) || '{}');
    Object.entries(draft).forEach(([name, value]) => {
      const field = form.elements.namedItem(name);
      if (!field || field.type === 'file') return;
      if (field.type === 'checkbox') field.checked = Boolean(value);
      else field.value = value;
    });
  } catch (_) { localStorage.removeItem(key); }
  const saveDraft = () => {
    const draft = {};
    [...form.elements].forEach((field) => {
      if (!field.name || field.type === 'file' || field.type === 'hidden') return;
      draft[field.name] = field.type === 'checkbox' ? field.checked : field.value;
    });
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
}
