<?php
namespace Tests;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Models\User;

class KYCTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testShouldReturn401()
    {
        $this->get('/v1/admin/kyc');
        $this->seeStatusCode(401);
    }

    public function testShouldReturnKYCList()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->get('/v1/admin/kyc?status=all')
            ->seeStatusCode(200);
    }

    public function testShouldReturn400()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->get('/v1/admin/kyc?status=any');
        $this->seeStatusCode(400);
    }

    public function testShouldReturn422()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->post('/v1/user-identify/upload', [
            'document_type' => 6,
            'document_front' => 'base64://jpeg:sjjsjfjjsfjjsjfjsfjs'
        ]);
        $this->seeStatusCode(422);
    }

    public function testShouldAddAndReturnKYC()
    {
        $user = User::factory(1)->create()->first();
        $this->actingAs($user)->post('/v1/user-identify/upload', [
            'document_type' => 3,
            'portrait' => 'base64://png:sjfjjsjf',
            'document_front' => 'base64://jpeg:sjjsjfjjsfjjsjfjsfjs'
        ])->seeStatusCode(200)->seeJsonStructure([
            'data' => [
                'message',
                'title'
            ]
        ]);
    }
}
