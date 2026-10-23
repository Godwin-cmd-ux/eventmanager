import { useCallback, useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Html5Qrcode } from "html5-qrcode";
import { supabase } from "../lib/supabase";
import { useAuth } from "../context/AuthContext";
import type { GuestDisplay, ScanFailureReason, VerifyScanResponse } from "../lib/types";
import { SuccessModal } from "../components/SuccessModal";
import { FailureModal } from "../components/FailureModal";

type CameraState = "initializing" | "scanning" | "error";

interface ModalState {
  kind: "success" | "failure";
  guest: GuestDisplay | null;
  title: string;
  message: string;
}

const FAILURE_COPY: Record<ScanFailureReason, { title: string; message: string }> = {
  invalid_qr: {
    title: "Invalid QR Code",
    message: "This code is not a valid invitation. Double-check the code shown by the guest.",
  },
  invitation_revoked: {
    title: "Invitation Revoked",
    message: "This invitation has been revoked. This guest is not authorized for entry.",
  },
  scan_limit_reached: {
    title: "Invitation Already Used",
    message: "This invitation has already used all of its allowed scans.",
  },
  clerk_not_authorized: {
    title: "Not Authorized",
    message: "Your account is not authorized to scan. Please contact the event organizer.",
  },
  connection_error: {
    title: "Verification Unavailable",
    message: "Could not reach the verification service. No entry was granted. Check your connection and try again.",
  },
};

function describeCameraError(err: unknown): string {
  const name = (err as { name?: string })?.name ?? "";
  if (name === "NotAllowedError" || name === "PermissionDeniedError") {
    return "Camera permission was denied. Allow camera access in your browser settings, then try again. On mobile, use the lock icon next to the address bar to enable the camera.";
  }
  if (name === "NotFoundError") {
    return "No camera was found on this device. A camera is required to scan QR codes.";
  }
  if (name === "NotReadableError") {
    return "The camera is already in use by another app. Close other apps using the camera and try again.";
  }
  if (typeof err === "string") return err;
  return "Could not initialize the camera. If you are testing, remember camera access requires HTTPS.";
}

export function ScannerPage() {
  const { clerk } = useAuth();
  const navigate = useNavigate();
  const readerRef = useRef<HTMLDivElement>(null);
  const scannerRef = useRef<Html5Qrcode | null>(null);
  const processingRef = useRef(false);

  const [cameraState, setCameraState] = useState<CameraState>("initializing");
  const [cameraError, setCameraError] = useState<string | null>(null);
  const [modal, setModal] = useState<ModalState | null>(null);

  const handleScanResult = useCallback(async (decodedText: string) => {
    if (processingRef.current) return;
    processingRef.current = true;

    const scanner = scannerRef.current;
    if (scanner) {
      try {
        scanner.pause(true);
      } catch {
        // Scanner may already be stopped; proceed anyway.
      }
    }

    try {
      const payload = decodedText.trim();
      const { data, error } = await supabase.rpc("verify_guest_scan", {
        p_guest_id: payload,
      });

      if (error) throw error;

      const res = data as VerifyScanResponse | null;
      if (!res) {
        throw new Error("Empty response from verification service.");
      }

      if (res.result === "success" && res.guest) {
        setModal({
          kind: "success",
          guest: res.guest,
          title: "SUCCESS",
          message: "Guest verified.",
        });
      } else {
        const reason: ScanFailureReason = res.failure_reason ?? "invalid_qr";
        const copy = FAILURE_COPY[reason] ?? FAILURE_COPY.invalid_qr;
        setModal({
          kind: "failure",
          guest: res.guest,
          title: copy.title,
          message: copy.message,
        });
      }
    } catch {
      const copy = FAILURE_COPY.connection_error;
      setModal({
        kind: "failure",
        guest: null,
        title: copy.title,
        message: copy.message,
      });
    } finally {
      processingRef.current = false;
    }
  }, []);

  useEffect(() => {
    if (!readerRef.current) return;
    let cancelled = false;

    const scanner = new Html5Qrcode(readerRef.current.id);
    scannerRef.current = scanner;

    const start = async () => {
      try {
        await scanner.start(
          { facingMode: "environment" },
          {
            fps: 10,
            qrbox: (viewWidth: number, viewHeight: number) => {
              const size = Math.max(160, Math.min(viewWidth, viewHeight, 260));
              return { width: size, height: size };
            },
          },
          (decodedText) => void handleScanResult(decodedText),
          () => {
            // Per-frame camera noise; ignored.
          },
        );
        if (!cancelled) {
          setCameraState("scanning");
        }
      } catch (err) {
        if (!cancelled) {
          setCameraState("error");
          setCameraError(describeCameraError(err));
        }
      }
    };

    void start();

    return () => {
      cancelled = true;
      const current = scannerRef.current;
      scannerRef.current = null;
      if (current) {
        current
          .stop()
          .catch(() => undefined)
          .finally(() => {
            try {
              current.clear();
            } catch {
              // Nothing left to clear.
            }
          });
      }
    };
  }, [handleScanResult]);

  const resume = useCallback(() => {
    const scanner = scannerRef.current;
    setModal(null);
    if (scanner) {
      try {
        scanner.resume();
      } catch {
        // Scanner may have stopped; the state below is still correct.
      }
    }
    setCameraState("scanning");
  }, []);

  const stopScanning = useCallback(() => {
    const scanner = scannerRef.current;
    if (scanner) {
      scanner
        .stop()
        .catch(() => undefined)
        .finally(() => navigate("/", { replace: true }));
    } else {
      navigate("/", { replace: true });
    }
  }, [navigate]);

  return (
    <div className="app-shell app-shell--scan">
      <header className="app-header">
        <div>
          <p className="app-header__eyebrow">
            Scanning as {clerk?.full_name || ""}
          </p>
          <h1 className="app-header__title">Scan Invitation</h1>
        </div>
        <button type="button" className="btn btn--ghost" onClick={stopScanning}>
          Stop
        </button>
      </header>

      <main className="app-main app-main--scan">
        <div id="qr-reader" ref={readerRef} className="qr-reader" />

        {cameraState === "initializing" && (
          <p className="scan-status scan-status--info">Requesting camera access...</p>
        )}

        {cameraState === "scanning" && (
          <p className="scan-status scan-status--ok">
            Point the frame at the guest's QR code.
          </p>
        )}

        {cameraState === "error" && (
          <div className="card">
            <div className="alert alert--error" role="alert">
              {cameraError}
            </div>
            <div className="btn-row">
              <button
                type="button"
                className="btn btn--primary"
                onClick={() => window.location.reload()}
              >
                Try Again
              </button>
              <button
                type="button"
                className="btn btn--secondary"
                onClick={stopScanning}
              >
                Back to Dashboard
              </button>
            </div>
          </div>
        )}

        <div className="scan-footer">
          <button
            type="button"
            className="btn btn--secondary btn--block"
            onClick={stopScanning}
          >
            Back to Dashboard
          </button>
        </div>
      </main>

      {modal?.kind === "success" && modal.guest && (
        <SuccessModal guest={modal.guest} onContinue={resume} />
      )}
      {modal?.kind === "failure" && (
        <FailureModal
          title={modal.title}
          message={modal.message}
          guest={modal.guest}
          onContinue={resume}
        />
      )}
    </div>
  );
}