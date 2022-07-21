<?php

namespace Tests;

use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\Activity;
use App\Models\User;

class ActivityTest extends TestCase
{
    /**
     * Test get all activities.
     *
     * @return void
     */
    public function test_get_activities()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->get("/v1/activities");

        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            "type",
            "title",
            "message",
            "data"
        ]);
    }

    /**
     * Test save activity.
     *
     * @return void
     */
    public function test_create_activities()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->post("/v1/activities", [
            "title" => "Password Update",
            "description" => "Your password has been updated"
        ]);

        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            "type",
            "title",
            "message",
            "data"
        ]);
    }

    /**
     * Test delete aactivity.
     *
     * @return void
     */
    public function test_delete_activities()
    {
        $user = User::factory(1)->create()->first();
        $activities_id = Activity::inRandomOrder()->first()->id;

        $this->actingAs($user)->delete("/v1/activities/{$activities_id}", []);

        $this->seeStatusCode(200);
        $this->seeJsonStructure([
            "type",
            "title",
            "message",
            "data"
        ]);
    }
}
