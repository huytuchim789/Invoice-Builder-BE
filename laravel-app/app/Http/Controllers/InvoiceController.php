<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StoreSendEmailTransactionRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Jobs\SendMailJob;
use App\Models\EmailTransaction;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try {
            $invoices = Invoice::with(['items', 'customer'])->paginate(15);
            return Response::customJson(200, $invoices, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\StoreInvoiceRequest $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $invoice = $this->saveInvoice(array_merge($validatedData, ["sender_id" => auth()->user()->id]));
            $emailTransaction = EmailTransaction::create([
                'invoice_id' => $invoice->id,
                'status' => 'draft',
            ]);
            // Return a response indicating success
            return Response::customJson(200, $emailTransaction, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['items', 'customer'])->find($id);
            if (!$invoice)
                return Response::customJson(404, $invoice, "Not Found");
            return Response::customJson(200, $invoice, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\UpdateInvoiceRequest $request
     * @param \App\Models\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //
    }

    public function sendEmail(StoreSendEmailTransactionRequest $request)
    {
        try {
            $file = $request->file('file');
            $filePath = $file->store('', 'temporary');

            $invoice = Invoice::find($request->invoice_id);
            if (!$invoice) {
                return Response::customJson(404, null, "Invoice not found");
            }

            $existingTransaction = EmailTransaction::where('invoice_id', $request->invoice_id)->first();
            if ($existingTransaction) {
                if ($existingTransaction->status == 'sent' || $existingTransaction->status == 'failed') {
                    $emailTransaction = $existingTransaction;
                    $message = "Resend Successfully";
                }
            } else {
                // Create a new email transaction
                $emailTransaction = EmailTransaction::create([
                    'invoice_id' => $invoice->id,
                    'status' => 'pending',
                ]);
                $message = "Send Successfully";
            }

            $emailInfo = [
                "subject" => $request->subject ?? '',
                "message" => $request->message ?? '',
            ];

            dispatch(new SendMailJob($emailTransaction, $filePath, $emailInfo, auth()->user()));

            return Response::customJson(200, null, $message);
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }


    private function saveInvoice(mixed $validatedData)
    {
        // Retrieve validated data from the request

        // Create a new invoice
        $invoice = new Invoice();
        $invoice->issued_date = $validatedData['issued_date'];
        $invoice->created_date = $validatedData['created_date'];
        $invoice->note = $validatedData['note'];
        $invoice->tax = $validatedData['tax'];
        $invoice->sale_person = $validatedData['sale_person'];
        $invoice->sender_id = $validatedData['sender_id'];
        $invoice->customer_id = $validatedData['customer_id'];
        $invoice->total = $validatedData['total'];
        $invoice->save();

        // Prepare item data for mass insertion
        $itemsData = [];
        foreach ($validatedData['items'] as $itemData) {
            $itemsData[] = [
                'id' => Str::uuid()->toString(),
                'name' => $itemData['name'],
                'description' => $itemData['description'],
                'cost' => $itemData['cost'],
                'hours' => $itemData['hours'],
                'price' => $itemData['price'],
                'invoice_id' => $invoice->id,
            ];
        }

        // Insert items into the database in a single query
        Item::insert($itemsData);
        return $invoice;
    }
}
