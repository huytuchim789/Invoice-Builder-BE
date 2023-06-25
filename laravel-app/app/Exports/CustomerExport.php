<?php

namespace App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Customer::select('name', 'company', 'email', 'country', 'address', 'contact_number', 'contact_number_country')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Name', 'Company', 'Email', 'Country', 'Address', 'Contact Number', 'Contact Number Country'];
    }
}
