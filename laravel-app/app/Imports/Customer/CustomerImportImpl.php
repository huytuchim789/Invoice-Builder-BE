<?php

namespace App\Imports\Customer;

use App\Models\Customer;

class CustomerImportImpl extends CustomerImport
{

    public function model(array $row)
    {
        return Customer::create(
            // ['email' => $row['email']], // Search condition
            [
                'email' => $row['email'],
                'name' => $row['name'],
                'company' => $row['company'],
                'country' => $row['country'],
                'address' => $row['address'],
                'contact_number' => $row['contact_number'],
                'contact_number_country' => $row['contact_number_country'],
                'user_id' => auth()->user()->id
            ]
        );
    }
}
