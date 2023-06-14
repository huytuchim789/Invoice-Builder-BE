<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Imports\CustomerImport;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;

class CustomerController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index()
    {
        try {
            $customers = Customer::all();
            return Response::customJson(200, $customers, trans('customer.list_success'));
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreCustomerRequest $request)
    {
        try {
            $customer = Customer::create($request->validated());
            return Response::customJson(200, $customer, trans('customer.create_success'));
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        try {
            // Find the customer by ID
            $customer = Customer::findOrFail($id);
            // Update the customer
            $customer->update($request->validated());

            return Response::customJson(200, $customer, trans('customer.update_success'));
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Find the customer by ID
            $customer = Customer::findOrFail($id);

            // Delete the customer
            $customer->delete();

            return Response::customJson(200, null, trans('customer.delete_success'));
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function validateCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv|max:2048',
        ]);

        try {
            $csvPath = $request->file('csv_file')->getRealPath();

            $reader = Excel::toArray(new CustomerImport(), $csvPath);

            $rows = $reader[0];

            if ($this->isValidCSVFormat($rows[0])) {
                return Response::customJson(200, null, "CSV is valid");
            }
            return Response::customJson(400, null, "CSV's format is in valid");

        } catch (ValidationException $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    private function isValidCSVFormat($headerRow)
    {
        $expectedHeaders = ['name', 'company', 'email', 'country', 'address', 'contact_number', 'contact_number_country'];

        if (count($headerRow) !== count($expectedHeaders)) {
            return false; // Number of columns in header does not match expected headers.
        }

        return $expectedHeaders == $headerRow;
    }
}
