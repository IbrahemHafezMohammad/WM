<?php

namespace Tests\Feature\Payment;

use Tests\Feature\Auth\RegisterTest;
use Tests\TestCase;
use App\Models\User;

class DepositTest extends TestCase
{
    protected $token;
    protected $headers;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Check if the user already exists
        $this->user = User::first();

        // If not, create a new user and get the token once for all tests
        if (!$this->user) {
            $this->token = $this->registerUser();
            $this->user = User::first();
        } else {
            // You may need a way to get the token for the existing user
            $this->token = $this->getTokenForUser($this->user);
        }

        $this->headers = [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }

    protected function registerUser()
    {
        // Register a new user (customize this as per your registration logic)
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(201);

        return $response->json('token');
    }

    protected function getTokenForUser(User $user)
    {
        // Implement a way to retrieve a token for the existing user
        // For example, by logging in
        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password', // use the password you set during registration
        ]);

        $response->assertStatus(200);

        return $response->json('token');
    }

    // Test case for creating a deposit without token
    public function testCreateDepositWithoutToken()
    {
        $apiParams = [
            'payment_method_id' => 1, // Example data, ensure this matches your actual data
            'amount' => 100,
        ];

        $headers = [
            'Authorization' => '',
        ];

        $response = $this->postJson('/api/player/deposit', $apiParams, $headers);

        $response->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'USER_TOKEN_NOT_VALID',
            ]);
    }

    // Test case for creating a deposit with a valid token
    public function testCreateDeposit()
    {
        // Step 1: Get payment methods for the user
        $response = $this->getJson('/api/player/payment/methods', $this->headers);
        $response->assertStatus(200);

        $paymentMethods = $response->json()['payment_methods'];
        $this->assertNotEmpty($paymentMethods);

        // Step 2: Use one of the payment methods to create a deposit
        $apiParams = [
            'payment_method_id' => $paymentMethods[0]['id'],
            'amount' => 100,
        ];

        // Step 3: Make a deposit request with the valid token
        $response = $this->postJson('/api/player/deposit', $apiParams, $this->headers);

        // Step 4: Assert the response status and data
        $response->assertStatus(201)
            ->assertJson([
                'status' => true,
                'message' => 'Deposit successful',
            ]);

        // Optionally, verify the deposit exists in the database
        $this->assertDatabaseHas('deposits', [
            'user_id' => $this->user->id,
            'payment_method_id' => $apiParams['payment_method_id'],
            'amount' => $apiParams['amount'],
        ]);
    }
}
