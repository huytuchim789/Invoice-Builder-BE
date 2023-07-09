<?php

namespace App\Imports;


use App\Models\Item;

class ItemImportImpl extends CustomerImport
{

    public function model(array $row)
    {
        return Item::updateOrCreate(
            ['nane' => $row['nane']], // Search condition
            [
                'price' => $row['price'],
                'organization_id' => auth()->user()->organization_id
            ]
        );
    }
}
