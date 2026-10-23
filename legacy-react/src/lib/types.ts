export type ClerkStatus = "active" | "pending" | "suspended";

export interface Clerk {
  id: string;
  full_name: string;
  email: string;
  status: ClerkStatus;
}

export type ScanFailureReason =
  | "invalid_qr"
  | "invitation_revoked"
  | "scan_limit_reached"
  | "clerk_not_authorized"
  | "connection_error";

export interface GuestDisplay {
  id: string;
  full_name: string;
  invitation_type: "single" | "double";
  successful_scans: number;
  allowed_scans: number;
  remaining_scans: number;
  scanned_at?: string;
}

export interface VerifyScanResponse {
  result: "success" | "failed";
  failure_reason: ScanFailureReason | null;
  guest: GuestDisplay | null;
}

export interface ClerkStats {
  total: number;
  successful: number;
  failed: number;
}
