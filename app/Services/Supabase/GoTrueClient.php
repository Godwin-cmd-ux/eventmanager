<?php

namespace App\Services\Supabase;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoTrueClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $anonKey,
    ) {}

    /**
     * POST /auth/v1/token?grant_type=password
     * Returns an array with 'access_token', 'refresh_token', 'user'.
     */
    public function signIn(string $email, string $password): array
    {
        return $this->decode(
            Http::withHeaders(['apikey' => $this->anonKey])
                ->post($this->url('token', ['grant_type' => 'password']), [
                    'email' => $email,
                    'password' => $password,
                ]),
            'sign in',
        );
    }

    /**
     * POST /auth/v1/token?grant_type=refresh_token
     */
    public function refresh(string $refreshToken): array
    {
        return $this->decode(
            Http::withHeaders(['apikey' => $this->anonKey])
                ->post($this->url('token', ['grant_type' => 'refresh_token']), [
                    'refresh_token' => $refreshToken,
                ]),
            'refresh session',
        );
    }

    /**
     * POST /auth/v1/signup
     * Returns either a full session (when email confirmation is disabled) or
     * only a 'user' record (when confirmation is required).
     */
    public function signUp(string $email, string $password, string $fullName): array
    {
        return $this->decode(
            Http::withHeaders(['apikey' => $this->anonKey])
                ->post($this->url('signup'), [
                    'email' => $email,
                    'password' => $password,
                    'data' => ['full_name' => $fullName],
                ]),
            'sign up',
        );
    }

    /**
     * GET /auth/v1/user
     */
    public function user(string $accessToken): array
    {
        return $this->decode(
            Http::withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer '.$accessToken,
            ])->get($this->url('user')),
            'user lookup',
        );
    }

    /**
     * POST /auth/v1/logout (best effort, never throws).
     */
    public function signOut(string $accessToken): void
    {
        try {
            Http::withHeaders([
                'apikey' => $this->anonKey,
                'Authorization' => 'Bearer '.$accessToken,
            ])->post($this->url('logout'));
        } catch (\Throwable) {
            // ignore, local session is cleared regardless
        }
    }

    private function url(string $path, array $query = []): string
    {
        return $this->baseUrl.'/auth/v1/'.ltrim($path, '/')
            .(count($query) > 0 ? '?'.http_build_query($query) : '');
    }

    private function decode(Response $response, string $action): array
    {
        $body = $response->json() ?? [];

        if ($response->failed()) {
            throw new SupabaseException($body, $response->status(), $action);
        }

        return $body;
    }
}
