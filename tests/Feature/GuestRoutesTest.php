<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GuestRoutesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'supabase.url' => 'https://test.supabase.co',
            'supabase.anon_key' => 'test-anon-key',
            'supabase.secret_key' => 'test-secret-key',
        ]);
    }

    public function test_guest_visiting_root_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_is_available_to_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Clerk Login');
    }

    public function test_register_page_is_available_to_guests(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Clerk Registration');
    }

    public function test_protected_pages_redirect_guests_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('scan'))->assertRedirect(route('login'));
        $this->get(route('clerk.status'))->assertRedirect(route('login'));
        $this->postJson(route('scan'))->assertUnauthorized();
    }

    public function test_invalid_credentials_return_an_email_error(): void
    {
        Http::fake([
            '*/auth/v1/token*' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'Invalid login credentials',
                'msg' => 'Invalid login credentials',
            ], 400),
        ]);

        $this->from(route('login'))
            ->post(route('login'), ['email' => 'clerk@example.com', 'password' => 'secret'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/auth/v1/token'));
    }

    public function test_successful_login_redirects_to_dashboard(): void
    {
        Http::fake([
            '*/auth/v1/token*' => Http::response($this->tokenPayload(), 200),
            '*/rest/v1/clerks*' => Http::response($this->activeClerkRows(), 200),
        ]);

        $this->post(route('login'), ['email' => 'clerk@example.com', 'password' => 'secret'])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('user-1', session('supabase.user.id'));
    }

    public function test_active_clerk_can_load_the_dashboard(): void
    {
        Http::fake([
            '*/rest/v1/clerks*' => Http::response($this->activeClerkRows(), 200),
            '*/rest/v1/scanner*' => Http::response([
                ['scan_result' => 'success'],
                ['scan_result' => 'failed'],
                ['scan_result' => 'success'],
            ], 200),
        ]);

        $this->withSession($this->clerkSession())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jane Clerk')
            ->assertSee('>3<', false)
            ->assertSee('>2<', false);
    }

    public function test_pending_clerk_is_redirected_to_the_status_page(): void
    {
        $clerk = $this->activeClerkRows();
        $clerk[0]['status'] = 'pending';

        Http::fake([
            '*/rest/v1/clerks*' => Http::response($clerk, 200),
        ]);

        $this->withSession($this->clerkSession())
            ->get(route('dashboard'))
            ->assertRedirect(route('clerk.status'));
    }

    public function test_active_clerk_can_verify_a_scan(): void
    {
        Http::fake([
            '*/rest/v1/clerks*' => Http::response($this->activeClerkRows(), 200),
            '*/rest/v1/rpc/verify_guest_scan' => Http::response([
                'result' => 'success',
                'failure_reason' => null,
                'guest' => [
                    'id' => 'guest-1',
                    'full_name' => 'Guests Guest',
                    'invitation_type' => 'double',
                    'successful_scans' => 1,
                    'allowed_scans' => 2,
                    'remaining_scans' => 1,
                ],
            ], 200),
        ]);

        $this->withSession($this->clerkSession())
            ->postJson(route('scan'), ['qr' => 'guest-1'])
            ->assertOk()
            ->assertJson(['result' => 'success']);
    }

    public function test_suspended_clerk_cannot_verify_a_scan(): void
    {
        $clerk = $this->activeClerkRows();
        $clerk[0]['status'] = 'suspended';

        Http::fake([
            '*/rest/v1/clerks*' => Http::response($clerk, 200),
        ]);

        $this->withSession($this->clerkSession())
            ->postJson(route('scan'), ['qr' => 'guest-1'])
            ->assertForbidden()
            ->assertJson(['failure_reason' => 'clerk_not_authorized']);
    }

    private function tokenPayload(): array
    {
        return [
            'access_token' => 'access-123',
            'refresh_token' => 'refresh-123',
            'expires_in' => 3600,
            'user' => [
                'id' => 'user-1',
                'email' => 'clerk@example.com',
                'email_confirmed_at' => '2026-01-01T00:00:00Z',
                'user_metadata' => ['full_name' => 'Jane Clerk'],
            ],
        ];
    }

    private function activeClerkRows(): array
    {
        return [[
            'id' => 'clerk-1',
            'auth_user_id' => 'user-1',
            'email' => 'clerk@example.com',
            'full_name' => 'Jane Clerk',
            'status' => 'active',
        ]];
    }

    private function clerkSession(): array
    {
        return [
            'supabase.access_token' => 'access-123',
            'supabase.refresh_token' => 'refresh-123',
            'supabase.expires_at' => time() + 3600,
            'supabase.user' => [
                'id' => 'user-1',
                'email' => 'clerk@example.com',
                'full_name' => 'Jane Clerk',
                'email_confirmed' => true,
            ],
        ];
    }
}
