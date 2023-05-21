<?php

namespace App\Models;

use Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Customer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'company',
        'email',
        'country',
        'address',
        'contact_number',
        'contact_number_country',

    ];

    protected $casts = [
        'contact_number' => RawPhoneNumberCast::class . ':contact_number_country',
    ];
}
