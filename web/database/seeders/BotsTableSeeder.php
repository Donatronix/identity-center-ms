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
                'name' => 'WhatsApp Bot',
                'uri' => 'https://wa.me/+447787572622?text=join',
                'sid' => 'ACf3a58e9402a2833cea9a69115f66d2ec',
                'token' => '8ce1f68bbbc6dc0725310d633b1ca498',
                'secret' => '',
                'number' => '+447787572622',
                'type' => 'whatsapp',
                'platform' => 'ULTAINFINITY',
                'status' => true,
            ],
            [
                'id' => '3ac69b47-349a-4959-a616-3ac69b45f478',
                'name' => 'WhatsApp Bot (Testing)',
                'uri' => 'https://wa.me/+14155238886?text=join%20putting-itself',
                'sid' => 'ACd41c7867838093709600bb6542863769',
                'token' => '4a2ead7900e376b2e12c3a46dca6d0db',
                'secret' => '',
                'number' => '+14155238886',
                'type' => 'whatsapp',
                'platform' => 'ULTAINFINITY',
                'status' => false,
            ],
            [
                'id' => '3ac69b45-349a-4959-a616-3ac69b45aadd',
                'name' => 'SMS by Twilio',
                'uri' => '',
                'sid' => 'ACf3a58e9402a2833cea9a69115f66d2ec',
                'token' => '8ce1f68bbbc6dc0725310d633b1ca498',
                'secret' => '',
                'number' => '+18647321310',
                'type' => 'twilio',
                'platform' => 'ULTAINFINITY',
                'status' => true,
            ],
            [
                'id' => 'e3276487-349a-4959-a616-e3276487aadd',
                'name' => 'SMS by Twilio (Testing)',
                'uri' => '',
                'sid' => 'ACd41c7867838093709600bb6542863769',
                'token' => '4a2ead7900e376b2e12c3a46dca6d0db',
                'secret' => '',
                'number' => '+19207827608',
                'type' => 'twilio',
                'platform' => 'ULTAINFINITY',
                'status' => false,
            ],
            [
                'id' => 'e3276487-349a-4959-a616-3ac69b45f253',
                'name' => 'OneStepID by Sumra',
                'uri' => '@OneStepID_Sumra_Bot',
                'token' => '2078755563:AAHtPzW2xyqngQxbyZSh0U821oRdMeankn8',
                'platform' => 'SUMRA',
                'type' => 'TELEGRAM',
                'status' => true,
            ],
            [
                'id' => '373458be-3f01-40ca-b6f3-245239c7889f',
                'name' => 'OneStepID by Ultainfinity',
                'uri' => '@OneStepID_Ultainfinity_Bot',
                'token' => '2088982449:AAHJ7d16HCpFI9j9B9JqOX1yDEgSb5piKmc',
                'platform' => 'ULTAINFINITY',
                'type' => 'TELEGRAM',
                'status' => true,
            ],
            [
                'id' => 'a126ae9d-f55a-443e-ad3f-b1cb20d5e1f1',
                'name' => 'Sumra by OneStep',
                'uri' => 'sumrabyonestep',
                'token' => '4e2f25788667df4c-5ba2bdeaf5991ff4-b4956e9dba79982',
                'platform' => 'SUMRA',
                'type' => 'VIBER',
                'status' => true,
            ],
            [
                'id' => '456c0391-9502-4081-b215-d070c2f803f2',
                'name' => 'Ultainfinity by OneStep',
                'uri' => 'ultainfinitybyonestep',
                'token' => '4e2f1b3446e7d9c2-ed5513c52400990a-9306a458fd9fef70',
                'platform' => 'ULTAINFINITY',
                'type' => 'VIBER',
                'status' => true,
            ],
            [
                'id' => '498f8236-568d-4446-b0f6-693dfbb6915c',
                'name' => 'SumraBot by OneStep',
                //'uri' => '900773740160376843',
                'uri' => 'https://discord.gg/75xbhgmbvP',
                'token' => 'OTAwNzczNzQwMTYwMzc2ODQz.YXGM6w.B9B9uaFnz86dwZE5LiCb3ctORrk',
                'platform' => 'SUMRA',
                'type' => 'DISCORD',
                'status' => true,
            ],
            [
                'id' => '37f1ec65-3a26-407a-9b8a-ac98673d00c0',
                'name' => 'UltainfinityBot By OneStep',
                //'uri' => '902917102187450458',
                'uri' => 'https://discord.gg/DUMwfyckKy',
                'token' => 'OTAyOTE3MTAyMTg3NDUwNDU4.YXlZFA.2Vtm5-rhiia6TyPqS_f1Er6nTVY',
                'platform' => 'ULTAINFINITY',
                'type' => 'DISCORD',
                'status' => true,
            ],
            [
                'id' => '9bdcdcee-4673-452d-a4f9-bc3c3cd7b2b3',
                'name' => 'Ultainfinity OneStep',
                'uri' => '@410jqinx',
                'token' => 'aVKv550IUP4dU\/HVyQsYVGZelAyLnp1+LlSnK6MQN7RKKzlCaSMiyI40dGL7fv5aRm3LyEeNlQ2XMwuIa9b45+SdM0XLQsZEO2qjj9HcYIyHOOff4LfGGIkCU4UXAypTRb8G0L\/Zzh1+dNBrNz5m8gdB04t89\/1O\/w1cDnyilFU=',
                'platform' => 'ULTAINFINITY',
                'type' => 'LINE',
                'status' => true,
            ],
            [
                'id' => 'a886ec79-9254-426c-b4a5-80b8a1d77d1e',
                'name' => 'Sumra by OneStep',
                'uri' => '@587eedqw',
                'token' => 'iJzFgGgCWhhF6MuJKCr9nJzmrLadgvvYtnND\/1L5PU52qNfndGFNNrBtpySwTZVcSYrp54SSFcrUiwxS2CtmGiuHKzYzoeojizpQJhHyH2z98L8K3fIYRhmRTVCueDnbCsWImEp8JixgWN+wZ7ZutQdB04t89\/1O\/w1cDnyilFU=',
                'platform' => 'SUMRA',
                'type' => 'LINE',
                'status' => true,
            ],
            [
                'id' => 'fd9b3d51-04b2-4854-ae71-2da84b482ec6',
                'name' => 'Sumra by OneStep',
                'uri' => '',
                'token' => 'ACf3a58e9402a2833cea9a69115f66d2ec:8ce1f68bbbc6dc0725310d633b1ca498',
                'platform' => 'SUMRA',
                'type' => 'TWILIO',
                'status' => true,
            ],
        ];

        foreach ($list as $item) {
            Bot::create($item);
        }
    }
}
