<?php

namespace App\Services\Payments;

use App\Contracts\PaymentSystemContract;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Exception\UnexpectedValueException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeManager implements PaymentSystemContract
{
    const STATUS_ORDER_REQUIRES_PAYMENT_METHOD = 1;
    const STATUS_ORDER_REQUIRES_CONFIRMATION = 2;
    const STATUS_ORDER_REQUIRES_ACTION = 3;
    const STATUS_ORDER_PROCESSING = 4;
    const STATUS_ORDER_REQUIRES_CAPTURE = 5;
    const STATUS_ORDER_CANCELED = 6;
    const STATUS_ORDER_SUCCEEDED = 7;
    const STATUS_ORDER_PAID = 8;
    const STATUS_ORDER_UNPAID = 9;
    const STATUS_ORDER_NO_PAYMENT_REQUIRED = 10;

    /**
     * @var array
     */
    private array $gateway;

    /**
     * constructor.
     */
    public function __construct()
    {
        $this->gateway = [];

        // Set your secret API key
        Stripe::setApiKey(config('payments.stripe.secret_key'));
    }

    public static function gateway(): string
    {
        return 'stripe';
    }

    public static function name(): string
    {
        return 'Stripe';
    }

    public static function description(): string
    {
        return 'Stripe is..';
    }

    public static function getNewStatusId(): int
    {
        return self::STATUS_ORDER_REQUIRES_PAYMENT_METHOD;
    }

    /**
     * @param Payment $payment
     * @param object $inputData
     * @return array
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function charge(Payment $payment, object $inputData): array
    {
        try {
            // Create a PaymentIntent with amount and currency
            $stripeDocument = \Stripe\PaymentIntent::create([
                'amount' => $inputData->amount,
                'currency' => mb_strtolower($inputData->currency),
//                'automatic_payment_methods' => [
//                    'enabled' => true,
//                ],
                'payment_method_types' => [
//                    'acss_debit',
                    'afterpay_clearpay',
                    'alipay',
//                    'au_becs_debit',
//                    'grabpay',
//                    'bancontact',
//                    'boleto',
//                    'bacs_debit',
                    'card',
//                    'eps',
//                    'giropay',
//                    'fpx',
//                    'ideal',
                    'klarna',
//                    'p24',
//                    'oxxo',
//                    'sepa_debit',
//                    'sofort',
                    'wechat_pay'
                ]
            ]);

            // Update order data
            $payment->status = self::STATUS_ORDER_REQUIRES_PAYMENT_METHOD;
            $payment->document_id = $stripeDocument->id;
            $payment->save();

            // Return result
            return [
                'type' => 'success',
                'title' => 'Stripe checkout payment session creating',
                'message' => "Session successfully created",
                'data' => [
                    'gateway' => self::gateway(),
                    'payment_id' => $payment->id,
                    'session_id' => $stripeDocument->id,
                    'public_key' => config('payments.stripe.public_key'),
                    'clientSecret' => $stripeDocument->client_secret,
                ]
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'danger',
                'title' => 'Stripe checkout payment session creating',
                'message' => sprintf("Unable to create an session. Error: %s \n", $e->getMessage()),
                'data' => []
            ];
        }
    }

    /**
     * Wrapper for create Stripe invoice for charge money
     *
     * @param Payment $payment
     * @param object $inputData
     * @return array
     */
    public function createInvoice(Payment $payment, object $inputData): array
    {
        try {
            // Create checkout session
            $checkout_session = \Stripe\Checkout\Session::create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'amount' => $inputData->amount * 100,
                    'currency' => $inputData->currency,
                    'quantity' => 1,
                    'name' => 'Wallet Charge',
                ]],
                'metadata' => [
                    'payment_order' => $payment->id,
                    'check_code' => $payment->check_code,
                ],
                'success_url' => env('PAYMENTS_WEBHOOK_URL') . '?success=true',
                'cancel_url' => env('PAYMENTS_WEBHOOK_URL') . '?canceled=true'
            ]);

            // Update order data
            $payment->status = self::STATUS_ORDER_REQUIRES_PAYMENT_METHOD;
            $payment->document_id = $checkout_session->id;
            $payment->save();

            // Return result
            return [
                'type' => 'success',
                'title' => 'Stripe checkout payment session creating',
                'message' => "Session successfully created",
                'data' => [
                    'gateway' => self::gateway(),
                    'payment_id' => $payment->id,
                    'session_id' => $checkout_session->id,
                    'session_url' => $checkout_session->url,
                    'public_key' => config('payments.stripe.public_key')
                ]
            ];
        } catch (\Exception $e) {
            return [
                'type' => 'danger',
                'title' => 'Stripe checkout payment session creating',
                'message' => sprintf("Unable to create an session. Error: %s \n", $e->getMessage()),
                'data' => []
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
        $event = null;
        $payload = $request->getContent();

        if (env("APP_DEBUG", 0)) {
            Log::info($request->headers);
            Log::info($payload);
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null),
                config('payments.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            $result = [
                'type' => 'danger',
                'message' => "Stripe Webhook: Invalid payload: " . $e->getMessage(),
                'payload' => $payload
            ];

            if (env("APP_DEBUG", 0)) {
                Log::error($result);
            }

            return $result;
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            $result = [
                'type' => 'danger',
                'message' => "Stripe Webhook: Invalid signature: " . $e->getMessage(),
                'payload' => $payload
            ];

            if (env("APP_DEBUG", 0)) {
                Log::error($result);
            }

            return $result;
        }

        if (env("APP_DEBUG", 0)) {
            Log::info('### START EVENT OBJECT ###');
            Log::info($event);
            Log::info('### FINISH EVENT OBJECT ###');
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
            case 'checkout.session.async_payment_failed':

            case 'payment_intent.amount_capturable_updated':
            case 'payment_intent.canceled':
            case 'payment_intent.created':
            case 'payment_intent.payment_failed':
            case 'payment_intent.processing':
            case 'payment_intent.requires_action':
            case 'payment_intent.succeeded':
                // Read contains a StripePaymentIntent
                $stripeDocument = $event->data->object;

                if (env("APP_DEBUG", 0)) {
                    Log::info($stripeDocument);
                }

                // Return result
                return [
                    'type' => 'success',
                    'stripeDocument' => $stripeDocument
//                    'payment_id' => $payment->id,
//                    'service' => $payment->service,
//                    'amount' => $payment->amount,
//                    'currency' => $payment->currency,
//                    'user_id' => $payment->user_id,
//                    'payment_completed' => (self::STATUS_ORDER_SUCCEEDED === $payment->status),
                ];

            default:
                $result = [
                    'type' => 'info',
                    'message' => 'Stripe Webhook: Received unsupported event type ' . $event->type,
                    'payload' => $payload
                ];

                Log::info($result);

                return $result;
        }
    }
}
