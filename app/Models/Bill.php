<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    protected $fillable = [
        'session',
        'introduced',
        'short_name',
        'name',
        'number',
        'politician_id',
        'bill_url',
        'is_government_bill',
        'bills_json',
    ];
}
