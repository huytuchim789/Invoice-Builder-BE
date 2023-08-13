<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

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
                $customerEmail = $user->email;

                $totalSum = Invoice::whereHas('customer', function ($query) use ($customerEmail) {
                    $query->where('email', $customerEmail);
                })
                ->where('is_paid', true)
                ->whereMonth('created_at', $currentMonth)
                ->sum('total');
            } else {
                $senderId = $user->id;

                $totalSum = Invoice::where('sender_id', $senderId)
                    ->where('is_paid', true)
                    ->whereMonth('created_at', $currentMonth) // Filter by the current month
                    ->sum('total');
            }

            return Response::customJson(200, round($totalSum, 2), 'success');
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
                'total_sum' => round($totalSum, 2),
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

            $invoices = Invoice::with(['customer', 'user'])->where($metadataField, $metadataValue)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            return Response::customJson(200, $invoices, 'success');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
    public function getRecentlyPaidInvoices(Request $request)
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

            $invoices = Invoice::with(['customer', 'user'])
                ->where($metadataField, $metadataValue)
                ->where('is_paid', true)
                ->orderBy('created_at', 'desc')
                ->limit(8)
                ->get();

            return Response::customJson(200, $invoices, 'success');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }


}
