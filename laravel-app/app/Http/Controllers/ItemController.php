<?php

namespace App\Http\Controllers;

use App\Exports\ItemExport;
use App\Http\Requests\StoreItemRequest;
use App\Imports\Item\ItemImportImpl;
use App\Imports\Item\ItemImportValidation;
use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use JetBrains\PhpStorm\ArrayShape;
use Maatwebsite\Excel\Facades\Excel;
use mysql_xdevapi\Exception;

class ItemController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('check.subscription.role');
    }

    public function index(Request $request)
    {
        $page = $request->query('page') ?? 1;
        $limit = $request->query('limit') ?? 10;
        $keyword = $request->query('keyword', '');
        $sortOrder = $request->query('sort_order', 'desc'); // Default sort order is descending

        $query = Item::where('organization_id', auth()->id()?->organiazation_id)
            ->when($keyword, function ($q) use ($keyword) {
                $q->where(function ($innerQ) use ($keyword) {
                    $innerQ->where('name', 'LIKE', "%$keyword%");
                });
            })
            ->orderBy($request->query('sort_by', 'created_at'), $sortOrder); // Sort by the specified column and order

        $item = $query->paginate($limit, ['*'], 'page', $page);

        try {
            return Response::customJson(200, $item, "success");
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
    public function store(StoreItemRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $validatedData['organization_id'] = auth()->id()?->organization_id;
            $item = Item::create($validatedData);
            return Response::customJson(200, $item, trans('customer.create_success'));
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
    public function update(StoreItemRequest $request, $id)
    {
        try {
            // Find the customer by ID
            $item = Item::findOrFail($id);

            // Check if the customer belongs to the organization
            if ($item?->organization_id !== auth()->user()?->organization_id) {
                return Response::customJson(403, null, "Unauthorized");
            }

            // Update the customer
            $item->update($request->validated());

            return Response::customJson(200, $item, trans('customer.update_success'));
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
            $item = Item::findOrFail($id);

            // Check if the customer belongs to the organization
            if ($item?->organization_id !== auth()->user()?->organization_id) {
                return Response::customJson(403, null, "Unauthorized");
            }

            $item->delete();

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

            $result = $this->isValidCSVFormat($csvPath);
            if ($result["valid"]) {
                return Response::customJson(200, $result, "CSV's format is valid");
            }
            return Response::customJson(400, $result, "CSV is not valid");

        } catch (Exception $e) {

            return Response::customJson(500, null, $e->getMessage());
        }
    }

    #[ArrayShape(["valid" => "bool", "errors" => "\Illuminate\Support\Collection|\Maatwebsite\Excel\Validators\Failure[]", "data" => "mixed"])] private function isValidCSVFormat($csvPath): array
    {
        $valid = true;
        $expectedHeaders = ['name','price'];
        $reader = Excel::toArray(new ItemImportValidation(), $csvPath);

        $rows = $reader[0];
        $headerRow = array_keys($rows[0]);
        $import = new ItemImportValidation();
        $import->import($csvPath);
        $failures = $import->failures();

        if (count($headerRow) != count($expectedHeaders) || $headerRow != $expectedHeaders || count($failures) > 0) {
            $valid = false;
        }
        return ["valid" => $valid, "errors" => $failures, "data" => $valid ? $import->toArray($csvPath) : null];
    }

    public function saveCSV(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv|max:2048',
        ]);

        try {
            $csvPath = $request->file('csv_file')->getRealPath();


            $import = new ItemImportImpl();
            $import->import($csvPath);
            if ($import->failures()->isNotEmpty()) {
                return Response::customJson(400, null, $import->failures());
            }
            return Response::customJson(200, null, "Successfully imported");
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

    public function exportCsv()
    {
        try {
            $now = Carbon::now()->format('Y-m-d H:i:s');
            return Excel::download(new ItemExport(), 'items_' . $now . '.csv');
        } catch (\Exception $e) {
            return Response::customJson(500, null, $e->getMessage());
        }
    }

}
