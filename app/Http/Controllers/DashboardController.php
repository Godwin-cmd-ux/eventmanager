<?php

namespace App\Http\Controllers;

use App\Services\Supabase\PostgrestClient;
use Illuminate\Support\Facades\Config;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly PostgrestClient $postgrest,
    ) {}

    public function index(): View
    {
        $clerk = request()->attributes->get('clerk');

        return view('dashboard', [
            'clerk' => $clerk,
            'stats' => $this->statsFor($clerk),
        ]);
    }

    /**
     * Counts this clerk's scans. Reads go through the service key server-side,
     * scoped to the authenticated clerk's id.
     */
    private function statsFor(?array $clerk): array
    {
        $empty = ['total' => 0, 'successful' => 0, 'failed' => 0];

        if (! $clerk || empty($clerk['id'])) {
            return $empty;
        }

        $key = Config::get('supabase.secret_key');

        try {
            $rows = $this->postgrest->select('scanner', [
                'select' => 'scan_result',
                'clerk_id' => 'eq.'.$clerk['id'],
            ], $key);
        } catch (\Throwable) {
            return $empty;
        }

        $successful = 0;
        foreach ($rows as $row) {
            if (($row['scan_result'] ?? null) === 'success') {
                $successful++;
            }
        }

        return [
            'total' => count($rows),
            'successful' => $successful,
            'failed' => count($rows) - $successful,
        ];
    }
}
