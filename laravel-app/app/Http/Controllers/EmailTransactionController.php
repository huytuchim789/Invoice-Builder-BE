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
            $page = $request->query('page') || 1;
            if (!$user) {
                // User not authenticated
                // Handle the scenario accordingly
            }

            $emailTransactions = EmailTransaction::with('invoice') // Eager load the invoice relationship
                ->whereIn('invoice_id', function ($query) use ($user) {
                    $query->select('id')
                        ->from('invoices')
                        ->where('sender_id', $user->id);
                })
                ->simplePaginate(10, ['*'], 'page', $page);

            // Perform your desired actions with the $emailTransactions

            return  Response::customJson(200, $emailTransactions, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
}
