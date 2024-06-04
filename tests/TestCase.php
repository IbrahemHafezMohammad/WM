<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use InvalidArgumentException;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    function api($route_name, $api_params = [], $route_params=[], $method = 'POST', $token =null)
    {
        if ($token && isset($token)) {
            $api_params["token"] = $token;
        }
       
        $url = route($route_name, $route_params);
        
        // Make HTTP request based on the method
        switch (strtoupper($method)) {
            case 'GET':
                $response = $this->getJson($url, $api_params);
                break;
            case 'POST':
                $response = $this->postJson($url, $api_params);
                break;
            case 'PUT':
                $response = $this->putJson($url, $api_params);
                break;
            case 'DELETE':
                $response = $this->deleteJson($url, $api_params);
                break;
            default:
                throw new InvalidArgumentException("Unsupported HTTP method: $method");
        }
        
        // Assert that the response status is 200
        $response->assertStatus(200);
        
        return $response;
    }
    
}
