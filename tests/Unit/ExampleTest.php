<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected $password = "1234qweR";

    /** @test */
    public function a_player_can_register_with_valid_data()
    {
       return true;
    }
}
