<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class SubscriptionController extends Controller
{

    public function __constructor()
    {
        $this->middleware('auth:api');
    }

    public function subscribe(Request $request)
    {
        $user = Auth::user();
        $paymentMethod = $request->paymentMethodId;
        try {
            $user->createOrGetStripeCustomer();

            $user->newSubscription('default', 'price_1NMRqvLt2JAaPrAX7C2OVfyG')->create($paymentMethod, [
                'email' => $user->email,
            ]);

            return Response::customJson(200, null, 'Subscription successful');
        } catch (IncompletePayment $exception) {
            return Response::customJson(500, null, 'Subscription failed');
        }
    }

    public function cancelSubscription(Request $request)
    {
        $user = $request->user();

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancel();
            return Response::customJson(200, null, 'Subscription cancelled');
        }

        return Response::customJson(500, null, 'Subscription failed');
    }

    public function createPaymentMethod(Request $request)
    {
        // Set your Stripe API secret key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $paymentMethodType = $request->input('type');
        $paymentMethodDetails = $request->input('details');

        try {
            $paymentMethod = PaymentMethod::create([
                'type' => $paymentMethodType,
                $paymentMethodType => $paymentMethodDetails,
            ]);

            // $paymentMethod now contains the created payment method

            return Response::customJson(200, $paymentMethod, 'Payment method created');
        } catch (\Exception $e) {
            // Handle any errors that occurred during the creation
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function checkSubscription(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->subscribed('default')) {
                $subscription = $user->subscription('default');
                $status = $subscription->stripe_status;
                $plan = $subscription->stripe_price;

                $card = $user->defaultPaymentMethod();

                return Response::customJson(200, [
                    'status' => $status,
                    'plan' => $plan,
                    'card' => $card,
                ], 'Subscription status retrieved');
            }
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }

    }
}
