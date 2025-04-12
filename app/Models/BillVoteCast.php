<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillVoteCast extends Model
{
    
    protected $fillable = [
        'bill_url',
        'session',
        'user_id',
        'is_supported',
    ];
}
