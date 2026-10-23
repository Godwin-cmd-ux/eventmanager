<?php

namespace App\Services\Supabase;

use RuntimeException;

class SupabaseException extends RuntimeException
{
    public function __construct(
        public readonly array $payload,
        int $status,
        string $action,
    ) {
        $message = $payload['error_description']
            ?? $payload['msg']
            ?? $payload['message']
            ?? ('Supabase '.$action.' failed (HTTP '.$status.').');

        parent::__construct($message, $status);
    }
}
