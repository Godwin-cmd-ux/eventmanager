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
            $user = $this->goTrue->createConfirmedUser(
                $data['email'],
                $data['password'],
                $data['full_name'],
            );
        } catch (SupabaseException $e) {
            $duplicate = (bool) preg_match('/already (been )?registered|already exists|duplicate|email_exists/i', $e->getMessage());

            throw ValidationException::withMessages([
                'email' => $duplicate
                    ? 'An account with this email already exists. Try logging in instead.'
                    : $e->getMessage(),
            ]);
        }

        // The clerk is created already confirmed: access is granted by the
        // organizer, never by clicking an email link. The Supabase trigger
        // creates the pending clerk row automatically; insert a fallback only
        // when the trigger did not run.
        if (! empty($user['id']) && ! $this->clerks->findByAuthUserId($user['id'])) {
            try {
                $this->clerks->insertFromAuth($user);
            } catch (\Throwable) {
                // Ignore: the trigger likely won the race.
            }
        }

        try {
            $this->session->store(
                $this->goTrue->signIn($data['email'], $data['password']),
            );
        } catch (SupabaseException) {
            // The account exists; let the clerk sign in manually rather than
            // leaving them on a form that would now reject their email.
            return redirect()->route('login')->with('toast', [
                'tone' => 'success',
                'title' => 'Account created',
                'message' => 'Sign in to check your approval status.',
            ]);
        }

        return redirect()->route('clerk.status');
    }
}
