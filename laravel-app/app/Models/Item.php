<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'price',

    ];

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot('description', 'cost', 'hours')
            ->withTimestamps();
    }

}
