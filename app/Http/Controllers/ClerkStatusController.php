<?php

namespace App\Http\Controllers;

use App\Repositories\ClerkRepository;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClerkStatusController extends Controller
{
    public function __construct(
        private readonly ClerkRepository $clerks,
        private readonly SupabaseSession $session,
    ) {}

    public function show(): View|RedirectResponse
    {
        $userId = $this->session->userId();
        $clerk = $userId ? $this->clerks->findByAuthUserId($userId) : null;
        $status = $clerk['status'] ?? 'missing';

        if ($status === 'active') {
            return redirect()->route('dashboard');
        }

        [$state, $title, $message] = match ($status) {
            'pending' => [
                'pending',
                'Registration received',
                'Your account is pending organizer approval. You will be able to scan once it is approved.',
            ],
            'suspended' => [
                'suspended',
                'Account suspended',
                'Your scanning access has been suspended. Please contact the event organizer.',
            ],
            default => [
                'missing',
                'No clerk profile',
                'We could not find a clerk profile for your account. Please contact the event organizer.',
            ],
        };

        return view('status', compact('state', 'title', 'message'));
    }
}
