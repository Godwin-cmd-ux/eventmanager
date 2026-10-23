<?php

namespace App\Repositories;

use App\Services\Supabase\PostgrestClient;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Support\Facades\Config;

class ClerkRepository
{
    public function __construct(
        private readonly PostgrestClient $postgrest,
        private readonly SupabaseSession $session,
    ) {}

    public function findByAuthUserId(string $authUserId, ?string $token = null): ?array
    {
        $result = $this->postgrest->select('clerks', [
            'select' => '*',
            'auth_user_id' => 'eq.'.$authUserId,
            'limit' => '1',
        ], Config::get('supabase.secret_key'), $token);

        return $result[0] ?? null;
    }

    public function findById(string $id, ?string $token = null): ?array
    {
        $result = $this->postgrest->select('clerks', [
            'select' => '*',
            'id' => 'eq.'.$id,
            'limit' => '1',
        ], Config::get('supabase.secret_key'), $token);

        return $result[0] ?? null;
    }

    public function approve(string $email, ?string $token = null): array
    {
        $result = $this->postgrest->update('clerks', [
            'email' => 'eq.'.$email,
        ], ['status' => 'active'], Config::get('supabase.secret_key'), $token);

        return $result[0] ?? [];
    }

    public function suspend(string $email, ?string $token = null): array
    {
        $result = $this->postgrest->update('clerks', [
            'email' => 'eq.'.$email,
        ], ['status' => 'suspended'], Config::get('supabase.secret_key'), $token);

        return $result[0] ?? [];
    }

    public function insertFromAuth(array $user): array
    {
        $meta = $user['user_metadata'] ?? [];
        $fullName = $meta['full_name'] ?? ($user['email'] ?? 'Unknown Clerk');
        $email = $user['email'] ?? null;

        $result = $this->postgrest->insert('clerks', [
            'auth_user_id' => $user['id'],
            'email' => $email,
            'full_name' => $fullName,
            'status' => 'pending',
        ], Config::get('supabase.secret_key'));

        return $result[0] ?? [];
    }

    public function inviteGuest(string $fullName, string $invitationType, ?string $token = null): array
    {
        $result = $this->postgrest->insert('invited_guests', [
            'full_name' => $fullName,
            'invitation_type' => $invitationType,
            'allowed_scans' => $invitationType === 'double' ? 2 : 1,
            'successful_scans' => 0,
        ], Config::get('supabase.secret_key'), $token);

        return $result[0] ?? [];
    }
}
