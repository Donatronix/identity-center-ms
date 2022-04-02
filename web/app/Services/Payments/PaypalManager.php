<?php

namespace App\Services\Payments;

use App\Contracts\PaymentSystemContract;
use App\Models\Payment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCreateRequest;

class PaypalManager implements PaymentSystemContract
{
    /**
     * Order / Invoice statuses
     */
    // The order was created with the specified context
    const STATUS_ORDER_CREATED = 1;

    // The order was saved and persisted. The order status continues to be in progress until
    // a capture is made with final_capture = true for all purchase units within the order.
    const STATUS_ORDER_SAVED = 2;

    // The customer approved the payment through the PayPal wallet or another form of guest
    // or unbranded payment. For example, a card, bank account, or so on.
    const STATUS_ORDER_APPROVED = 3;

    // All purchase units in the order are voided.
    const STATUS_ORDER_VOIDED = 4;

    // The payment was authorized or the authorized payment was captured for the order
    const STATUS_ORDER_COMPLETED = 5;

    // The order requires an action from the payer (e.g. 3DS authentication).
    // Redirect the payer to the "rel":"payer-action" HATEOAS link returned as part
    // of the response prior to authorizing or capturing the order.
    const STATUS_ORDER_PAYER_ACTION_REQUIRED = 6;

    /**
     * @var \PayPalCheckoutSdk\Core\PayPalHttpClient
     */
    private $gateway;

    /**
     * BitPayManager constructor.
     */
    public function __construct()
    {
        if (config('payments.paypal.mode') === 'sandbox') {
            $environment = new SandboxEnvironment(
                config('payments.paypal.sandbox.client_id'),
                config('payments.paypal.sandbox.client_secret')
            );
        } else {
            $environment = new ProductionEnvironment(
                config('payments.paypal.live.client_id'),
                config('payments.paypal.live.client_secret')
            );
        }

        $this->gateway = new PayPalHttpClient($environment);
    }

    public static function name(): string
    {
        return 'PayPal';
    }

    public static function description(): string
    {
        return 'PayPal payment is..';
    }

    public static function gateway(): string
    {
        return 'paypal';
    }

    public static function getNewStatusId(): int
    {
        return self::STATUS_ORDER_CREATED;
    }

    /**
     * Wrapper for create paypal invoice for charge money
     *
     * @param array $input
     *
     * @return mixed|void
     */
    public function createInvoice(Payment $payment, object $inputData): array
    {
        try {
            // Create new charge
            $request = new OrdersCreateRequest();
            $request->prefer('return=representation');
            $request->body = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'description' => 'Charge Balance for Sumra User',
                        'custom_id' => $payment->check_code,
                        'invoice_id' => $payment->id,
                        'amount' => [
                            'value' => $inputData->amount,
                            'currency_code' => $inputData->currency,
                        ],
                        'payment_instruction' => [
                            'disbursement_mode' => 'INSTANT'
                        ],
                        'soft_descriptor' => 'SUMRA.NET',
                    ]
                ],
                'application_context' => [
                    'brand_name' => 'INFINITY SUMRA NET',
                    'locale' => 'en-US',
                    'landing_page' => 'NO_PREFERENCE',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => env('PAYMENTS_REDIRECT_URL'),
                    'cancel_url' => env('PAYMENTS_REDIRECT_URL'),
                ]
            ];
            $chargeObj = $this->gateway->execute($request);

            // Update payment transaction data
            $payment->status = self::STATUS_ORDER_CREATED;
            $payment->document_id = $chargeObj->result->id;
            $payment->save();

            $invoiceUrl = '';
            foreach ($chargeObj->result->links as $link) {
                if ($link->rel === 'approve') {
                    $invoiceUrl = $link->href;
                }
            }

            return [
                'type' => 'success',
                'gateway' => self::gateway(),
                'payment_id' => $payment->id,
                'invoice_url' => $invoiceUrl
            ];
        } catch (Exception $e) {
            return [
                'type' => 'danger',
                'message' => sprintf("Unable to create an order. Error: %s \n", $e->getMessage())
            ];
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function handlerWebhook(Request $request): array
    {
        \Log::info($request);
        // Check sender
        if (!Str::contains($request->header('User-Agent'), 'PayPal')) {
            return [
                'type' => 'danger',
                'message' => 'Payload was sent not from PayPal'
            ];
        }

        // Get event data
        $paymentData = $request->get('resource', null);
        if ($paymentData === null) {
            return [
                'type' => 'danger',
                'message' => 'Empty / Incorrect event data'
            ];
        }

        // Find payment transaction
        $payment = Payment::where('type', Payment::TYPE_INVOICE)
            ->where('id', $paymentData["purchase_units"][0]["invoice_id"])
            ->where('document_id', $paymentData["id"])
            ->where('check_code', $paymentData["purchase_units"][0]["custom_id"])
            ->where('gateway', self::gateway())
            ->first();

        if (!$payment) {
            return [
                'type' => 'danger',
                'message' => 'Payment transaction not found in Payment Microservice database'
            ];
        }

        // Update payment transaction status
        $status = 'STATUS_ORDER_' . mb_strtoupper($paymentData["status"]);
        $payment->status = constant("self::{$status}");
       // $payment->payload = $paymentData;
        $payment->save();

        // Return result
        return [
            'status' => 'success',
            'payment_id' => $payment->id,
            'service' => $payment->service,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'user_id' => $payment->user_id,
            'payment_completed' => (self::STATUS_ORDER_COMPLETED === $payment->status),
        ];
    }

    public function charge(Payment $payment, object $inputData): mixed
    {
        // TODO: Implement charge() method.
    }
}
