<?php

declare(strict_types=1);

namespace App\Modules\Payments\Http\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class GeneratePaymentIntentAction
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $request->all();
        $amount = (int) Arr::get($payload, 'data.attributes.amount', $request->query('amount', 1000));
        $currency = strtolower((string) Arr::get(
            $payload,
            'data.attributes.currency',
            $request->query('currency', config('evento.cashier_currency', 'aud'))
        ));

        $secret = (string) config('evento.stripe_secret', '');

        if ($secret !== '' && ! app()->runningUnitTests()) {
            try {
                $stripe = new StripeClient($secret);
                $paymentIntent = $stripe->paymentIntents->create([
                    'amount' => max(50, $amount),
                    'currency' => $currency,
                    'automatic_payment_methods' => ['enabled' => true],
                ]);

                return response()->json([
                    'data' => [
                        'id' => Str::random(15),
                        'type' => 'payment_intent',
                        'attributes' => [
                            'client_secret' => $paymentIntent->client_secret,
                            'currency' => $paymentIntent->currency,
                            'payment_method_options' => $paymentIntent->payment_method_options,
                            'payment_method_types' => $paymentIntent->payment_method_types,
                        ],
                    ],
                ]);
            } catch (\Throwable) {
                return response()->json(['message' => 'Error Handling Stripe Payment intent!'], 500);
            }
        }

        $intentId = 'pi_'.Str::random(24);

        return response()->json([
            'data' => [
                'id' => Str::random(15),
                'type' => 'payment_intent',
                'attributes' => [
                    'client_secret' => $intentId.'_secret_'.Str::random(24),
                    'currency' => $currency,
                    'payment_method_options' => ['card' => ['request_three_d_secure' => 'automatic']],
                    'payment_method_types' => ['card'],
                ],
            ],
        ]);
    }
}
