<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRegistrationAndIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_new_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'New Business Owner',
            'email' => 'owner@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com']);
    }

    public function test_user_data_is_isolated_between_users(): void
    {
        $userA = User::factory()->create(['email' => 'userA@example.com']);
        $userB = User::factory()->create(['email' => 'userB@example.com']);

        // Order created by User A
        $orderA = Order::create([
            'user_id' => $userA->id,
            'order_id' => 'ORD_USER_A',
            'customer_name' => 'Customer A',
            'phone_number' => '9876543210',
            'status' => 'NEW',
        ]);

        // Order created by User B
        $orderB = Order::create([
            'user_id' => $userB->id,
            'order_id' => 'ORD_USER_B',
            'customer_name' => 'Customer B',
            'phone_number' => '9812345678',
            'status' => 'NEW',
        ]);

        // User A views orders list
        $responseA = $this->actingAs($userA)->get('/orders');
        $responseA->assertSee('ORD_USER_A')
                 ->assertDontSee('ORD_USER_B');

        // User B views orders list
        $responseB = $this->actingAs($userB)->get('/orders');
        $responseB->assertSee('ORD_USER_B')
                 ->assertDontSee('ORD_USER_A');
    }
}
