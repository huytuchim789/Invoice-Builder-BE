<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'issued_date',
        'created_date',
        'note',
        'tax',
        'sale_person'
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
