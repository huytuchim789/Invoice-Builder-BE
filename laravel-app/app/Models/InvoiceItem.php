<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'invoice_item';
    protected $fillable = [
        'description',
        'cost',
        'hours',
        'invoice_id',
        'item_id'
    ];
}
