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
use App\Models\Item;
use App\Models\Pin;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Exception;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Transfer;
use ZipArchive;

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

    private function saveInvoice(mixed $validatedData)
    {
        // Retrieve validated data from the request

        // Create a new invoice
        $invoice = new Invoice();
        $invoice->issued_date = $validatedData['issued_date'];
        $invoice->created_date = $validatedData['created_date'];
        $invoice->note = $validatedData['note'];
        $invoice->sale_person = $validatedData['sale_person'];
        $invoice->sender_id = $validatedData['sender_id'];
        $invoice->customer_id = $validatedData['customer_id'];


        $invoice->save();
        $qrCodeContents = "/preview/{$invoice->id}";
        $publicId = "qrcode_{$invoice->id}";
        $qrImg = Storage::disk('temporary')->get($this->generateQRCode($qrCodeContents, $publicId));

        $qrCodeUrl = Cloudinary::upload($qrImg, [
            'public_id' => $publicId,
            'upload_preset' => 'xc7j5cl5'
        ])->getSecurePath();

        // Save the QR code URL to the invoice
        $invoice->qr_code = $qrCodeUrl;

        if (Storage::disk('temporary')->exists($publicId . '.png')) {
            Storage::disk('temporary')->delete($publicId . '.png');
        }
        // Prepare item data for mass insertion
        $itemsData = [];
        $total = 0;
        foreach ($validatedData['items'] as $itemData) {
            $itemsData[] = [
                'id' => Str::uuid()->toString(),
                'item_id' => $itemData['id'],
                'description' => $itemData['description'],
                'cost' => $itemData['quantity'],
                'hours' => $itemData['hours'],
                'invoice_id' => $invoice->id,
            ];
            $item = Item::find($itemData['id']);
            $total += $itemData['quantity'] * $itemData['hours'] * $item->price;
        }
        $invoice->tax = 8;
        $invoice->total = round(($total * ($invoice->tax + 100)) / 100, 2);

        $invoice->save();

        InvoiceItem::insert($itemsData);
        $this->generatePdf($invoice, 'new');
        return $invoice;
    }

    private function generateQRCode(string $data, string $filename)
    {
        $options = new QROptions([
            'version' => 5, // QR code version (adjust as needed)
            'outputType' => QRCode::OUTPUT_IMAGE_PNG, // Output as PNG image
            'eccLevel' => QRCode::ECC_L, // Error correction level (adjust as needed)
        ]);

        $qrCode = new QRCode($options);
        $qrCodeImage = $qrCode->render($data);

        $path = "{$filename}.png"; // Relative path to store the QR code image

        // Save the QR code image to the storage/app/public directory
        Storage::disk('temporary')->put($path, $qrCodeImage);
        return $path; // Return the relative path to the saved QR code image
    }

    /**
     * @throws Exception
     */

    private function generatePdf($invoice, $type)
    {
        $currentTime = Carbon::now()->format('Ymd_His');

        $pdf = PDF::loadView('pdf.pdf', ['invoiceData' => $invoice->load(['items', 'customer']), 'organization' => Auth::user()->organization]);
        $filePath = storage_path('app' . DIRECTORY_SEPARATOR . 'temporary' . DIRECTORY_SEPARATOR . $invoice->code . '_' . $currentTime . '.pdf');
        $file = $pdf->save($filePath);
        if ($type == 'new') {
            $invoice->attachMedia(new File($filePath), [
                'upload_preset' => $this->uploadPreset, 'public_id' => $invoice->code . '_' . $currentTime
            ]);
        } else {
            $invoice->updateMedia(new File($filePath), [
                'upload_preset' => $this->uploadPreset, 'public_id' => $invoice->code . '_' . $currentTime
            ]);
        }
        unlink($filePath);

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
            $invoice->tax = 8;
            $invoice->sale_person = $validatedData['sale_person'];
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
            $total = 0;
            foreach ($validatedData['items'] as $itemData) {
                if (isset($itemData['id'])) {
                    // Update existing item
                    $item = InvoiceItem::findOrFail($itemData['id']);
                    if (isset($itemData['is_deleted']) && $itemData['is_deleted'] == true) {
                        $item->delete();
                        continue;
                    }
                    $item->item_id = $itemData['item_id'];
                    $item->description = $itemData['description'] ?? '';
                    $item->cost = $itemData['quantity'];
                    $item->hours = $itemData['hours'];
                    $item->save();
                } else {
                    // Create new item
                    $item = new InvoiceItem([
                        'item_id' => $itemData['item_id'],
                        'description' => $itemData['description'] ?? '',
                        'cost' => $itemData['quantity'],
                        'hours' => $itemData['hours'],
                        'invoice_id' => $invoice->id,
                    ]);
                    $item->save();
                }
                $total += $itemData['quantity'] * $itemData['hours'] * $item->item->price;
            }
            $invoice->total = round(($total * ($invoice->tax + 100)) / 100, 2);
            $invoice->save();

            // Delete any items that were not included in the updated item data
//            $invoice->items()->whereNotIn('id', array_column($itemsData, 'id'))->delete();

            $this->generatePdf($invoice, 'update');
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

    public function downloadFile(Request $request)
    {
        $emailTransactionIds = $request->input('email_transaction_ids');
        try {
            $invoiceIds = EmailTransaction::whereIn('id', $emailTransactionIds)->distinct()->pluck('invoice_id');

            if ($invoiceIds->isEmpty()) {
                return Response::customJson(404, null, "Invoices not found");
            }

            $invoices = Invoice::whereIn('id', $invoiceIds)->get();
            if ($invoices->isEmpty()) {
                return Response::customJson(404, null, "Invoices not found");
            }


            if (count($emailTransactionIds) == 1) {
                $invoice = $invoices->first();
                $file = $invoice->fetchFirstMedia();
                if (!$file) {
                    return Response::customJson(404, null, "File not found");
                }
                //            $fileUrl = $file->getFullUrl();
                return Response::customJson(200, $file, "success");
            } else {
                $zip = new ZipArchive();
                $zipFileName = 'invoices.zip';
                $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
                foreach ($invoiceIds as $invoiceId) {
                    $invoice = Invoice::find($invoiceId);
                    if (!$invoice) {
                        return Response::customJson(404, null, "Invoice not found");
                    }
                    $file = $invoice->fetchFirstMedia();
                    if (!$file) {
                        return Response::customJson(404, null, "File not found");
                    }
                    $pdfContent = file_get_contents($file->file_url);

                    // Set the appropriate MIME type based on the file extension
                    // Add the file with the correct MIME type to the zip archive
                    $zip->addFromString($file->file_name . '.pdf', $pdfContent);
                }
                $zip->close();
                return response()->download($zipFileName)->deleteFileAfterSend(true);
            }
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
////                        'source_transaction' => $stripeCharge->id,
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
            $invoice->load(['items', 'customer', 'emailTransaction']);

            return Response::customJson(200, $invoice, "Invoice status updated successfully.");
        } catch (Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

}
