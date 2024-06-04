<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LoginTest extends TestCase
{   
    protected $responseData;


     public function testCreateLogin()
    {
        $username ='ajay7777';
        $password = '1234qweR';

        $requestData = [
            'user_name' => $username,
            'password' => $password,
        ];

        $response = $this->postJson('/api/player/login', $requestData);

        $data = $this->assertLoginSuccess($response);
        return [
            'token'=>$data['token'],
        ];
    }

    /**
     * Test a login failure due to an empty username.
     *
     * @test
     */
    public function LoginFailureEmptyUsername()
    {
        $requestData = [
            'user_name' => '',     // empty username
            'password' => '1234qweR',
        ];

        $response = $this->postJson('/api/player/login', $requestData);

        $this->assertValidationFailure($response, 'The user name field is required.');
    }

    /**
     * Test a login failure due to an empty password.
     *
     * @test
     */
    public function LoginFailureEmptyPassword()
    {
        $requestData = [
            'user_name' => 'sanjayplayer',
            'password' => '',     // empty password
        ];

        $response = $this->postJson('/api/player/login', $requestData);

        $this->assertValidationFailure($response, 'The password field is required.');
    }

    public function LoginFailureInactiveUser()
    {
        $requestData = [
            'user_name' => 'zyz14000',
            'password' => 'Diganta7908',
        ];

        $response = $this->postJson('/api/player/login', $requestData);

        $this->assertLoginFailure($response, 'ACCOUNT_INACTIVE');
    }

    /**
     * Asserts a successful login response.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @return void
     */
    protected function assertLoginSuccess($response)
    {
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'USER_LOGGED_IN_SUCCESSFULLY',
            ]);

        // Extract user name and token from the response
        $responseData = $response->json();

        // Return an array containing success status, user name, and token
        $data = [
            'token' => $responseData['token']
        ];
        return $data;
    }

    /**
     * Asserts a validation failure response.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $errorMessage
     * @return void
     */
    protected function assertValidationFailure($response, $errorMessage)
    {
        $response->assertStatus(422)
            ->assertJson([
                'message' => $errorMessage,
            ]);
    }

    /**
     * Asserts a login failure response.
     *
     * @param \Illuminate\Testing\TestResponse $response
     * @param string $errorMessage
     * @return void
     */
    protected function assertLoginFailure($response, $errorMessage)
    {
        $response->assertStatus(402)
            ->assertJson([
                'message' => $errorMessage,
            ]);
    }
}
