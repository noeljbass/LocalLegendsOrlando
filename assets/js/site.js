const toggle = document.querySelector('.nav-toggle');
const nav = document.querySelector('.site-nav');
if (toggle && nav) toggle.addEventListener('click', () => { const open = nav.classList.toggle('open'); toggle.setAttribute('aria-expanded', String(open)); });

const interviewForm = document.querySelector('[data-interview-form]');
if (interviewForm) {
  const steps = [...interviewForm.querySelectorAll('[data-step]')];
  const progress = [...interviewForm.querySelectorAll('.interview-progress span')];
  const previous = interviewForm.querySelector('[data-previous]');
  const next = interviewForm.querySelector('[data-next]');
  const submit = interviewForm.querySelector('[data-submit]');
  const error = interviewForm.querySelector('[data-interview-error]');
  let current = 0;
  const showStep = () => {
    steps.forEach((step, index) => step.hidden = index !== current);
    progress.forEach((item, index) => item.classList.toggle('active', index === current));
    previous.hidden = current === 0;
    next.hidden = current === steps.length - 1;
    submit.hidden = current !== steps.length - 1;
  };
  next.addEventListener('click', () => {
    const fields = [...steps[current].querySelectorAll('input, textarea, select')];
    if (!fields.every(field => field.reportValidity())) return;
    error.hidden = true; current += 1; showStep();
  });
  previous.addEventListener('click', () => { current -= 1; showStep(); });
}
