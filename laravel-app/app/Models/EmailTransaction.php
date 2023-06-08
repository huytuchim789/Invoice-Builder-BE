<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class EmailTransaction extends Model
{
    use HasFactory, HasUuids;
    protected $fillable = ['invoice_id', 'status', 'error_message'];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
