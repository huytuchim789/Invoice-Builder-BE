<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmailTransaction extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['invoice_id', 'status', 'error_message'];


    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
