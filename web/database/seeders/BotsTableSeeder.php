<?php

namespace Database\Seeders;

use App\Models\Bot;
use Illuminate\Database\Seeder;

class BotsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $list = [
            [
                'id' => 'e3276487-349a-4959-a616-e3276487f478',
                'token' => 'ACf3a58e9402a2833cea9a69115f66d2ec:8ce1f68bbbc6dc0725310d633b1ca498',
                'number' => '+447787572622',
                'type' => 'whatsapp'
            ],
            [
                'id' => '373458be-3f01-40ca-b6f3-245239c7889f',
                'token' => '2088982449:AAHJ7d16HCpFI9j9B9JqOX1yDEgSb5piKmc',
                'type' => 'telegram'
            ],
            [
                'id' => '456c0391-9502-4081-b215-d070c2f803f2',
                'token' => '4e2f1b3446e7d9c2-ed5513c52400990a-9306a458fd9fef70',
                'type' => 'viber'
            ],
            [
                'id' => '37f1ec65-3a26-407a-9b8a-ac98673d00c0',
                'token' => 'OTAyOTE3MTAyMTg3NDUwNDU4.YXlZFA.2Vtm5-rhiia6TyPqS_f1Er6nTVY',
                'type' => 'discord'
            ],
            [
                'id' => '9bdcdcee-4673-452d-a4f9-bc3c3cd7b2b3',
                'token' => 'aVKv550IUP4dU\/HVyQsYVGZelAyLnp1+LlSnK6MQN7RKKzlCaSMiyI40dGL7fv5aRm3LyEeNlQ2XMwuIa9b45+SdM0XLQsZEO2qjj9HcYIyHOOff4LfGGIkCU4UXAypTRb8G0L\/Zzh1+dNBrNz5m8gdB04t89\/1O\/w1cDnyilFU=',
                'type' => 'line'
            ],
            [
                'id' => 'fd9b3d51-04b2-4854-ae71-2da84b482ec6',
                'token' => 'ACf3a58e9402a2833cea9a69115f66d2ec:8ce1f68bbbc6dc0725310d633b1ca498',
                'number' => '+17859758117',
                'type' => 'twilio'
            ]
        ];

        foreach ($list as $item) {
            Bot::create($item);
        }
    }
}
