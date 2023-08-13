<?php

namespace App\Imports\Item;
use App\Models\Item;

class ItemImportImpl extends ItemImport
{

    public function model(array $row)
    {
        return Item::create(
            // ['name' => $row['name']], // Search condition
            [   'name' => $row['name'],
                'price' => $row['price'],
                'organization_id' => auth()->user()->organization_id
            ]
        );
    }
}
