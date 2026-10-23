import { toast } from './toast';

/**
 * App-wide progressive enhancement. Everything here degrades to plain HTML if
 * JavaScript is unavailable, so the forms keep working without it.
 */

document.documentElement.classList.add('js');

// Give every submit a visible pending state instead of a dead-feeling tap.
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || form.dataset.submitting === 'true') {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    if (!button) return;

    form.dataset.submitting = 'true';
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.classList.add('is-loading');

    // Icon-only buttons keep their glyph; the CSS spinner replaces it.
    if (!button.classList.contains('icon-btn')) {
        button.dataset.label = button.textContent;
        button.textContent = 'Working…';
    }
});

export { toast };
