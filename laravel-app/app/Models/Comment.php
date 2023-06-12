<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory,HasUuids;

    protected $fillable = [
        'invoice_id',
        'user_id',
        'pin_id',
        'content'
    ];
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
