<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'number',
        'coordinate_X',
        'coordinate_Y',
        'invoice_id',
        'user_id',
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function user(){
        return $this->belongsTo(User::class);
    }
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
