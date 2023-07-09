<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ItemExport implements FromCollection, WithHeadings
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Item::select('name', 'price')->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return ['Name', 'Price'];
    }
}
