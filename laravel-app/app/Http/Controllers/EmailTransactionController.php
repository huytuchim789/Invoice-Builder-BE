<?php

namespace App\Http\Controllers;

use App\Models\EmailTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class EmailTransactionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $page = $request->query('page') + 1 ?? 1;
            $limit = $request->query('limit')  ?? 10;
            $status = $request->query('status');
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            if (!$user) {
                // User not authenticated
                // Handle the scenario accordingly
            }

            $query = EmailTransaction::with(['invoice.customer','invoice.media']) // Eager load the invoice relationship
                ->whereIn('invoice_id', function ($query) use ($user) {
                    $query->select('id')
                        ->from('invoices')
                        ->where('sender_id', $user->id);
                })->orderBy('created_at', 'desc')
                ->orderBy('updated_at', 'desc');;

            // Retrieve the search parameters from the request
            $customerEmail = $request->query('customer_email');
            $invoiceId = $request->query('invoice_id');

            // Perform the search if the search parameters are provided
            if ($customerEmail) {
                $query->whereHas('invoice.customer', function ($query) use ($customerEmail) {
                    $query->where('email', 'LIKE', "%$customerEmail%");
                });
            }

            if ($invoiceId) {
                $query->whereHas('invoice', function ($query) use ($invoiceId) {
                    $query->where('id', $invoiceId);
                });
            }
            if ($status) {
                $query->where('status', $status);
            }
            if ($startDate && $endDate) {
                $query->whereHas('invoice', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('issued_date', [$startDate, $endDate]);
                });
            }
            $emailTransactions = $query->simplePaginate($limit, ['*'], 'page', $page);

            // Perform your desired actions with the $emailTransactions

            return Response::customJson(200, $emailTransactions, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
}
