<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function getTotalInvoiceSumInMonth(Request $request)
    {
        $currentMonth = Carbon::now()->format('m'); // Get the current month

        try {
            $user = Auth::user();

            $role = $user->role;

            if ($role === 'guest') {
                $customerId = $user->customer_id;

                $totalSum = Invoice::where('customer_id', $customerId)
                    ->where('is_paid', true)
                    ->whereMonth('created_at', $currentMonth) // Filter by the current month
                    ->sum('total');
            } else {
                $senderId = $user->id;

                $totalSum = Invoice::where('sender_id', $senderId)
                    ->where('is_paid', true)
                    ->whereMonth('created_at', $currentMonth) // Filter by the current month
                    ->sum('total');
            }

            return Response::customJson(200, $totalSum, 'success');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function getAnalytics(Request $request)
    {
        try {
            $user = Auth::user();
            $customerId = $user->customer_id;
            $senderId = $user->id;

            // Number of customers of the current user
            $customerCount = Customer::where('user_id', $user->id)->count();

            // Number of items belonging to the user's organization
            $itemCount = Item::whereHas('organization', function ($query) use ($user) {
                $query->where('id', $user->organization_id);
            })->count();

            // Sum of total invoices that the user has sent and are marked as paid
            $totalSum = Invoice::where('sender_id', $senderId)
                ->where('is_paid', true)
                ->sum('total');

            return Response::customJson(200, [
                'customer_count' => $customerCount,
                'item_count' => $itemCount,
                'total_sum' => $totalSum,
            ], 'success');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function getPaymentsHistory(Request $request)
    {
        try {
            $user = Auth::user();
            $role = $user->role;

            if ($role === 'user') {
                $metadataField = 'sender_id';
                $metadataValue = $user->id;
            } else {
                $metadataField = 'customer_id';
                $metadataValue = $user->customer_id;
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $payments = PaymentIntent::search([
                'query' => 'metadata[\'sender_id\']:'.'\''.$metadataValue.'\'',
            ]);

//            $filteredPayments = $payments->data->filter(function ($payment) use ($user) {
//                $invoiceCodes = $payment->metadata->invoice_codes ?? [];
//                return in_array($user->invoice->code, $invoiceCodes);
//            });

            return Response::customJson(200, $payments, 'success');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

}
