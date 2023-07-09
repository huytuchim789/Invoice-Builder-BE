<?php

namespace App\Imports\Customer;

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


abstract class CustomerImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure, WithBatchInserts,WithUpserts
{

    use  Importable, SkipsFailures;

    /**
     * @param array $row
     *
     * @return string
     */


    public function uniqueBy(): string
    {
        return 'email';
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'company' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'country' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'contact_number' => 'required|phone',
            'contact_number_country' => 'required_with:contact_number',
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'company.required' => 'Company is required.',
            'email.required' => 'Email is required.',
            'country.required' => 'Country is required.',
            'address.required' => 'Address is required.',
            'contact_number.required' => 'Contact number is required.',
            'contact_number.phone' => 'Contact number must be a valid phone number.',
            'contact_number_country.required_with' => 'Contact number country is required when contact number is present.',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

}
