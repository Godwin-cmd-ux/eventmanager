<?php

namespace App\Console\Commands;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\SupabaseException;
use Illuminate\Console\Command;
use Illuminate\Validation\Rule;

class InviteGuest extends Command
{
    protected $signature = 'guests:invite
        {name : Full name of the invited guest}
        {--type=single : Invitation type: single (1 scan) or double (2 scans)}';

    protected $description = 'Create an invited guest and print the UUID to encode as their QR code';

    public function handle(ClerkRepository $clerks): int
    {
        $type = $this->option('type');

        $validator = validator([
            'name' => $this->argument('name'),
            'type' => $type,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['single', 'double'])],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        try {
            $guest = $clerks->inviteGuest($this->argument('name'), $type);
        } catch (SupabaseException $e) {
            $this->error('Supabase error: '.$e->getMessage());

            return self::FAILURE;
        }

        if (empty($guest['id'])) {
            $this->error('The guest was not created (no id returned).');

            return self::FAILURE;
        }

        $this->info("Guest '{$guest['full_name']}' created ({$type}).");
        $this->line('');
        $this->line('Encode this UUID as the guest QR code:');
        $this->line('');
        $this->components->info($guest['id']);
        $this->line('');

        return self::SUCCESS;
    }
}
