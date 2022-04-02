<?php

namespace App\Listeners;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Class RechargeBalanceTransactionListener
 *
 * @package App\Listeners
 */
class RechargeBalanceTransactionListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param array $inputData
     *
     * @return void
     */
    public function handle(array $inputData)
    {
        // Validate input
        $validation = Validator::make($inputData, [
            'payment_id' => 'integer|required',
        ]);

        if ($validation->fails()) {
            Log::info($validation->errors());

            exit;
        }

        // Get or check order
        try{
            $payment = Payment::findOrFail($inputData['payment_id']);
        }catch(\Exception $e){
            Log::info('Recharge balance transaction listener error: ' . $e->getMessage());

            exit;
        }

        // Update payment/ Set flag, transaction is created
        $payment->transaction_created = true;
        $payment->save();
    }
}
