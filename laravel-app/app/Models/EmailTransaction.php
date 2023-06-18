<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ['invoice_id', 'status', 'error_message', 'method'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
