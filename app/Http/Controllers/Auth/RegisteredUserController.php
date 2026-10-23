<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\ClerkRepository;
use App\Services\Supabase\GoTrueClient;
use App\Services\Supabase\SupabaseException;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly GoTrueClient $goTrue,
        private readonly ClerkRepository $clerks,
        private readonly SupabaseSession $session,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $payload = $this->goTrue->signUp(
                $data['email'],
                $data['password'],
                $data['full_name'],
            );
        } catch (SupabaseException $e) {
            if (preg_match('/already registered|already exists|duplicate/i', $e->getMessage())) {
                throw ValidationException::withMessages([
                    'email' => 'An account with this email already exists. Try logging in instead.',
                ]);
            }

            throw ValidationException::withMessages([
                'email' => $e->getMessage(),
            ]);
        }

        // The Supabase trigger creates the pending clerk row automatically.
        // Insert a fallback only when the trigger did not run.
        $user = $payload['user'] ?? null;
        if ($user && ! empty($user['id']) && ! $this->clerks->findByAuthUserId($user['id'])) {
            try {
                $this->clerks->insertFromAuth($user);
            } catch (\Throwable) {
                // Ignore: the trigger likely won the race.
            }
        }

        // When email confirmation is disabled GoTrue returns a session; sign
        // the clerk in and show them the pending status page.
        if (! empty($payload['access_token'])) {
            $this->session->store($payload);

            return redirect()->route('clerk.status');
        }

        return redirect()->route('register')->with('status', 'confirm_email');
    }
}
