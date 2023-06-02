<?php

namespace App\Http\Controllers;

use App\Events\EmailTransactionStatusUpdated;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StoreSendEmailTransactionRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Jobs\SendMailJob;
use App\Models\EmailTransaction;
use App\Models\Invoice;
use App\Models\Item;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


        protected  $uploadPreset;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->uploadPreset=Config::get('cloudinary.upload_preset');
    }

    public function index()
    {
        try {
            $invoices = Invoice::with(['items', 'customer'])->paginate(15);
            return Response::customJson(200, $invoices, "success");
        } catch (Exception $e) {
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
     * @param StoreInvoiceRequest $request
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
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * @throws Exception
     */
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
        if ($validatedData['file'])
            $invoice->attachMedia($validatedData['file'], ['upload_preset' => $this->uploadPreset]);
        // Insert items into the database in a single query
        Item::insert($itemsData);
        return $invoice;
    }

    /**
     * Display the specified resource.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::with(['items', 'customer'])->find($id);
            if (!$invoice)
                return Response::customJson(404, $invoice, "Not Found");
            return Response::customJson(200, $invoice, "success");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateInvoiceRequest $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        try {
            $validatedData = $request->validated();

            // Update the invoice data
            $invoice->issued_date = $validatedData['issued_date'];
            $invoice->created_date = $validatedData['created_date'];
            $invoice->note = $validatedData['note'];
            $invoice->tax = $validatedData['tax'];
            $invoice->sale_person = $validatedData['sale_person'];
            $invoice->total = $validatedData['total'];

            $invoice->save();

            // Update or create the items
            $itemsData = [];
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = Item::find($itemData['id']);
                    $item->name = $itemData['name'];
                    $item->description = $itemData['description'] ?? '';
                    $item->cost = $itemData['cost'];
                    $item->hours = $itemData['hours'];
                    $item->price = $itemData['price'];
                    $item->save();
                } else {
                    // Create new item
                    $item = new Item([
                        'name' => $itemData['name'],
                        'description' => $itemData['description'] ?? '',
                        'cost' => $itemData['cost'],
                        'hours' => $itemData['hours'],
                        'price' => $itemData['price'],
                        'invoice_id' => $invoice->id,
                    ]);
                    $item->save();
                }
                $itemsData[] = $item;
            }

            // Delete any items that were not included in the updated item data
            $invoice->items()->whereNotIn('id', array_column($itemsData, 'id'))->delete();

            // Update the attached file if provided
            if ($validatedData['file']) {
                $invoice->updateMedia($validatedData['file'], ['upload_preset' => $this->uploadPreset]);
            }

            return Response::customJson(200, $invoice, "Invoice updated successfully.");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Invoice $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //
    }

    public function sendEmail(StoreSendEmailTransactionRequest $request)
    {
        try {
            $emailTransaction = null;
            $file = $request->file('file');
            $filePath = $file->store('', 'temporary');
            $sender = auth()->user();
            $page = $request->query('page') + 1 ?? 1;

            $invoice = Invoice::find($request->invoice_id);
            if (!$invoice) {
                return Response::customJson(404, null, "Invoice not found");
            }

            $existingTransaction = EmailTransaction::where('invoice_id', $request->invoice_id)->first();
            if ($existingTransaction) {
                if ($existingTransaction->status == 'sent' || $existingTransaction->status == 'failed') {
                    $emailTransaction = $existingTransaction;
                    $emailTransaction->status = 'pending';
                    event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray(), $page));
                    $message = "Resend Successfully";
                } elseif ($existingTransaction->status == 'draft') {
                    $emailTransaction = $existingTransaction;
                    $emailTransaction->status = 'pending';
                    event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray(), $page));
                    $message = "Send Successfully";
                }
            } else {
                // Create a new email transaction
                $emailTransaction = EmailTransaction::create([
                    'invoice_id' => $invoice->id,
                    'status' => 'pending',
                ]);
                event(new EmailTransactionStatusUpdated($sender, $emailTransaction, $page));
                $message = "Send Successfully";
            }

            $emailInfo = [
                "subject" => $request->subject ?? '',
                "message" => $request->message ?? '',
            ];

            dispatch(new SendMailJob($emailTransaction, $filePath, $emailInfo, $sender, $page));

            return Response::customJson(200, null, $message);
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }
}
