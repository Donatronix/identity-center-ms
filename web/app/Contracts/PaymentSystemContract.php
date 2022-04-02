<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentSystemContract
{
    /**
     * @return mixed
     */
    public static function gateway();

    /**
     * @return mixed
     */
    public static function name();

    /**
     * @return mixed
     */
    public static function description();

    /**
     * @return integer
     */
    public static function getNewStatusId();

    /**
     * Make one-time charge money to system
     *
     * @param Payment $payment
     * @param object $inputData
     * @return mixed
     */
    public function charge(Payment $payment, object $inputData): mixed;

//    public function payout();
//
//    public function refund();

    /**
     * @param Payment $payment
     * @param object $inputData
     *
     * @return mixed
     */
    public function createInvoice(Payment $payment, object $inputData): mixed;

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return mixed
     */
    public function handlerWebhook(Request $request): mixed;
}
