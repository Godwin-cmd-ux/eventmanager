<?php

namespace App\Http\Middleware;

use App\Services\Supabase\SupabaseSession;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupabaseUser
{
    public function __construct(
        private readonly SupabaseSession $session,
    ) {}

    /**
     * Require a signed-in GoTrue session; otherwise send the user back to the
     * login screen (or return 401 for JSON requests such as the scanner).
     */
    public function handle(Request $request, Closure $next): Response|JsonResponse
    {
        if (! $this->session->isLoggedIn()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Please sign in.'], 401);
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
