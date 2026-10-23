<?php

namespace App\Services\Supabase;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class PostgrestClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    /**
     * GET /rest/v1/{$table}
     *
     * $query is passed straight through as PostgREST query params, e.g.:
     *   ['select' => 'id,full_name,status', 'auth_user_id' => 'eq.'.$userId]
     */
    public function select(string $table, array $query, string $key, ?string $token = null): array
    {
        $response = Http::withHeaders($this->headers($key, $token))
            ->acceptJson()
            ->get($this->url($table, $query));

        return $this->decode($response, "read {$table}");
    }

    /**
     * POST /rest/v1/{$table}
     */
    public function insert(string $table, array $body, string $key, ?string $token = null): array
    {
        $response = Http::withHeaders($this->headers($key, $token))
            ->acceptJson()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->post($this->url($table), $body);

        return $this->decode($response, "insert into {$table}");
    }

    /**
     * PATCH /rest/v1/{$table}
     *
     * $where is a PostgREST filter map, e.g. ['email' => 'eq.'.$email].
     */
    public function update(string $table, array $where, array $body, string $key, ?string $token = null): array
    {
        $response = Http::withHeaders($this->headers($key, $token))
            ->acceptJson()
            ->withHeaders(['Prefer' => 'return=representation'])
            ->patch($this->url($table, $where), $body);

        return $this->decode($response, "update {$table}");
    }

    /**
     * POST /rest/v1/rpc/{$fn}
     *
     * $args are sent as the JSON body and must match the function parameter
     * names. Returns the decoded body, or null for 204 no content.
     */
    public function rpc(string $fn, array $args, string $key, ?string $token = null): ?array
    {
        $response = Http::withHeaders($this->headers($key, $token))
            ->acceptJson()
            ->post($this->url('rpc/'.$fn), $args);

        if ($response->noContent()) {
            return null;
        }

        return $this->decode($response, "rpc {$fn}");
    }

    private function url(string $path, array $query = []): string
    {
        return $this->baseUrl.'/rest/v1/'.ltrim($path, '/')
            .(count($query) > 0 ? '?'.http_build_query($query) : '');
    }

    private function headers(string $key, ?string $token): array
    {
        // PostgREST picks the database role from the Authorization bearer
        // token; the apikey header alone only authenticates the request at the
        // gateway and leaves the connection on the anon role. Forward the key
        // as the bearer whenever no user token is supplied so privileged keys
        // (service_role) actually assume their role instead of getting
        // "permission denied" on RLS-protected tables.
        return [
            'apikey' => $key,
            'Authorization' => 'Bearer '.($token ?? $key),
        ];
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
