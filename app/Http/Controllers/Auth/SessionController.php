<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\ClerkRepository;
use App\Services\Supabase\GoTrueClient;
use App\Services\Supabase\SupabaseException;
use App\Services\Supabase\SupabaseSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(
        private readonly GoTrueClient $goTrue,
        private readonly ClerkRepository $clerks,
        private readonly SupabaseSession $session,
    ) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        try {
            $payload = $this->signInOrConfirm($data['email'], $data['password']);
            $this->session->store($payload);

            // Clerks no longer need approval: activate any account still left
            // in 'pending' by an earlier registration.
            $userId = $payload['user']['id'] ?? null;
            if ($userId) {
                $this->clerks->activatePending($userId);
            }
        } catch (SupabaseException $e) {
            $msg = $e->getMessage();

            $friendly = match (true) {
                (bool) preg_match('/email not confirmed/i', $msg) => 'Your email is not confirmed yet. Please check your inbox.',
                (bool) preg_match('/invalid login credentials/i', $msg) => 'Incorrect email or password.',
                default => $msg,
            };

            throw ValidationException::withMessages(['email' => $friendly]);
        }

        return Redirect::route('dashboard')->with('toast', [
            'tone' => 'success',
            'title' => 'Signed in',
            'message' => 'You are ready to verify invitations.',
        ]);
    }

    /**
     * Sign in, auto-confirming the account if Supabase still considers it
     * unconfirmed. Clerks are approved by the organizer, not by email, so an
     * old unconfirmed account must not block access.
     */
    private function signInOrConfirm(string $email, string $password): array
    {
        try {
            return $this->goTrue->signIn($email, $password);
        } catch (SupabaseException $e) {
            if (! preg_match('/email not confirmed/i', $e->getMessage())) {
                throw $e;
            }
        }

        $user = $this->goTrue->findUserByEmail($email);

        if ($user === null || empty($user['id'])) {
            throw new SupabaseException(['msg' => 'Email not confirmed'], 400, 'confirm user');
        }

        $this->goTrue->confirmUser($user['id']);

        return $this->goTrue->signIn($email, $password);
    }

    public function destroy(): RedirectResponse
    {
        $token = $this->session->accessToken();
        if ($token) {
            $this->goTrue->signOut($token);
        }

        $this->session->forget();

        return Redirect::route('login')->with('toast', [
            'tone' => 'info',
            'title' => 'Signed out',
            'message' => 'Your session has ended safely.',
        ]);
    }
}
