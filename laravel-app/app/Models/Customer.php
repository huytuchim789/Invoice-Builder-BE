<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'company',
        'email',
        'country',
        'address',
        'contact_number',
        'contact_number_country',

    ];
    public $casts = [
        'contact_number' => RawPhoneNumberCast::class . ':contact_number_country',
    ];
}
