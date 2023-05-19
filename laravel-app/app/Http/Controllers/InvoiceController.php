<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $invoices = Invoice::with(['items', 'customer'])->paginate(15);
            return  Response::customJson(200, $invoices, "success");
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
     * @param  \App\Http\Requests\StoreInvoiceRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreInvoiceRequest $request)
    {
        try {
            // Retrieve validated data from the request
            $validatedData = $request->validated();

            // Create a new invoice
            $invoice = new Invoice();
            $invoice->issued_date = $validatedData['issued_date'];
            $invoice->created_date = $validatedData['created_date'];
            $invoice->note = $validatedData['note'];
            $invoice->tax = $validatedData['tax'];
            $invoice->sale_person = $validatedData['sale_person'];
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

            // Return a response indicating success
            return  Response::customJson(200, null, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $invoice = Invoice::find($id);
            if (!$invoice)
                return  Response::customJson(404, $invoice, "Not Found");
            return  Response::customJson(200, $invoice, "success");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function edit(Invoice $invoice)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateInvoiceRequest  $request
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return \Illuminate\Http\Response
     */
    public function destroy(Invoice $invoice)
    {
        //
    }
}
