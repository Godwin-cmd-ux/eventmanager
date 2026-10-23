import type { GuestDisplay } from "../lib/types";

interface FailureModalProps {
  title: string;
  message: string;
  guest: GuestDisplay | null;
  onContinue: () => void;
}

export function FailureModal({
  title,
  message,
  guest,
  onContinue,
}: FailureModalProps) {
  return (
    <div className="modal-backdrop" role="dialog" aria-modal="true">
      <div className="modal modal--failure">
        <div className="modal__icon modal__icon--failure" aria-hidden="true">
          ✕
        </div>
        <h2 className="modal__title">Entry Not Granted</h2>
        <p className="modal__subtitle">{title}</p>
        <p className="modal__message">{message}</p>

        {guest && (
          <div className="guest-detail">
            <span className="guest-detail__label">Name</span>
            <span className="guest-detail__value">{guest.full_name}</span>
          </div>
        )}

        <button
          type="button"
          className="btn btn--danger btn--block"
          onClick={onContinue}
        >
          Continue Scanning
        </button>
      </div>
    </div>
  );
}