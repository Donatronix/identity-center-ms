<?php

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\Activity;
use App\Models\User;

class TwoFASecurityTest extends TestCase
{
    /**
     * Test generate secret.
     *
     * @return void
     */
    public function test_generate_secret()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->get("/v1/2fa/generateSecret");

        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            "type",
            "title",
            "message",
            "data"
        ]);
    }

    /**
     * Test enable 2fa.
     *
     * @return void
     */
    // public function test_enable_2fa()
    // {
        // $user = User::factory(1)->create()->first();
        // $this->actingAs($user)->post("/v1/2fa/enable2fa", [
        //     "code" => "155667"
        // ]);

        // $this->seeStatusCode(200);
        // $this->seeJsonStructure([
        //     "type",
        //     "title",
        //     "message",
        //     "data"
        // ]);
    // }

    /**
     * Test verify 2fa.
     *
     * @return void
     */
    // public function test_verify_2fa()
    // {
        // $user = User::factory(1)->create()->first();
        // $this->actingAs($user)->post("/v1/2fa/verify", [
        //     "code" => "155667"
        // ]);

        // $this->seeStatusCode(200);
        // $this->seeJsonStructure([
        //     "type",
        //     "title",
        //     "message",
        //     "data"
        // ]);
    // }

    /**
     * Test disable 2fa.
     *
     * @return void
     */
    // public function test_disable_2fa()
    // {
        // $user = User::factory(1)->create()->first();
        // $this->actingAs($user)->post("/v1/2fa/disable2fa", [
        //     "password" => "password"
        // ]);

        // $this->seeStatusCode(200);
        // $this->seeJsonStructure([
        //     "type",
        //     "title",
        //     "message",
        //     "data"
        // ]);
    // }
}
