import { Html5Qrcode } from 'html5-qrcode';

const FAILURE_COPY = {
    invalid_qr: {
        title: 'Invalid QR Code',
        message:
            'This code is not a valid invitation. Double-check the code shown by the guest.',
    },
    invitation_revoked: {
        title: 'Invitation Revoked',
        message:
            'This invitation has been revoked. This guest is not authorized for entry.',
    },
    scan_limit_reached: {
        title: 'Invitation Already Used',
        message:
            'This invitation has already used all of its allowed scans.',
    },
    clerk_not_authorized: {
        title: 'Not Authorized',
        message:
            'Your account is not authorized to scan. Please contact the event organizer.',
    },
    connection_error: {
        title: 'Verification Unavailable',
        message:
            'Could not reach the verification service. No entry was granted. Check your connection and try again.',
    },
};

function describeCameraError(err) {
    const name = err && err.name ? err.name : '';
    if (name === 'NotAllowedError' || name === 'PermissionDeniedError') {
        return 'Camera permission was denied. Allow camera access in your browser settings, then try again. On mobile, use the lock icon next to the address bar to enable the camera.';
    }
    if (name === 'NotFoundError') {
        return 'No camera was found on this device. A camera is required to scan QR codes.';
    }
    if (name === 'NotReadableError') {
        return 'The camera is already in use by another app. Close other apps using the camera and try again.';
    }
    return 'Could not initialize the camera. If you are testing, remember camera access requires HTTPS.';
}

function el(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
}

function guestRow(label, value) {
    const row = el('div', 'guest-detail');
    row.appendChild(el('span', 'guest-detail__label', label));
    row.appendChild(el('span', 'guest-detail__value', value));
    return row;
}

function formatTime(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    return d.toLocaleString([], { dateStyle: 'medium', timeStyle: 'short' });
}

const FAILURE_REASONS = new Set([
    'invalid_qr',
    'invitation_revoked',
    'scan_limit_reached',
    'clerk_not_authorized',
    'connection_error',
]);

class ScannerApp {
    constructor() {
        this.reader = new Html5Qrcode('qr-reader');
        this.readerElement = document.getElementById('qr-reader');
        this.status = document.getElementById('scan-status');
        this.errorCard = document.getElementById('scan-error');
        this.errorAlert = this.errorCard.querySelector('.alert');
        this.processing = false;
    }

    setStatus(text, tone = 'info') {
        this.status.textContent = text;
        this.status.className = `scan-status scan-status--${tone}`;
    }

    showError(message) {
        this.errorAlert.textContent = message;
        this.errorCard.hidden = false;
        this.status.hidden = true;
    }

    async start() {
        try {
            await this.reader.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: (viewWidth, viewHeight) => {
                        const size = Math.max(
                            160,
                            Math.min(viewWidth, viewHeight, 260),
                        );
                        return { width: size, height: size };
                    },
                },
                (decodedText) => this.onDecoded(decodedText),
                () => {
                    // Per-frame camera noise; ignored.
                },
            );
            this.setStatus("Point the frame at the guest's QR code.", 'ok');
        } catch (err) {
            this.showError(describeCameraError(err));
        }
    }

    async onDecoded(decodedText) {
        if (this.processing) return;
        this.processing = true;
        try {
            this.reader.pause(true);
        } catch {
            // Scanner may already be stopped; proceed anyway.
        }

        try {
            const response = await fetch('/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify({ qr: decodedText.trim() }),
            });

            if (!response.ok) {
                throw new Error('Verification service returned an error.');
            }

            const data = await response.json();

            if (data.result === 'success' && data.guest) {
                this.showSuccess(data.guest);
            } else {
                const reason = FAILURE_REASONS.has(data.failure_reason)
                    ? data.failure_reason
                    : 'invalid_qr';
                this.showFailure(reason, data.guest || null);
            }
        } catch {
            this.showFailure('connection_error', null);
        } finally {
            this.processing = false;
        }
    }

    buildModal(kind, content) {
        const backdrop = el('div', 'modal-backdrop');
        backdrop.setAttribute('role', 'dialog');
        backdrop.setAttribute('aria-modal', 'true');
        const modal = el('div', `modal modal--${kind}`);
        backdrop.appendChild(modal);
        content(modal);
        return backdrop;
    }

    showSuccess(guest) {
        const backdrop = this.buildModal('success', (modal) => {
            modal.appendChild(
                el('div', 'modal__icon modal__icon--success', '\u2713'),
            );
            modal.appendChild(el('h2', 'modal__title', 'Guest Verified'));
            modal.appendChild(el('p', 'modal__subtitle', 'SUCCESS'));
            modal.appendChild(guestRow('Name', guest.full_name));
            modal.appendChild(
                guestRow('Invitation', guest.invitation_type.toUpperCase()),
            );
            modal.appendChild(
                guestRow(
                    'Successful scans',
                    `${guest.successful_scans} of ${guest.allowed_scans}`,
                ),
            );
            modal.appendChild(
                guestRow('Remaining scans', guest.remaining_scans),
            );
            const when = formatTime(guest.scanned_at);
            if (when) {
                modal.appendChild(guestRow('Scanned at', when));
            }
            const button = el('button', 'btn btn--success btn--block', 'Continue Scanning');
            button.type = 'button';
            button.addEventListener('click', () => this.resume(backdrop));
            modal.appendChild(button);
        });
        document.body.appendChild(backdrop);
    }

    showFailure(reason, guest) {
        const copy = FAILURE_COPY[reason] || FAILURE_COPY.invalid_qr;
        const backdrop = this.buildModal('failure', (modal) => {
            modal.appendChild(
                el('div', 'modal__icon modal__icon--failure', '\u2715'),
            );
            modal.appendChild(el('h2', 'modal__title', 'Entry Not Granted'));
            modal.appendChild(el('p', 'modal__subtitle', copy.title));
            modal.appendChild(el('p', 'modal__message', copy.message));
            if (guest) {
                modal.appendChild(guestRow('Name', guest.full_name));
            }
            const button = el('button', 'btn btn--danger btn--block', 'Continue Scanning');
            button.type = 'button';
            button.addEventListener('click', () => this.resume(backdrop));
            modal.appendChild(button);
        });
        document.body.appendChild(backdrop);
    }

    resume(backdrop) {
        backdrop.remove();
        try {
            this.reader.resume();
        } catch {
            // Scanner may have stopped; the status below is still correct.
        }
        this.setStatus("Point the frame at the guest's QR code.", 'ok');
    }
}

const app = new ScannerApp();
app.start();