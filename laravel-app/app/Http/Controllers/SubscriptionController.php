<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
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
            if ($user->subscription('default')->onTrial()) {
                $subscription = $user->subscription('default');
                $subscription->resume(); // Resume subscription
                $subscription->swap('price_1NMRqvLt2JAaPrAX7C2OVfyG'); // Swap to new subscription plan

                return Response::customJson(200, $subscription, 'Subscription plan updated during trial');
            }
            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
            }

            $subcription = $user->newSubscription('default', 'price_1NMRqvLt2JAaPrAX7C2OVfyG')->create($defaultPaymentMethodId, [
                'email' => $user->email,
            ]);

            return Response::customJson(200, $subcription, 'Subscription successful');
        } catch (IncompletePayment $exception) {
            return Response::customJson(500, null, 'Subscription failed');
        }
    }

    public function trialSubscription(Request $request)
    {
        $user = Auth::user();
        $stripeCustomerId = $user->stripe_id;
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $stripeCustomer = Customer::retrieve($stripeCustomerId);
        $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method;

        try {
            if ($user->subscribed('default') && $user->subscription('default')->onTrial()) {
                return Response::customJson(403, null, 'Already on a trial or another subscription');
            }

            $trialEndDate = Carbon::now()->addDays(7); // Set the trial end date to 7 days from now

            $trialSubcription = $user->newSubscription('default', 'price_1NMRqvLt2JAaPrAX7C2OVfyG')
                ->trialUntil($trialEndDate)
                ->create($defaultPaymentMethodId, [
                    'email' => $user->email,
                ]);

            return Response::customJson(200, $trialSubcription, 'Trial subscription created successfully');
        } catch (IncompletePayment $exception) {
            return Response::customJson(500, null, 'Trial subscription failed');
        }
    }


    public function checkSubcription(Request $request)
    {
        try {
            $user = Auth::user();

            if ($user->subscribed('default')) {
                $subscription = $user->subscription('default');


                return Response::customJson(200, $subscription, null);
            }
            return Response::customJson(200, ["data" => null], 'No Subcription found');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function checkCard()
    {
        try {
            $user = Auth::user();
            $card = $user->defaultPaymentMethod();
            if (!$card) {
                return Response::customJson(200, null, 'No card found');
            }
            return Response::customJson(200, null, 'Card retrieved');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function cancelSubscription(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->subscribed('default')) {
                $user->subscription('default')->cancelNow();
                return Response::customJson(200, null, 'Cancel Subscription cancelled');
            }
            if ($user->onTrial('default')) {
                $user->subscription('default')->endTrial();
                return Response::customJson(200, null, 'Cancel Subscription Trial cancelled');

            }
        } catch (\Exception $e) {
            return Response::customJson(500, $user->subscribed('default'), $e->getMessage());

        }
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
