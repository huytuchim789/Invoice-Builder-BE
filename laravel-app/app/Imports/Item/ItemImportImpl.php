<?php

namespace App\Imports\Item;
use App\Models\Item;

class ItemImportImpl extends ItemImport
{

    public function model(array $row)
    {
        return Item::updateOrCreate(
            ['name' => $row['name']], // Search condition
            [
                'price' => $row['price'],
                'organization_id' => auth()->user()->organization_id
            ]
        );
    }
}
