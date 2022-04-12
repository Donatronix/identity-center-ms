<?php

namespace App\Services\Payments;

use App\Contracts\PaymentSystemContract;
use App\Models\Payment;
use BitPaySDK\Client;
use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Invoice\Invoice;
use BitPaySDK\Tokens;
use Exception;
use Illuminate\Http\Request;

class BitpayManager implements PaymentSystemContract
{
    /**
     * Invoice statuses
     */
    // New
    const STATUS_INVOICE_NEW = 1000;

    // To notify merchant that an invoice has reached the status paid
    const STATUS_INVOICE_PAID_IN_FULL = 1003;

    // To notify a merchant that an invoice has expired without being paid
    const STATUS_INVOICE_EXPIRED = 1004;

    // To notify merchant that an invoice has reached the status confirmed
    const STATUS_INVOICE_CONFIRMED = 1005;

    // To notify merchant that an invoice has reached the status completed
    const STATUS_INVOICE_COMPLETED = 1006;

    // To notify merchant that an invoice has reached the status invalid
    const STATUS_INVOICE_FAILED_TO_CONFIRM = 1013;

    // To notify a merchant that a refund request has been successfully processed
    const STATUS_INVOICE_REFUND_COMPLETE = 1016;

    /**
     * @var \BitPaySDK\Client
     */
    private $gateway;

    /**
     * BitPayManager constructor.
     */
    public function __construct()
    {
        try {
            // Initialize with separate variables and Private Key stored in file.
            $this->gateway = Client::create()->withData(
                config('payments.bitpay.environment'),
                config('payments.bitpay.private_key.path'),
                new Tokens(
                    config('payments.bitpay.api_tokens.merchant'), //merchant
                    config('payments.bitpay.api_tokens.payroll') //payroll
                ),
                config('payments.bitpay.private_key.password')
            );

        } catch (BitPayException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public static function name(): string
    {
        return 'BitPay';
    }

    public static function description(): string
    {
        return 'BitPay is..';
    }

    public static function gateway(): string
    {
        return 'bitpay';
    }

    public static function getNewStatusId(): int
    {
        return self::STATUS_INVOICE_NEW;
    }

    /**
     * Wrapper for create bitpay invoice for charge money
     *
     * @param Payment $payment
     * @param array $input
     * @return array
     */
    public function createInvoice(Payment $payment, object $inputData): array
    {
        try {
            // Set invoice detail
            $invoice = new Invoice($inputData->amount, $inputData->currency);

            $invoice->setOrderId($payment->id);

            $invoice->setFullNotifications(true);
            $invoice->setExtendedNotifications(true);
            $invoice->setNotificationURL(config('payments.bitpay.webhook_url') . '/bitpay/invoices');
            $invoice->setRedirectURL(config('payments.bitpay.redirect_url'));
            $invoice->setPosData(json_encode(['code' => $payment->check_code]));
            $invoice->setItemDesc("Charge Balance for Sumra User");

            // Send data to bitpay and get created invoice
            $chargeObj = $this->gateway->createInvoice($invoice);

            // Update payment transaction data
            $payment->status = self::STATUS_INVOICE_NEW;
            $payment->document_id = $chargeObj->getId();
            $payment->save();

            return [
                'type' => 'success',
                'gateway' => self::gateway(),
                'payment_id' => $payment->id,
                'invoice_url' => $chargeObj->getURL()
            ];
        } catch (Exception $e) {
            return [
                'type' => 'danger',
                'message' => sprintf("Unable to create an invoice. Error: %s \n", $e->getMessage())
            ];
        }
    }

    public function charge(Payment $payment, object $inputData): mixed
    {
        try {
            // Set invoice detail
            $invoice = new Invoice($inputData->amount, $inputData->currency);
            $invoice->setOrderId($payment->id);
            $invoice->setFullNotifications(true);
            $invoice->setExtendedNotifications(true);
            $invoice->setNotificationURL(config('payments.bitpay.webhook_url') . '/bitpay/invoices');
            $invoice->setRedirectURL(config('payments.bitpay.redirect_url'));
            $invoice->setPosData(json_encode(['code' => $payment->check_code]));
            $invoice->setItemDesc("Charge Balance for Sumra User");

            // Send data to bitpay and get created invoice
            $chargeObj = $this->gateway->createInvoice($invoice);

            // Update payment transaction data
            $payment->status = self::STATUS_INVOICE_NEW;
            $payment->document_id = $chargeObj->getId();
            $payment->save();

            return [
                'type' => 'success',
                'gateway' => self::gateway(),
                'payment_id' => $payment->id,
                'invoice_url' => $chargeObj->getURL()
            ];
        } catch (Exception $e) {
            return [
                'type' => 'danger',
                'message' => sprintf("Unable to create an invoice. Error: %s \n", $e->getMessage())
            ];
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array|string[]
     */
    public function handlerWebhook(Request $request): array
    {
        \Log::info($request);
        // Check event property
        if (!$request->has('event')) {
            return [
                'type' => 'danger',
                'message' => 'Empty / Incorrect event data'
            ];
        }

        // Get event data
        $paymentData = $request->get('data', null);
        if ($paymentData === null) {
            return [
                'type' => 'danger',
                'message' => 'Empty / Incorrect event data'
            ];
        }

        $paymentData['posData'] = json_decode($paymentData['posData']);

        // Find payment transaction
        $payment = Payment::where('type', Payment::TYPE_INVOICE)
            ->where('id', $paymentData['orderId'])
            ->where('document_id', $paymentData['id'])
            ->where('check_code', $paymentData['posData']->code)
            ->where('gateway', self::gateway())
            ->first();

        if (!$payment) {
            return [
                'type' => 'danger',
                'message' => 'Payment transaction not found in Payment Microservice database'
            ];
        }

        $payment->status = $request->event['code'];

       // $payment->payload = $paymentData;
        $payment->save();

        // Return result
        return [
            'status' => 'success',
            'payment_id' => $payment->id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'service' => $payment->service,
            'user_id' => $payment->user_id,
            'payment_completed' => (self::STATUS_INVOICE_COMPLETED === $payment->status),
        ];
    }
}
