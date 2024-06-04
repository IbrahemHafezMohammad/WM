<?php

namespace Tests\Feature\Auth;

use App\Models\Agent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class RegisterTest extends TestCase
{

    /**
     * Generate a random phone number with the +63 country code.
     *
     * @return string
     */
    public function generateRandomPhoneNumber()
    {
        // Generate a random 9-digit number (for mobile numbers which are typically 10 digits without the country code)
        $randomNumber = mt_rand(100000000, 999999999);
        return '+63' . $randomNumber;
    }
    /**
     * Generate a random username that meets the required format.
     *
     * @return string
     */
    public function generateValidUsername()
    {
        $length = mt_rand(8, 12);
        $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $username = '';
        for ($i = 0; $i < $length; $i++) {
            $username .= $characters[mt_rand(0, strlen($characters) - 1)];
        }
        return $username;
    }

    /**
     * Test a successful user registration without agent_id.
     *
     * @test
     */
    public function registerSuccessWithoutAgentId()
    {
        $requestData = [
            'user_name' => $this->generateValidUsername(), 
            'password' => '1234qwer', 
            'phone' => $this->generateRandomPhoneNumber(),
            'name' => Str::random(10),
            'currency' => 1,
        ];

        $response = $this->postJson('/api/player/create', $requestData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'PLAYER_CREATED_SUCCESSFULLY',
            ]);
    }

     /**
     * Test a successful user registration with agent_id.
     *
     * @test
     */

    public function registerSuccessWithAgentId()
    {
        // Retrieve an existing agent ID from the database
        $agentcode = Agent::inRandomOrder()->value('unique_code');
      
        $requestData = [
            'user_name' => $this->generateValidUsername(),
            'password' => 'validpwd1', 
            'phone' => $this->generateRandomPhoneNumber(), 
            'name' => Str::random(10), 
            'currency' => 1,
            'agent_id' => $agentcode,
        ];

        $response = $this->postJson('/api/player/create', $requestData);

        // Assert the response status and message
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'PLAYER_CREATED_SUCCESSFULLY',
            ]);

        $userId = $response->json('user_id');
        $agentId = Agent::select('id')->where('unique_code',$agentcode)->first();

        // Verify that the player's agent_id is correctly mapped
        $this->assertDatabaseHas('players', [
            'user_id' => $userId,
            'agent_id' => $agentId->id,
        ]);
    }

    /**
     * Test a user registration failure due to missing required fields.
     *
     * @test
     */

    public function registerFailureMissingFields()
    {
        $requestData = [];

        $response = $this->postJson('/api/player/create', $requestData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => [
                    'user_name' => ['The user name field is required.'],
                    'password' => ['The password field is required.'],
                    'phone' => ['The phone field is required.'],
                    'name' => ['The name field is required.'],
                    'currency' => ['The currency field is required.'],
                ],
            ]);
    }

    /**
     * Test a user registration failure due to invalid phone number.
     *
     * @test
     */

    public function registerFailureInvalidPhoneNumber()
    {
        $requestData = [
            'user_name' => $this->generateValidUsername(),
            'password' => 'validpwd1', 
            'phone' => 'invalid_phone',
            'name' => Str::random(10), 
            'currency' => 1,
        ];

        $response = $this->postJson('/api/player/create', $requestData);

        $response->assertStatus(422)
        ->assertJson([
            'status' => false,
            'message' => [
                'phone' => ['The phone must match this format (+)(number) and the number length between 11 and 14'],
            ],
        ]);
    }

    /**
     * Test a user registration failure due to invalid input.
     *
     * @test
     */

    public function registerFailureInvalidInput()
    {
        $requestData = [
            'user_name' => 'InvalidUser', 
            'password' => 'weakpwd',
            'phone' => 'invalid_phone',
            'name' => '', 
            'currency' => 999, // Invalid currency ID
        ];

        $response = $this->postJson('/api/player/create', $requestData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => false,
                'message' => [
                    'user_name' => ['The user name must be lowercase or numbers or both and the length between 8 and 12'],
                    'password' => ['The password must contain at least one lowercase letter and at least one number, consist only of lowercase letters and numbers, and be between 6 and 13 characters long.'],
                    'phone' => ['The phone must match this format (+)(number) and the number length between 11 and 14'],
                    'name' => ['The name field is required.'],
                    'currency' => ['The selected currency is invalid.'],
                ],
            ]);
    }

 
}
