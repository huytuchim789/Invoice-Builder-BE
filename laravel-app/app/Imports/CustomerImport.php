<?php

namespace App\Imports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\ToModel;

class CustomerImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return Customer|null
     */
    public function model(array $row)
    {
        return new Customer([
            'name' => $row['name'],
            'company' => $row['company'],
            'email' => $row['email'],
            'country' => $row['country'],
            'address' => $row['address'],
            'contact_number' => $row['contact_number'],
            'contact_number_country' => $row['contact_number_country'],
        ]);
    }
}
