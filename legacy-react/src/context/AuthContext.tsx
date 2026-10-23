import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import type { Session } from "@supabase/supabase-js";
import { supabase } from "../lib/supabase";
import type { Clerk } from "../lib/types";

interface AuthContextValue {
  session: Session | null;
  clerk: Clerk | null;
  loading: boolean;
  signOut: () => Promise<void>;
  refreshClerk: () => Promise<void>;
}

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

async function fetchClerk(userId: string): Promise<Clerk | null> {
  const { data, error } = await supabase
    .from("clerks")
    .select("id, full_name, email, status")
    .eq("auth_user_id", userId)
    .maybeSingle();

  if (error) {
    throw error;
  }
  return data ?? null;
}

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<Session | null>(null);
  const [clerk, setClerk] = useState<Clerk | null>(null);
  const [loading, setLoading] = useState(true);

  const loadClerkForSession = useCallback(async (current: Session | null) => {
    if (!current) {
      setClerk(null);
      return;
    }
    try {
      setClerk(await fetchClerk(current.user.id));
    } catch {
      setClerk(null);
    }
  }, []);

  useEffect(() => {
    let active = true;

    (async () => {
      const { data } = await supabase.auth.getSession();
      if (!active) return;
      setSession(data.session);
      await loadClerkForSession(data.session);
      if (active) setLoading(false);
    })();

    const { data: sub } = supabase.auth.onAuthStateChange((_event, next) => {
      setSession(next);
      void loadClerkForSession(next);
    });

    return () => {
      active = false;
      sub.subscription.unsubscribe();
    };
  }, [loadClerkForSession]);

  const signOut = useCallback(async () => {
    await supabase.auth.signOut();
    setClerk(null);
  }, []);

  const refreshClerk = useCallback(async () => {
    const { data } = await supabase.auth.getSession();
    await loadClerkForSession(data.session);
  }, [loadClerkForSession]);

  const value = useMemo(
    () => ({ session, clerk, loading, signOut, refreshClerk }),
    [session, clerk, loading, signOut, refreshClerk],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error("useAuth must be used within an AuthProvider");
  }
  return ctx;
}
