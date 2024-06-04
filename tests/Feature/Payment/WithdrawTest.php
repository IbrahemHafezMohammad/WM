<?php

namespace Tests\Feature\Auth;
use function PHPUnit\Framework\assertTrue;

use App\Constants\BankCodeConstants;
use App\Constants\PaymentCategoryConstants;
use Illuminate\Support\Str;
use Tests\Feature\Auth\LoginTest;

class WithdrawTest extends LoginTest
{

    public function testInvalidToken() {
        $apiParams = [
        ];

        $response = $this->postJson('/api/player/withdraw', $apiParams);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'USER_TOKEN_NOT_VALID',
            ]);

        return $response->json();
    }

    public function testAmountField() {
        $token = $this->registerUser();
        $apiParams = [
            'user_payment_method_id' => 104,
        ];

        $headers = [ 
            'Authorization' => 'Bearer ' . $token, 
        ]; 
         
        $response = $this->postJson('/api/player/withdraw', $apiParams, $headers);

        $response->assertStatus(422);

        $this->assertIsArray($response['message']);
        $this->assertArrayHasKey('amount', $response['message'], "The amount field is required");
        return $response->json();
    }

    public function testPaymentMethodIdField() {
        $token = $this->registerUser();
        $apiParams = [
            'amount' => 100,
        ];

        $headers = [ 
            'Authorization' => 'Bearer ' . $token, 
        ]; 
         
        $response = $this->postJson('/api/player/withdraw', $apiParams, $headers);

        $response->assertStatus(422);

        $this->assertIsArray($response['message']);
        $this->assertArrayHasKey('user_payment_method_id', $response['message'], "The user payment method id field is required.");
        return $response->json();
    }

    public function testInvalidPaymentMethodId() {
        $token = $this->registerUser();
        $apiParams = [
            'user_payment_method_id' => 65,
            'amount' => 100,
        ];

        $headers = [ 
            'Authorization' => 'Bearer ' . $token, 
        ]; 
         
        $response = $this->postJson('/api/player/withdraw', $apiParams, $headers);

        $response->assertStatus(422);

        $this->assertIsArray($response['message']);
        $this->assertArrayHasKey('user_payment_method_id', $response['message'], "The selected user payment method id is invalid.");
        return $response->json();
    }

    /**
     * @test
     */
    // public function testInvalidAmount() {
    //     $token = $this->registerUser();
        

    //     $paymentMethodCreateApiParams = [
    //         'bank_code_id' => BankCodeConstants::CODE_GCASH,
    //         'payment_category_id' => 2,
    //         'account_number' => '9876543210',
    //     ];

    //     $headers = [ 
    //         'Authorization' => 'Bearer ' . $token, 
    //     ]; 

    //     $paymentMethodCreateApi = $this->postJson('api/player/user/payment/method/create', $paymentMethodCreateApiParams, $headers);

    //     $paymentMethodCreateApi->assertStatus(200)
    //         ->assertJson([
    //             'message' => 'USER_PAYMENT_METHOD_CREATED_SUCCESSFULLY',
    //         ]);

    //     $paymentMethodApi = $this->getJson('api/player/user/payment/method/list', $headers);

    //     $responseData = $paymentMethodApi->decodeResponseJson();

    //     dd($responseData);

    //     $apiParams = [
    //         'user_payment_method_id' => 104,
    //         'amount' => 10,
    //     ];
         
    //     $response = $this->postJson('/api/player/withdraw', $apiParams, $headers);

    //     $response->assertStatus(422)
    //         ->assertJson([
    //             'message' => 'INVALID_AMOUNT',
    //         ]);

    //         return $response->json();
    // }

    // public function testSuccessWithdrawl() {
    //     $token = $this->registerUser();
    //     $apiParams = [
    //         'user_name' => 'sanjayplayer',
    //         'password' => '1234qweR',
    //         'user_payment_method_id' => 104,
    //         'amount' => 100,
    //     ];

    //     $headers = [ 
    //         'Authorization' => 'Bearer ' . $token, 
    //     ]; 
         
    //     $response = $this->postJson('/api/player/withdraw', $apiParams, $headers);

    //     $response->assertStatus(200)
    //         ->assertJson([
    //             'message' => 'WITHDRAW_SUCCESSFULLY',
    //         ]);

    //         return $response->json();
    // }

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
     * @test
     */
    public function registerUser()
    {
        $requestData = [
            'user_name' => $this->generateValidUsername(), // Ensure this meets the required format
            'password' => '1234qwer', // Ensure this meets the required format
            'phone' => $this->generateRandomPhoneNumber(), // Generate a random phone number with +63 country code
            'name' => Str::random(10), // Generate a random name
            'currency' => 1,
        ];

        $response = $this->postJson('/api/player/create', $requestData);

        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'PLAYER_CREATED_SUCCESSFULLY',
            ]);
        
        return $response['token'];
    }

}