<?php

namespace App\Http\Middleware;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\SupabaseSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClerkActive
{
    public function __construct(
        private readonly ClerkRepository $clerks,
        private readonly SupabaseSession $session,
    ) {}

    /**
     * Allow the request only when the signed-in user has an active clerk
     * record. Pending, suspended, or missing profiles are sent to the status
     * page, which explains the situation. The clerk record is attached to the
     * request so controllers do not need to fetch it again.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        $userId = $this->session->userId();
        $clerk = $userId ? $this->clerks->findByAuthUserId($userId) : null;

        if (! $clerk || ($clerk['status'] ?? null) !== 'active') {
            if ($request->expectsJson()) {
                return response()->json([
                    'result' => 'failed',
                    'failure_reason' => 'clerk_not_authorized',
                    'guest' => null,
                ], 403);
            }

            return redirect()->route('clerk.status');
        }

        $request->attributes->set('clerk', $clerk);

        return $next($request);
    }
}
