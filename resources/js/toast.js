/**
 * Toast notifications.
 *
 * `toast({ tone, title, message, duration })` renders a toast in the fixed
 * region at the bottom of the viewport. Toasts from the server arrive as JSON
 * in `#app-flash` and are replayed on load, so Blade can flash a message
 * without shipping inline JavaScript.
 */

const DEFAULT_DURATION = 5000;
const MAX_VISIBLE = 4;

const ICONS = {
    success:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>',
    error:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>',
    warning:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 9v4M12 17h.01"/><path d="M10.3 4.3 2.6 17.8A2 2 0 0 0 4.3 20.8h15.4a2 2 0 0 0 1.7-3L13.7 4.3a2 2 0 0 0-3.4 0Z"/></svg>',
    info:
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 11v5M12 8h.01"/><circle cx="12" cy="12" r="9"/></svg>',
};

const CLOSE_ICON =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>';

let region = null;

function regionNode() {
    if (region && region.isConnected) return region;

    region = document.createElement('div');
    region.id = 'toast-region';
    region.className = 'toast-region';
    region.setAttribute('role', 'region');
    region.setAttribute('aria-label', 'Notifications');
    region.setAttribute('aria-live', 'polite');
    document.body.appendChild(region);
    return region;
}

function dismiss(node) {
    if (!node || node.classList.contains('is-leaving')) return;

    node.classList.add('is-leaving');
    const finish = () => node.remove();
    node.addEventListener('animationend', finish, { once: true });
    // Animation may be disabled (reduced motion); remove regardless.
    window.setTimeout(finish, 400);
}

function buildTone(tone) {
    return Object.prototype.hasOwnProperty.call(ICONS, tone) ? tone : 'info';
}

function createToast({ tone, title, message, duration }) {
    const node = document.createElement('div');
    node.className = `toast toast--${tone}`;
    node.setAttribute('role', tone === 'error' ? 'alert' : 'status');
    node.dataset.duration = String(duration);

    const icon = document.createElement('span');
    icon.className = 'toast__icon';
    icon.innerHTML = ICONS[tone];

    const body = document.createElement('div');
    body.className = 'toast__body';

    if (title) {
        const heading = document.createElement('p');
        heading.className = 'toast__title';
        heading.textContent = title;
        body.appendChild(heading);
    }

    if (message) {
        const text = document.createElement('p');
        text.className = 'toast__message';
        text.textContent = message;
        body.appendChild(text);
    }

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'toast__close';
    close.setAttribute('aria-label', 'Dismiss notification');
    close.innerHTML = CLOSE_ICON;

    let timer = null;
    let startedAt = 0;
    let remaining = duration;
    let paused = false;

    const stop = () => {
        window.clearTimeout(timer);
        timer = null;
    };

    const schedule = () => {
        if (remaining <= 0) return;
        startedAt = performance.now();
        timer = window.setTimeout(() => dismiss(node), remaining);
    };

    const pause = () => {
        if (paused || !timer) return;
        paused = true;
        remaining -= performance.now() - startedAt;
        stop();
        node.classList.add('is-paused');
    };

    const resume = () => {
        if (!paused) return;
        paused = false;
        node.classList.remove('is-paused');
        schedule();
    };

    close.addEventListener('click', () => {
        stop();
        dismiss(node);
    });

    node.addEventListener('pointerenter', pause);
    node.addEventListener('pointerleave', resume);
    node.addEventListener('focusin', pause);
    node.addEventListener('focusout', resume);

    node.append(icon, body, close);

    if (duration > 0) {
        const progress = document.createElement('span');
        progress.className = 'toast__progress';
        progress.style.setProperty('--toast-duration', `${duration}ms`);
        node.appendChild(progress);
        schedule();
    }

    return node;
}

export function toast(options = {}) {
    const title = typeof options.title === 'string' ? options.title : '';
    const message = typeof options.message === 'string' ? options.message : '';
    if (!title && !message) return null;

    const tone = buildTone(options.tone);
    const duration =
        Number.isFinite(options.duration) && options.duration >= 0
            ? options.duration
            : tone === 'error'
              ? 7000
              : DEFAULT_DURATION;

    const host = regionNode();
    const node = createToast({ tone, title, message, duration });
    host.appendChild(node);

    // Keep the stack short: retire the oldest toast when a new one arrives.
    const toasts = Array.from(host.children);
    if (toasts.length > MAX_VISIBLE) {
        toasts.slice(0, toasts.length - MAX_VISIBLE).forEach(dismiss);
    }

    return node;
}

toast.success = (message, options = {}) => toast({ ...options, message, tone: 'success' });
toast.error = (message, options = {}) => toast({ ...options, message, tone: 'error' });
toast.warning = (message, options = {}) => toast({ ...options, message, tone: 'warning' });
toast.info = (message, options = {}) => toast({ ...options, message, tone: 'info' });

function replayFlash() {
    const holder = document.getElementById('app-flash');
    if (!holder) return;

    let payload;
    try {
        payload = JSON.parse(holder.textContent || '{}');
    } catch {
        return;
    }

    const items = Array.isArray(payload.toasts) ? payload.toasts : [];
    items.forEach((item, index) => {
        if (!item || (!item.title && !item.message)) return;
        // Stagger so a burst of flashes reads as a sequence, not a wall.
        window.setTimeout(
            () =>
                toast({
                    tone: item.tone,
                    title: item.title,
                    message: item.message,
                    duration: item.duration,
                }),
            index * 160,
        );
    });
}

window.toast = toast;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', replayFlash, { once: true });
} else {
    replayFlash();
}

export default toast;
