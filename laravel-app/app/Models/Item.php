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
        'organization_id'

    ];

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class)
            ->withPivot('id','description', 'cost', 'hours')
            ->withTimestamps();
    }

}
