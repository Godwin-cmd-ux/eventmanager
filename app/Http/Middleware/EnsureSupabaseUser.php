<?php

namespace App\Http\Middleware;

use App\Services\Supabase\GoTrueClient;
use App\Services\Supabase\SupabaseException;
use App\Services\Supabase\SupabaseSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupabaseUser
{
    public function __construct(
        private readonly SupabaseSession $session,
        private readonly GoTrueClient $goTrue,
    ) {}

    /**
     * Require a signed-in GoTrue session; otherwise send the user back to the
     * login screen (or return 401 for JSON requests such as the scanner).
     *
     * The session is only a local copy of the GoTrue session, so the account is
     * re-checked with Supabase on every request. Deleting a user in Supabase
     * therefore signs them out immediately instead of leaving a stale session
     * that still looks valid in the browser.
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $this->session->isLoggedIn()) {
            return $this->unauthenticated($request);
        }

        $token = $this->session->freshToken();

        if ($token === null) {
            $this->session->forget();

            return $this->unauthenticated($request);
        }

        try {
            $this->goTrue->user($token);
        } catch (SupabaseException) {
            $this->session->forget();

            return $this->unauthenticated($request);
        }

        return $next($request);
    }

    private function unauthenticated(Request $request): Response|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Please sign in.'], 401);
        }

        return redirect()->route('login');
    }
}
