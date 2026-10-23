<?php

namespace App\Console\Commands;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\SupabaseException;
use Illuminate\Console\Command;

class SuspendClerk extends Command
{
    protected $signature = 'clerk:suspend {email : Email of the clerk to suspend}';

    protected $description = 'Suspend a clerk, revoking scanning privileges';

    public function handle(ClerkRepository $clerks): int
    {
        $email = $this->argument('email');

        try {
            $clerk = $clerks->suspend($email);
        } catch (SupabaseException $e) {
            $this->error('Supabase error: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($clerk)) {
            $this->error("No clerk found for email '{$email}'.");

            return self::FAILURE;
        }

        $this->info("Clerk '{$clerk['full_name']}' ({$email}) is now SUSPENDED.");

        return self::SUCCESS;
    }
}
