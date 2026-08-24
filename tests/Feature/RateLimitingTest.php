<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_five_attempts_per_minute()
    {
        User::create([
            'name' => 'Throttle User',
            'email' => 'throttle@example.com',
            'password' => bcrypt('password123'),
            'role' => User::ROLE_CLIENTE,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/login', [
                'email' => 'throttle@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(401);
        }

        // The 6th attempt within the same minute must be throttled,
        // regardless of whether the credentials would've been correct.
        $this->postJson('/api/auth/login', [
            'email' => 'throttle@example.com',
            'password' => 'password123',
        ])->assertStatus(429);
    }

    public function test_register_is_throttled_after_five_attempts_per_minute()
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/auth/register', [
                'name' => "User {$i}",
                'email' => "user{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])->assertStatus(201);
        }

        $this->postJson('/api/auth/register', [
            'name' => 'User Six',
            'email' => 'user6@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(429);
    }
}
