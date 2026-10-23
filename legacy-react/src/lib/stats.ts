import { supabase } from "./supabase";
import type { ClerkStats } from "./types";

async function countScans(
  clerkId: string,
  result?: "success" | "failed",
): Promise<number> {
  let query = supabase
    .from("scanner")
    .select("id", { count: "exact", head: true })
    .eq("clerk_id", clerkId);

  if (result) {
    query = query.eq("scan_result", result);
  }

  const { count, error } = await query;
  if (error) throw error;
  return count ?? 0;
}

export async function fetchClerkStats(clerkId: string): Promise<ClerkStats> {
  const [total, successful, failed] = await Promise.all([
    countScans(clerkId),
    countScans(clerkId, "success"),
    countScans(clerkId, "failed"),
  ]);
  return { total, successful, failed };
}
