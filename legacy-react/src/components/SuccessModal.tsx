import type { GuestDisplay } from "../lib/types";

interface SuccessModalProps {
  guest: GuestDisplay;
  onContinue: () => void;
}

function formatTime(value?: string): string {
  if (!value) return "";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "";
  return d.toLocaleString([], {
    dateStyle: "medium",
    timeStyle: "short",
  });
}

export function SuccessModal({ guest, onContinue }: SuccessModalProps) {
  return (
    <div className="modal-backdrop" role="dialog" aria-modal="true">
      <div className="modal modal--success">
        <div className="modal__icon modal__icon--success" aria-hidden="true">
          ✓
        </div>
        <h2 className="modal__title">Guest Verified</h2>
        <p className="modal__subtitle">SUCCESS</p>

        <div className="guest-detail">
          <span className="guest-detail__label">Name</span>
          <span className="guest-detail__value">{guest.full_name}</span>
        </div>
        <div className="guest-detail">
          <span className="guest-detail__label">Invitation</span>
          <span className="guest-detail__value">
            {guest.invitation_type.toUpperCase()}
          </span>
        </div>
        <div className="guest-detail">
          <span className="guest-detail__label">Successful scans</span>
          <span className="guest-detail__value">
            {guest.successful_scans} of {guest.allowed_scans}
          </span>
        </div>
        <div className="guest-detail">
          <span className="guest-detail__label">Remaining scans</span>
          <span className="guest-detail__value">{guest.remaining_scans}</span>
        </div>

        {formatTime(guest.scanned_at) && (
          <div className="guest-detail">
            <span className="guest-detail__label">Scanned at</span>
            <span className="guest-detail__value">{formatTime(guest.scanned_at)}</span>
          </div>
        )}

        <button
          type="button"
          className="btn btn--success btn--block"
          onClick={onContinue}
        >
          Continue Scanning
        </button>
      </div>
    </div>
  );
}