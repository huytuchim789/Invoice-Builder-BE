<?php

namespace App\Models;

use CloudinaryLabs\CloudinaryLaravel\MediaAlly;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, HasUuids, MediaAlly;

    protected $fillable = [
        'code',
        'issued_date',
        'created_date',
        'note',
        'tax',
        'sale_person',
        'sender_id',
        'customer_id',
        'organization_id',
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
    public function pins()
    {
        return $this->hasMany(Pin::class);
    }
    public function media()
    {
        return $this->morphMany(Media::class, 'medially');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            $latestInvoice = static::latest('id')->first();
            $invoiceNumber = $latestInvoice ? (int)substr($latestInvoice->code, 5) + 1 : 1;
            $invoice->code = 'INVC-' . str_pad($invoiceNumber, 4, '0', STR_PAD_LEFT);
        });
    }
}
