<?php

namespace App\Services\Supabase;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class GoTrueClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $anonKey,
        private readonly string $secretKey,
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
     * POST /auth/v1/admin/users
     *
     * Creates an already-confirmed account with the service key. Clerks are
     * approved by the organizer, not by email, so registration must not depend
     * on a confirmation link (or on Supabase's email rate limit).
     */
    public function createConfirmedUser(string $email, string $password, string $fullName): array
    {
        return $this->decode(
            Http::withHeaders([
                'apikey' => $this->secretKey,
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->post($this->url('admin/users'), [
                'email' => $email,
                'password' => $password,
                'email_confirm' => true,
                'user_metadata' => ['full_name' => $fullName],
            ]),
            'create user',
        );
    }

    /**
     * GET /auth/v1/admin/users (paginated)
     *
     * Clerks created before registration switched to the admin API may still
     * be unconfirmed. Look them up so sign-in can confirm them on the spot.
     */
    public function findUserByEmail(string $email): ?array
    {
        $headers = [
            'apikey' => $this->secretKey,
            'Authorization' => 'Bearer '.$this->secretKey,
        ];

        for ($page = 1; $page <= 20; $page++) {
            $body = $this->decode(
                Http::withHeaders($headers)->get($this->url('admin/users', [
                    'page' => $page,
                    'per_page' => 1000,
                ])),
                'list users',
            );

            $users = $body['users'] ?? $body;

            foreach ($users as $user) {
                if (isset($user['email']) && strcasecmp((string) $user['email'], $email) === 0) {
                    return $user;
                }
            }

            if (count($users) < 1000) {
                break;
            }
        }

        return null;
    }

    /**
     * PUT /auth/v1/admin/users/{id}
     *
     * Marks the account as confirmed without sending a confirmation email.
     */
    public function confirmUser(string $userId): array
    {
        return $this->decode(
            Http::withHeaders([
                'apikey' => $this->secretKey,
                'Authorization' => 'Bearer '.$this->secretKey,
            ])->put($this->url('admin/users/'.rawurlencode($userId)), [
                'email_confirm' => true,
            ]),
            'confirm user',
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
