<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
            $payload = $this->goTrue->signIn($data['email'], $data['password']);
            $this->session->store($payload);
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
