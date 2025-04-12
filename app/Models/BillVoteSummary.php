<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillVoteSummary extends Model
{
    protected $fillable = [
        'bill_url',
        'session',
        'description',
        'result',
        'vote_url',
        'vote_json',
    ];
}
