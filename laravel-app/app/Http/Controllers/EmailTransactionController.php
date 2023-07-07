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
        $this->middleware('check.subscription.role');

    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $page = $request->query('page')  ?? 1;
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
            $keyword = $request->query('keyword');


            // Perform the search if the search parameters are provided
            if ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereHas('invoice', function ($query) use ($keyword) {
                        $query->where('id', 'LIKE', "%$keyword%");
                    })->orWhereHas('invoice.customer', function ($query) use ($keyword) {
                        $query->where('email', 'LIKE', "%$keyword%");
                    });
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
            $emailTransactions = $query->paginate($limit, ['*'], 'page', $page);

            // Perform your desired actions with the $emailTransactions

            return Response::customJson(200, $emailTransactions, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
}
