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
        'sale_person',
        'sender_id',
        'customer_id'
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
    public function pins(){
        return $this->hasMany(Pin::class);
    }
}
