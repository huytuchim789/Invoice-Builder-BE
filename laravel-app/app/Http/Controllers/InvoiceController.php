<?php

namespace App\Http\Controllers;

use App\Events\EmailTransactionStatusUpdated;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\StoreMultipleSendEmailTransactionRequest;
use App\Http\Requests\StoreSendEmailTransactionRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Jobs\SendMailJob;
use App\Models\EmailTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Pin;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Transfer;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    protected $uploadPreset;

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('check.subscription.role')->except(['downloadFile', 'listPins']);
        $this->uploadPreset = Config::get('cloudinary.upload_preset');
    }

    public function index()
    {
        try {
            $invoices = Invoice::with(['items', 'customer'])->get();
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
                'method' => $validatedData['send_method'],
                'email_subject' => $validatedData['subject'] ?? '',
                'email_message' => $validatedData['message'] ?? '',

            ]);
            $invoice->load('items');

            // Append the invoice to the email transaction
            $emailTransaction->invoice = $invoice;
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
                'item_id' => $itemData['id'],
                'description' => $itemData['description'],
                'cost' => $itemData['cost'],
                'hours' => $itemData['hours'],
                'invoice_id' => $invoice->id,
            ];
        }
        if ($validatedData['file']) {
            $currentTime = Carbon::now()->format('Ymd_His');
            $fileName = pathinfo($validatedData['file']->getClientOriginalName(), PATHINFO_FILENAME) . '_' . $invoice->id . '_' . $currentTime;
            $invoice->attachMedia($validatedData['file'], [
                'upload_preset' => $this->uploadPreset, 'public_id' => $fileName
            ]);
        }
        // Insert items into the database in a single query
        InvoiceItem::insert($itemsData);
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
            $invoice = Invoice::with(['items', 'customer', 'emailTransaction'])->find($id);
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
            $invoice->customer_id = $validatedData['customer_id'];
            $invoice->save();


            $emailTransaction = $invoice->emailTransaction;
            if ($emailTransaction) {
                // Update existing email transaction
                $emailTransaction->method = $validatedData['send_method'];
                $emailTransaction->email_subject = $validatedData['subject'] ?? '';
                $emailTransaction->email_message = $validatedData['message'] ?? '';
                $emailTransaction->save();
            } else {
                // Create new email transaction
                $emailTransaction = new EmailTransaction([
                    'invoice_id' => $invoice->id,
                    'method' => $validatedData['send_method'],
                    'email_subject' => $validatedData['subject'] ?? '',
                    'email_message' => $validatedData['message'] ?? '',
                ]);
                $emailTransaction->save();
            }
            // Update or create the items
            $itemsData = [];
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = InvoiceItem::findOrFail($itemData['id']);
//                    dd(isNull($item));
//                    if (isNull($item))
//                        continue;
                    if (isset($itemData['is_deleted']) && $itemData['is_deleted'] == true) {
                        $item->delete();
                        continue;
                    }
                    $item->item_id = $itemData['item_id'];
                    $item->description = $itemData['description'] ?? '';
                    $item->cost = $itemData['cost'];
                    $item->hours = $itemData['hours'];
                    $item->save();
                } else {
                    // Create new item
                    $item = new InvoiceItem([
                        'item_id' => $itemData['item_id'],
                        'description' => $itemData['description'] ?? '',
                        'cost' => $itemData['cost'],
                        'hours' => $itemData['hours'],
                        'invoice_id' => $invoice->id,
                    ]);
                    $item->save();
                }
                $itemsData[] = $item;
            }

            // Delete any items that were not included in the updated item data
//            $invoice->items()->whereNotIn('id', array_column($itemsData, 'id'))->delete();

            // Update the attached file if provided
            if ($validatedData['file']) {
                $currentTime = Carbon::now()->format('Ymd_His');
                $fileName = pathinfo($validatedData['file']->getClientOriginalName(), PATHINFO_FILENAME) . '_' . $invoice->id . '_' . $currentTime;
                $invoice->updateMedia($validatedData['file'], ['upload_preset' => $this->uploadPreset, 'public_id' => $fileName]);
            }
            $invoice->load(['items', 'customer']);
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
            $sender = auth()->user();
            $request->validated();
            $invoice = Invoice::find($request->invoice_id);
            $message = [];
            if (!$invoice) {
                return Response::customJson(404, null, "Invoice not found");
            }

            $existingTransaction = EmailTransaction::where(['invoice_id' => $request->invoice_id, 'method' => $request->send_method])->first();
            if ($existingTransaction) {
                if ($existingTransaction->status == 'sent' || $existingTransaction->status == 'failed') {
                    $emailTransaction = $existingTransaction;
                    $emailTransaction->status = 'pending';
                    event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray()));
                    $message[$emailTransaction->id] = "Resend Successfully";
                } elseif ($existingTransaction->status == 'draft') {
                    $emailTransaction = $existingTransaction;
                    $emailTransaction->status = 'pending';
                    event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray()));
                    $message[$emailTransaction->id] = "Send Successfully";
                }
            } else {
                // Create a new email transaction
                $emailTransaction = EmailTransaction::create([
                    'invoice_id' => $invoice->id,
                    'status' => 'pending',
                    'method' => $request->send_method,
                ]);
                event(new EmailTransactionStatusUpdated($sender, $emailTransaction));
                $message[$emailTransaction->id] = "Send Successfully";
            }

            $emailInfo = [
                "subject" => $emailTransaction->subject ?? '',
                "message" => $emailTransaction->message ?? '',
            ];

            dispatch(new SendMailJob($emailTransaction, $emailInfo, $sender));

            return Response::customJson(200, null, $message);
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function sendMultipleEmail(StoreMultipleSendEmailTransactionRequest $request)
    {
        try {
            $emailTransactions = new Collection();
            $sender = auth()->user();
            $emailTransactionIds = $request->input('emailtransaction_ids');
            $request->validated();
            $message = [];
            $emailTransactionIds = collect($emailTransactionIds);
            $emailTransactionIds
                ->map(function ($emailTransactionId) use ($sender, $request, &$emailTransactions, &$message) {
                    $emailTransaction = EmailTransaction::find($emailTransactionId);
                    if (!$emailTransaction) {
                        $message[$emailTransactionId] = "Email Transaction not found";
                        return;
                    }
                    if ($emailTransaction->status == 'sent' || $emailTransaction->status == 'failed') {
                        $emailTransaction->status = 'pending';
                        event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray()));
                        $message[$emailTransaction->id] = "Resend Successfully";
                    } elseif ($emailTransaction->status == 'draft') {
                        $emailTransaction->status = 'pending';
                        event(new EmailTransactionStatusUpdated($sender, $emailTransaction->toArray()));
                        $message[$emailTransaction->id] = "Send Successfully";

                    } else {
                        // Create a new email transaction
                        $emailTransaction->status = 'pending';
                        $emailTransaction->method = $request->send_method;
                        $emailTransaction->save();
                        event(new EmailTransactionStatusUpdated($sender, $emailTransaction));
                        $message[$emailTransaction->id] = "Send Successfully";
                    }

                    $emailTransactions->push($emailTransaction);

                    $emailInfo = [
                        "subject" => $emailTransaction->email_subject ?? '',
                        "message" => $emailTransaction->email_message ?? '',
                    ];

                    dispatch(new SendMailJob($emailTransaction, $emailInfo, $sender));
                });

            return Response::customJson(200, $emailTransactions->pluck('id')->toArray(), $message);
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function downloadFile($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);
            if (!$invoice) {
                return Response::customJson(404, null, "Invoice not found");
            }
            $file = $invoice->fetchFirstMedia();
            if (!$file) {
                return Response::customJson(404, null, "File not found");
            }
            //            $fileUrl = $file->getFullUrl();
            return Response::customJson(200, $file, "success");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function listPins($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);
            $file = $invoice->fetchFirstMedia();
            if (!$file) {
                return Response::customJson(404, null, "File not found");
            }
            $pins = Pin::with(['comments.user'])->where('invoice_id', $invoiceId)->get();
            return Response::customJson(200, ["pins" => $pins, "file_url" => $this->replaceFileExtension($file->file_url)], "success");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    private function replaceFileExtension($pdfUrl)
    {
        $pngUrl = pathinfo($pdfUrl, PATHINFO_DIRNAME) . '/' . pathinfo($pdfUrl, PATHINFO_FILENAME) . '.png';

        return $pngUrl;
    }

    public function payInvoice(Request $request)
    {
        $user = Auth::user();
        $stripeCustomerId = $user->stripe_id;
        Stripe::setApiKey(env('STRIPE_SECRET'));

        $stripeCustomer = Customer::retrieve($stripeCustomerId);
        $defaultPaymentMethodId = $stripeCustomer->invoice_settings->default_payment_method;
        $invoiceCodes = $request->input('invoice_codes');
        $invoices = Invoice::whereIn('code', $invoiceCodes)->where('is_paid', false)->get();
        // Calculate the total amount to charge based on the 'total' field of each invoice
        $totalAmount = $invoices->sum('total');
        if ($totalAmount == 0) {
            return Response::customJson(200, null, "No Invoice to pay");
        }
        if (empty($defaultPaymentMethodId)) {
            return Response::customJson(500, null, "No payment method found");
        }
        // Charge the user's payment method
        $stripeCharge = $user->charge($totalAmount * 100, $defaultPaymentMethodId, [
            'currency' => 'usd',
            'description' => 'Invoice payment',
            'metadata' => [
                'invoice_codes' => implode(",", $invoiceCodes),
            ],
        ]);
        $transfer = null;
        if ($stripeCharge->status === 'succeeded') {

            foreach ($invoices as $invoice) {
//                $customer = Customer::retrieve($invoice->user->stripe_id);
//                if ($customer->id) {
//                    $transfer = Transfer::create([
//                        'amount' => $invoice->total * 100, // Stripe accepts amounts in cents
//                        'currency' => 'usd',
//                        'source_transaction' => $stripeCharge->id,
//                        'destination' => $customer->id,
//                        'description' => 'Invoice payment transfer',
//                    ]);
//
//                }
                $invoice->is_paid = true;
                $invoice->save();
            }

            return Response::customJson(200, $stripeCharge, "Invoice payment successful");
        } else {
            return Response::customJson(500, null, "Invoice payment failed");
        }
    }

    public function getTotalSum(Request $request)
    {
        try {
//            $invoiceCodes = $request->get('invoice_codes', []);
            $validatedData = $request->validate([
                'invoice_codes' => 'required|array',
                'invoice_codes.*' => 'exists:invoices,code',
            ]);
            $totalSum = Invoice::whereIn('code', $validatedData['invoice_codes'])
                ->sum('total');

            return Response::customJson(200, $totalSum, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function maskAsPaid($invoiceId)
    {
        try {
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                return Response::customJson(404, null, "Invoice not found");
            }

            $currentIsPaidStatus = $invoice->is_paid;
            $newIsPaidStatus = !$currentIsPaidStatus;

            $invoice->is_paid = $newIsPaidStatus;
            $invoice->save();

            return Response::customJson(200, $invoice, "Invoice status updated successfully.");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

}
