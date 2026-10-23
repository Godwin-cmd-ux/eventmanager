<?php

namespace App\Console\Commands;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\PostgrestClient;
use App\Services\Supabase\SupabaseException;
use App\Support\GuestListParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

/**
 * Imports the hand-written names list into public.invited_guests and writes a
 * JSON mapping of every guest (name, invitation type, UUID) that the card
 * generator then uses to build each QR code.
 *
 * The write goes through the service_role key, so it is idempotent by
 * full_name: guests that already exist are reused instead of duplicated, and
 * their existing UUID is preserved (which keeps previously printed cards valid).
 */
class ImportGuests extends Command
{
    protected $signature = 'guests:import
        {file=cards/names.txt : Path to the guest list (relative to the project root)}
        {--out=cards/guests.json : Where to write the name -> id mapping}
        {--dry-run : Parse and report without touching Supabase}';

    protected $description = 'Create invited guests from the names list and emit their QR id mapping';

    public function handle(ClerkRepository $clerks, PostgrestClient $postgrest, GuestListParser $parser): int
    {
        $file = base_path($this->argument('file'));

        if (! is_file($file)) {
            $this->error("Guest list not found: {$file}");

            return self::FAILURE;
        }

        $parsed = $parser->parse((string) file_get_contents($file));

        foreach ($parsed['errors'] as $error) {
            $this->warn($error);
        }

        $guests = $parsed['guests'];

        if ($guests === []) {
            $this->error('No guests were parsed.');

            return self::FAILURE;
        }

        if ($parsed['errors'] !== []) {
            $this->error('Aborting: fix the malformed lines above, then re-run.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Parsed %d guests (%d single, %d double).',
            count($guests),
            count(array_filter($guests, fn ($g) => $g['type'] === 'single')),
            count(array_filter($guests, fn ($g) => $g['type'] === 'double')),
        ));

        if ($this->option('dry-run')) {
            foreach ($guests as $i => $guest) {
                $this->line(sprintf('  %2d. %-34s %s', $i + 1, $guest['name'], $guest['type']));
            }

            return self::SUCCESS;
        }

        $key = (string) Config::get('supabase.secret_key');

        if ($key === '') {
            $this->error('SUPABASE_SECRET_KEY is not configured.');

            return self::FAILURE;
        }

        // Existing guests, so re-runs never create duplicates and keep the ids
        // already baked into any printed QR codes.
        try {
            $existing = $postgrest->select(
                'invited_guests',
                ['select' => 'id,full_name,invitation_type'],
                $key,
                $key,
            );
        } catch (SupabaseException $e) {
            $this->error('Supabase error while listing guests: '.$e->getMessage());

            return self::FAILURE;
        }

        $byName = [];
        foreach ($existing as $row) {
            $byName[$this->normalize($row['full_name'])] = $row;
        }

        $mapping = [];
        $created = 0;
        $reused = 0;
        $bar = $this->output->createProgressBar(count($guests));
        $bar->start();

        foreach ($guests as $i => $guest) {
            $normalized = $this->normalize($guest['name']);

            if (isset($byName[$normalized])) {
                $row = $byName[$normalized];
                $reused++;
            } else {
                try {
                    $row = $clerks->inviteGuest($guest['name'], $guest['type'], $key);
                } catch (SupabaseException $e) {
                    $bar->finish();
                    $this->newLine(2);
                    $this->error(sprintf('Failed to create "%s": %s', $guest['name'], $e->getMessage()));

                    return self::FAILURE;
                }

                if (empty($row['id'])) {
                    $bar->finish();
                    $this->newLine(2);
                    $this->error(sprintf('Supabase created no id for "%s".', $guest['name']));

                    return self::FAILURE;
                }

                $byName[$normalized] = $row;
                $created++;
            }

            $mapping[] = [
                'index' => $i + 1,
                'name' => $guest['name'],
                'type' => $row['invitation_type'] ?? $guest['type'],
                'allowed_scans' => ($row['invitation_type'] ?? $guest['type']) === 'double' ? 2 : 1,
                'id' => $row['id'],
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $out = base_path($this->option('out'));

        if (! is_dir(dirname($out))) {
            mkdir(dirname($out), 0775, true);
        }

        file_put_contents(
            $out,
            json_encode($mapping, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        );

        $this->info(sprintf('Created %d, reused %d. Mapping written to %s', $created, $reused, $out));

        return self::SUCCESS;
    }

    private function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }
}
