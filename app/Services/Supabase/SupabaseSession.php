<?php

namespace App\Services\Supabase;

use Illuminate\Contracts\Session\Session;

/**
 * Stores the GoTrue access/refresh tokens and the normalized user profile in
 * the Laravel session, and transparently refreshes the access token before it
 * expires so controllers never have to handle token rotation themselves.
 */
class SupabaseSession
{
    public const ACCESS = 'supabase.access_token';

    public const REFRESH = 'supabase.refresh_token';

    public const EXPIRES = 'supabase.expires_at';

    public const USER = 'supabase.user';

    public function __construct(
        private readonly Session $session,
        private readonly GoTrueClient $goTrue,
    ) {}

    public function isLoggedIn(): bool
    {
        return $this->session->has(self::ACCESS);
    }

    public function user(): ?array
    {
        return $this->session->get(self::USER);
    }

    public function userId(): ?string
    {
        return $this->user()['id'] ?? null;
    }

    public function accessToken(): ?string
    {
        return $this->session->get(self::ACCESS);
    }

    public function refreshToken(): ?string
    {
        return $this->session->get(self::REFRESH);
    }

    /**
     * Persists a GoTrue session payload:
     * { access_token, refresh_token, expires_in, user }
     */
    public function store(array $payload): void
    {
        $this->session->put(self::ACCESS, $payload['access_token'] ?? null);
        $this->session->put(self::REFRESH, $payload['refresh_token'] ?? null);
        $this->session->put(self::EXPIRES, time() + (int) ($payload['expires_in'] ?? 3600));
        $this->session->put(self::USER, $this->normalizeUser($payload['user'] ?? []));
        $this->session->save();
    }

    public function forget(): void
    {
        $this->session->forget([self::ACCESS, self::REFRESH, self::EXPIRES, self::USER]);
        $this->session->save();
    }

    /**
     * Returns a user-scoped (authenticated) access token that is guaranteed
     * fresh for the next request, or null when there is no session.
     */
    public function freshToken(): ?string
    {
        $token = $this->accessToken();

        if ($token === null) {
            return null;
        }

        if (time() < (int) $this->session->get(self::EXPIRES, 0) - 60) {
            return $token;
        }

        $refreshToken = $this->refreshToken();

        if ($refreshToken === null) {
            $this->forget();

            return null;
        }

        try {
            $this->store($this->goTrue->refresh($refreshToken));
        } catch (SupabaseException) {
            $this->forget();

            return null;
        }

        return $this->accessToken();
    }

    private function normalizeUser(array $user): array
    {
        $meta = $user['user_metadata'] ?? [];

        return [
            'id' => $user['id'] ?? null,
            'email' => $user['email'] ?? null,
            'full_name' => $meta['full_name'] ?? null,
            'email_confirmed' => ($user['email_confirmed_at'] ?? null) !== null,
        ];
    }
}
