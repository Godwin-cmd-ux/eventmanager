<?php

namespace App\Console\Commands;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\SupabaseException;
use Illuminate\Console\Command;

class ApproveClerk extends Command
{
    protected $signature = 'clerk:approve {email : Email of the clerk to approve}';

    protected $description = 'Approve a pending clerk, granting scanning privileges';

    public function handle(ClerkRepository $clerks): int
    {
        $email = $this->argument('email');

        try {
            $clerk = $clerks->approve($email);
        } catch (SupabaseException $e) {
            $this->error('Supabase error: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($clerk)) {
            $this->error("No clerk found for email '{$email}'.");

            return self::FAILURE;
        }

        $this->info("Clerk '{$clerk['full_name']}' ({$email}) is now ACTIVE.");

        return self::SUCCESS;
    }
}
