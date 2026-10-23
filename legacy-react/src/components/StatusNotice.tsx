interface StatusNoticeProps {
  tone: "warning" | "error";
  title: string;
  message: string;
  onSignOut?: () => void | Promise<void>;
}

export function StatusNotice({
  tone,
  title,
  message,
  onSignOut,
}: StatusNoticeProps) {
  return (
    <div className="center-layout">
      <div className={`card notice notice--${tone}`}>
        <div className={`notice__badge notice__badge--${tone}`} aria-hidden="true">
          !
        </div>
        <h1 className="notice__title">{title}</h1>
        <p className="notice__message">{message}</p>
        {onSignOut && (
          <button
            type="button"
            className="btn btn--secondary btn--block"
            onClick={() => void onSignOut()}
          >
            Sign out
          </button>
        )}
      </div>
    </div>
  );
}
