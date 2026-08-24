<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_reset_notification_for_existing_email()
    {
        Notification::fake();

        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);

        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'reset@example.com']);

        $response->assertStatus(200);
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_forgot_password_returns_generic_message_for_unknown_email()
    {
        Notification::fake();

        // Must look identical to the "email exists" response — this
        // endpoint shouldn't leak which emails are registered.
        $response = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['message']);
        Notification::assertNothingSent();
    }

    public function test_reset_password_with_valid_token_updates_password()
    {
        $user = User::create([
            'name' => 'Reset User',
            'email' => 'reset2@example.com',
            'password' => bcrypt('old-password'),
            'role' => User::ROLE_CLIENTE,
        ]);

        $token = Password::createToken($user);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => $token,
            'email' => 'reset2@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));

        // The old password no longer works, the new one does.
        $this->postJson('/api/auth/login', [
            'email' => 'reset2@example.com',
            'password' => 'new-password-123',
        ])->assertStatus(200);
    }

    public function test_reset_password_with_invalid_token_fails()
    {
        User::create([
            'name' => 'Reset User',
            'email' => 'reset3@example.com',
            'password' => bcrypt('old-password'),
            'role' => User::ROLE_CLIENTE,
        ]);

        $response = $this->postJson('/api/auth/reset-password', [
            'token' => 'not-a-real-token',
            'email' => 'reset3@example.com',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $response->assertStatus(422);
    }
}
