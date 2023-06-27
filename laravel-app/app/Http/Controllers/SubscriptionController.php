<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class SubscriptionController extends Controller
{

    public function __constructor()
    {
        $this->middleware('auth:api');
        Stripe::setApiKey(env('STRIPE_SECRET'));
    }

    public function subscribe(Request $request)
    {
        $user = Auth::user();
        $stripeCustomerId = $user->stripe_id;
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $stripeCustomer = Customer::retrieve($stripeCustomerId);
        $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method;
        try {

            $user->newSubscription('default', 'price_1NMRqvLt2JAaPrAX7C2OVfyG')->create($defaultPaymentMethodId, [
                'email' => $user->email,
            ]);

            return Response::customJson(200, $stripeCustomer, 'Subscription successful');
        } catch (IncompletePayment $exception) {
            return Response::customJson(500, null, 'Subscription failed');
        }
    }

    public function checkCard(Request $request)
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
        $user = Auth::user();
        $paymentMethodType = $request->input('type');
        $paymentMethodDetails = $request->input('details');
        $user->createOrGetStripeCustomer();
        try {
            $paymentMethod = PaymentMethod::create([
                'type' => $paymentMethodType,
                $paymentMethodType => $paymentMethodDetails,
            ]);

            // Attach the payment method to the user
            $user->updateDefaultPaymentMethod($paymentMethod->id);

            return Response::customJson(200, $paymentMethod, 'Payment method created');
        } catch (\Exception $e) {
            // Handle any errors that occurred during the creation
            return Response::customJson(500, null, $e->getMessage());
        }

    }

    public function detachPaymentMethod()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();
            $stripeCustomerId = $user->stripe_id;
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $stripeCustomer = Customer::retrieve($stripeCustomerId);
            $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method;
            // Detach the payment method
            $paymentMethod = PaymentMethod::retrieve($defaultPaymentMethodId);
            $paymentMethod->detach();

            return response()->json(['message' => 'Payment method detached successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

}
