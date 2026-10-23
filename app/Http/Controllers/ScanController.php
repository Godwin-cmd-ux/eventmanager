<?php

namespace App\Http\Controllers;

use App\Services\Supabase\PostgrestClient;
use App\Services\Supabase\SupabaseException;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class ScanController extends Controller
{
    public function __construct(
        private readonly PostgrestClient $postgrest,
        private readonly SupabaseSession $session,
    ) {}

    public function create(): View
    {
        return view('scanner', [
            'clerk' => request()->attributes->get('clerk'),
        ]);
    }

    /**
     * Proxies the atomic verify_guest_scan Postgres function. The call is made
     * with the clerk's own Supabase access token so the function's auth.uid()
     * check resolves to this signed-in clerk on the database side.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'qr' => ['required', 'string', 'max:255'],
        ]);

        $token = $this->session->freshToken();

        if ($token === null) {
            return response()->json([
                'result' => 'failed',
                'failure_reason' => 'clerk_not_authorized',
                'guest' => null,
            ], 403);
        }

        try {
            $result = $this->postgrest->rpc(
                'verify_guest_scan',
                ['p_guest_id' => $data['qr']],
                Config::get('supabase.anon_key'),
                $token,
            );
        } catch (SupabaseException) {
            return response()->json([
                'result' => 'failed',
                'failure_reason' => 'connection_error',
                'guest' => null,
            ], 502);
        }

        return response()->json($result ?? [
            'result' => 'failed',
            'failure_reason' => 'invalid_qr',
            'guest' => null,
        ]);
    }
}
