<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Imports\HeadingRowFormatter;


abstract class ItemImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts,WithUpserts
{

    use  Importable, SkipsFailures;

    /**
     * @param array $row
     *
     * @return string
     */


    public function uniqueBy(): string
    {
        return 'name';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|int',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'price.required' => 'Price is required.',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

}
