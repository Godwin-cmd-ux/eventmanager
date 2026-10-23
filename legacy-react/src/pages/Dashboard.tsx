import { useCallback, useEffect, useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { fetchClerkStats } from "../lib/stats";
import type { ClerkStats } from "../lib/types";
import { LoadingScreen } from "../components/LoadingScreen";

export function DashboardPage() {
  const { clerk, signOut } = useAuth();
  const navigate = useNavigate();
  const [stats, setStats] = useState<ClerkStats | null>(null);
  const [error, setError] = useState<string | null>(null);

  const loadStats = useCallback(async () => {
    if (!clerk) return;
    try {
      setError(null);
      setStats(await fetchClerkStats(clerk.id));
    } catch {
      setError("Could not load your statistics. Please try again.");
    }
  }, [clerk]);

  useEffect(() => {
    void loadStats();
  }, [loadStats]);

  if (!clerk) return <LoadingScreen />;

  const hasScanAttempts = (stats?.total ?? 0) > 0;
  const scanLabel = hasScanAttempts ? "Continuous Scan" : "Start Scan";

  return (
    <div className="app-shell">
      <header className="app-header">
        <div>
          <p className="app-header__eyebrow">Wedding Entrance</p>
          <h1 className="app-header__title">Clerk Dashboard</h1>
        </div>
        <button
          type="button"
          className="btn btn--ghost"
          onClick={() => void signOut()}
        >
          Log out
        </button>
      </header>

      <main className="app-main">
        <p className="greeting">
          Signed in as <strong>{clerk.full_name || clerk.email}</strong>
        </p>

        {error && (
          <div className="alert alert--error" role="alert">
            {error}
          </div>
        )}

        {stats === null && !error ? (
          <LoadingScreen message="Loading statistics..." />
        ) : (
          <section className="stats-grid" aria-label="Your scan statistics">
            <StatCard label="Total Scans" value={stats?.total ?? 0} tone="neutral" />
            <StatCard
              label="Successful"
              value={stats?.successful ?? 0}
              tone="success"
            />
            <StatCard
              label="Failed"
              value={stats?.failed ?? 0}
              tone="danger"
            />
          </section>
        )}

        <button
          type="button"
          className="btn btn--primary btn--block btn--xl"
          onClick={() => navigate("/scan")}
        >
          {scanLabel}
        </button>

        <p className="hint">
          Each successful scan consumes one place on the invitation. The database
          is the final authority on every scan.
        </p>
      </main>
    </div>
  );
}

function StatCard({
  label,
  value,
  tone,
}: {
  label: string;
  value: number;
  tone: "neutral" | "success" | "danger";
}) {
  return (
    <div className={`stat-card stat-card--${tone}`}>
      <span className="stat-card__value">{value}</span>
      <span className="stat-card__label">{label}</span>
    </div>
  );
}
