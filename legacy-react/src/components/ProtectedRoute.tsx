import { Navigate } from "react-router-dom";
import type { ReactNode } from "react";
import { useAuth } from "../context/AuthContext";
import { LoadingScreen } from "./LoadingScreen";
import { StatusNotice } from "./StatusNotice";

export function ProtectedRoute({ children }: { children: ReactNode }) {
  const { session, clerk, loading, signOut } = useAuth();

  if (loading) {
    return <LoadingScreen message="Restoring your session..." />;
  }

  if (!session) {
    return <Navigate to="/login" replace />;
  }

  if (!clerk) {
    return (
      <StatusNotice
        tone="error"
        title="No clerk record found"
        message="Your account is not linked to a clerk profile. Please contact the event organizer."
        onSignOut={signOut}
      />
    );
  }

  if (clerk.status === "pending") {
    return (
      <StatusNotice
        tone="warning"
        title="Account pending approval"
        message="Your account has been created but is waiting for organizer approval. You will be able to scan once approved."
        onSignOut={signOut}
      />
    );
  }

  if (clerk.status === "suspended") {
    return (
      <StatusNotice
        tone="error"
        title="Account suspended"
        message="Your scanning privileges have been suspended. Please contact the event organizer."
        onSignOut={signOut}
      />
    );
  }

  return <>{children}</>;
}
